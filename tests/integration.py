"""Real C++ / PHP HTTP / SQLite integration checks; no third-party Python packages.
Run from repository root: python tests/integration.py --php C:/xampp/php/php.exe
Only a temporary database under build/ is used. TEST_CASES.md records executed results.
"""
import argparse
from contextlib import closing
import http.cookiejar
import os
from pathlib import Path
import re
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.parse
import urllib.request
from datetime import datetime, timezone

ROOT = Path(__file__).resolve().parents[1]
RESULTS = []


def check(label, action):
    try:
        action()
    except Exception as error:
        RESULTS.append((label, 'FAIL', str(error)))
        raise
    else:
        RESULTS.append((label, 'PASS', 'Executed successfully'))
        print('PASS:', label)


def require(condition, message='Unexpected result'):
    if not condition:
        raise AssertionError(message)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--php', default='php')
    args = parser.parse_args()
    os.chdir(ROOT)
    (ROOT / 'build').mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='integration-', dir=ROOT / 'build') as directory:
        directory = Path(directory)
        path = directory / 'exchange.db'
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        base = f'http://127.0.0.1:{port}/'
        environment = os.environ.copy()
        environment['SKILL_EXCHANGE_DB'] = str(path)
        log = open(directory / 'php.log', 'w', encoding='utf-8')
        server = subprocess.Popen([args.php, '-d', f'session.save_path={directory}', '-S', f'127.0.0.1:{port}', '-t', 'dashboard'], env=environment, stdout=log, stderr=log)
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

        def get(page='overview', extra=''):
            with opener.open(base+'?page='+page+extra, timeout=5) as response:
                body = response.read().decode('utf-8')
                require(response.status == 200 and 'Fatal error' not in body, body)
                return body

        def post(page, action, success=True, **fields):
            body = get(page)
            token = re.search(r'name="csrf" value="([a-f0-9]+)"', body).group(1)
            data = {'csrf': token, 'action': action, **fields}
            request = urllib.request.Request(base+'?page='+page, data=urllib.parse.urlencode(data).encode())
            with opener.open(request, timeout=5) as response:
                body = response.read().decode('utf-8')
                marker = 'notice success' if success else 'notice error'
                require(marker in body, body)
                return body

        def console(data):
            process = subprocess.run([str(ROOT/'build/skill_exchange.exe'), str(path)], input=data, text=True, capture_output=True, timeout=10)
            require(process.returncode == 0, process.stderr)
            return process.stdout

        def rows(sql, params=()):
            with closing(sqlite3.connect(path)) as connection:
                return connection.execute(sql, params).fetchall()

        def reject_sql(sql):
            with closing(sqlite3.connect(path)) as connection:
                connection.execute('PRAGMA foreign_keys=ON')
                try:
                    connection.execute(sql)
                except sqlite3.IntegrityError:
                    return
            raise AssertionError('Invalid SQL accepted: '+sql)

        def schedule(session_id=301, **changes):
            data = dict(id=session_id, listing_id=201, learner_id=102, date='2026-11-20', time='14:30', duration_hours='2.5')
            data.update(changes)
            return post('sessions','session_create',**data)

        try:
            for _ in range(50):
                if server.poll() is not None:
                    raise RuntimeError('PHP server exited: '+(directory/'php.log').read_text())
                try:
                    get()
                    break
                except OSError:
                    time.sleep(.1)
            else:
                raise RuntimeError('PHP server did not become ready')

            check('All six empty dashboard pages render over HTTP', lambda: [get(p) for p in ['overview','students','skills','sessions','feedback','progress']])
            check('CSS and JavaScript served successfully', lambda: [require(opener.open(base+'assets/'+asset).status==200) for asset in ['style.css','app.js']])
            check('Console rejects trailing numeric text and empty names', lambda: require('This field cannot be empty' in console('1x\n1\n101\n   \nTutor\nC++\nMath\n0\n')))
            check('C++ registration appears in PHP dashboard', lambda: require('Tutor' in get('students') and '#101' in get('students')))
            check('PHP registration writes learner', lambda: post('students','student_save',id=102,name='Learner',skills_offered='Math',skills_needed='C++'))
            check('PHP changes appear after C++ restart', lambda: require('Learner' in console('2\n0\n')))
            check('Duplicate student IDs rejected in both interfaces', lambda: (post('students','student_save',False,id=101,name='Duplicate',skills_offered='x',skills_needed='x'),require('Invalid or duplicate' in console('1\n101\n0\n'))))
            check('Empty and malformed student fields rejected', lambda: (post('students','student_save',False,id=103,name=' ',skills_offered='x',skills_needed='x'),post('students','student_save',False,id='103x',name='x',skills_offered='x',skills_needed='x')))
            check('Profile editing, search and SQL-like input', lambda: (post('students','student_save',editing=1,id=102,name="Learner O'Neil",skills_offered='Math',skills_needed='C++'),require("Learner O&#039;Neil" in get('students','&q=O%27Neil')),require('No records yet' in get('students','&q=%27%20OR%201%3D1--'))))
            check('C++ listing creation visible on dashboard', lambda: (require('Skill listing created' in console('4\n201\n101\nC++\nOffer\nBasics\n0\n')),require('Basics' in get('skills'))))
            check('PHP Learn listing and search/type filtering', lambda: (post('skills','skill_save',id=202,student_id=102,skill_name='Calculus',type='Learn',description='Practice'),require('Calculus' in get('skills','&type=Learn&q=Calculus')),require('No records yet' in get('skills','&type=Offer&q=Calculus'))))
            check('Invalid listing owner and type rejected', lambda: (post('skills','skill_save',False,id=203,student_id=999,skill_name='x',type='Offer',description='x'),post('skills','skill_save',False,id=203,student_id=101,skill_name='x',type='Other',description='x')))
            check('Session scheduling and C++ visibility', lambda: (schedule(),require('Session ID: 301' in console('9\n0\n'))))
            check('Self tutoring, missing learner and Learn bookings rejected', lambda: [post('sessions','session_create',False,id=302,listing_id=listing,learner_id=learner,date='2026-11-20',time='14:30',duration_hours=1) for listing,learner in [(201,101),(201,999),(202,101)]])
            check('Invalid date, time, duration and duplicate session rejected', lambda: [post('sessions','session_create',False,**{**dict(id=302,listing_id=201,learner_id=102,date='2026-11-20',time='14:30',duration_hours=1),**bad}) for bad in [dict(date='2026-02-30'),dict(time='24:00'),dict(duration_hours=0),dict(duration_hours=25),dict(duration_hours='nan'),dict(id=301)]])
            check('Feedback before completion and Pending-to-Completed rejected', lambda: (post('feedback','feedback_save',False,session_id=301,rating=5,comments='too soon'),post('sessions','session_status',False,id=301,status='Completed')))
            check('PHP accepts session; C++ completes session', lambda: (post('sessions','session_status',id=301,status='Accepted'),require('Status updated' in console('10\n301\n3\n0\n')),require('Completed' in get('sessions'))))
            check('Progress derives from completed hours without feedback', lambda: (require(rows('SELECT completed_sessions,learning_hours FROM learning_progress WHERE id=102')==[(1,2.5)]),require('2.5 h' in get('progress')),require('Completed sessions: 1' in console('13\n102\n0\n'))))
            check('Invalid ratings and terminal status changes rejected', lambda: (post('feedback','feedback_save',False,session_id=301,rating=6,comments='bad'),post('feedback','feedback_save',False,session_id=301,rating='2.5',comments='bad'),post('sessions','session_status',False,id=301,status='Accepted')))
            check('PHP feedback visible in C++; output escaping and averages', lambda: (post('feedback','feedback_save',session_id=301,rating=5,comments='<script>alert(1)</script> Helpful'),require('Helpful' in console('12\n0\n')),require('&lt;script&gt;alert(1)&lt;/script&gt;' in get('feedback')),require(rows('SELECT AVG(rating) FROM feedback')==[(5.0,)])))
            check('Duplicate feedback rejected in both interfaces', lambda: (post('feedback','feedback_save',False,session_id=301,rating=4,comments='duplicate'),require('Feedback already exists' in console('11\n301\n0\n'))))
            check('Feedback and skill editing persist', lambda: (post('feedback','feedback_save',editing=1,session_id=301,rating=4,comments='Revised review'),post('skills','skill_save',editing=1,id=201,student_id=101,skill_name='C++ foundations',type='Offer',description='Updated'),require('Revised review' in console('12\n0\n')),require('C++ foundations' in console('5\n0\n'))))
            check('Booked listing ownership/type immutable and deletion protected', lambda: (post('skills','skill_save',False,editing=1,id=201,student_id=102,skill_name='x',type='Learn',description='x'),post('skills','delete',False,table='skill_listings',id=201),post('students','delete',False,table='students',id=101)))
            check('Missing or invalid CSRF rejected without mutation', lambda: (post('students','student_save',False,csrf='bad',id=104,name='Bad CSRF',skills_offered='x',skills_needed='x'),post('students','student_save',False,csrf='',id=104,name='Missing CSRF',skills_offered='x',skills_needed='x'),require(not rows('SELECT * FROM students WHERE id=104'))))
            check('Database directly rejects foreign keys, bad dates, feedback identity and status bypasses', lambda: [reject_sql(sql) for sql in ["INSERT INTO skill_listings VALUES(999,999,'x','Offer','x')","INSERT INTO tutoring_sessions VALUES(302,201,102,101,'2026-02-30','12:00',1,'Pending')","INSERT INTO tutoring_sessions VALUES(302,201,102,101,'2026-11-20','12:00',1,'Completed')","UPDATE tutoring_sessions SET learner_id=101 WHERE id=301","UPDATE feedback SET student_id=101 WHERE session_id=301","UPDATE feedback SET rating=0 WHERE session_id=301"]])
            check('Rejected sessions remain terminal', lambda: (schedule(302),post('sessions','session_status',id=302,status='Rejected'),post('sessions','session_status',False,id=302,status='Accepted')))
            check('Console date/time validation and rejection flow', lambda: (require('Session requested' in console('8\n303\n201\n102\n2026-02-30\n2028-02-29\n25:00\n09:15\n0\n1.5\n10\n303\n2\n0\n')),require(rows('SELECT date,time,status FROM tutoring_sessions WHERE id=303')==[('2028-02-29','09:15','Rejected')])))
            check('Initialization is repeatable without changing IDs or records', lambda: (require(subprocess.run([args.php,'scripts/init_db.php',str(path)],capture_output=True).returncode==0),require('Learner' in console('2\n0\n')),require(rows('SELECT COUNT(*) FROM feedback')==[(1,)])))
            check('Fresh SQLite connection sees persisted feedback', lambda: require(rows('SELECT rating,comments FROM feedback WHERE session_id=301')==[(4,'Revised review')]))
            # Restart PHP, then verify through HTTP rather than only opening the file.
            server.terminate();server.wait(timeout=5)
            server=subprocess.Popen([args.php,'-d',f'session.save_path={directory}','-S',f'127.0.0.1:{port}','-t','dashboard'],env=environment,stdout=log,stderr=log)
            for _ in range(50):
                try:
                    require('Revised review' in get('feedback'));break
                except OSError:time.sleep(.1)
            else:raise RuntimeError('Restarted PHP server not ready')
            check('Restarted PHP serves persisted feedback', lambda: require('Revised review' in get('feedback')))
            check('Delete feedback, sessions, listings and students in dependency order', lambda: (post('feedback','delete',table='feedback',id=301),post('sessions','delete',table='tutoring_sessions',id=301),post('sessions','delete',table='tutoring_sessions',id=302),require('Record deleted' in console('17\n3\n303\n0\n')),post('skills','delete',table='skill_listings',id=201),post('skills','delete',table='skill_listings',id=202),post('students','delete',table='students',id=101),post('students','delete',table='students',id=102),require(rows('SELECT COUNT(*) FROM students')==[(0,)])))
            check('Foreign key integrity clean', lambda: require(rows('PRAGMA foreign_key_check')==[]))
        finally:
            server.terminate();server.wait(timeout=5);log.close()
            report=['# Executed test results','',f'Run: {datetime.now(timezone.utc).isoformat()}','',f'Environment: Windows; MinGW GCC 6.3.0; SQLite amalgamation 3.51.3; PHP executable `{args.php}`; Python standard library.','',f'Result: {sum(r[1]=="PASS" for r in RESULTS)} passed, {sum(r[1]=="FAIL" for r in RESULTS)} failed.','', '| Test | Result | Evidence |','| --- | --- | --- |']
            report += ['| '+ ' | '.join(part.replace('|','/').replace('\n',' ') for part in row)+' |' for row in RESULTS]
            report += ['', 'Reproduce: `python tests/integration.py --php C:/xampp/php/php.exe` after building the console. Tests create and remove an isolated temporary database under `build/`; the real project database is untouched.', '', 'Additional executed checks: milestone 1 compiled with `-std=c++11 -Wall -Wextra -pedantic`; numeric trailing text, blank name, duplicate ID and display smoke checks passed. Milestone 2 completed a console registration/listing/session/feedback workflow and restart; six direct constraint failures were rejected. Dashboard PHP syntax and HTTP GET checks passed for all six pages and both assets.', '', 'Not executed: interactive browser layout/accessibility review, JavaScript confirmation-dialog interaction, or multi-user load testing. No browser automation tool is available in this session. Responsive CSS and confirmation code were inspected; this is not a visual test pass.', '', 'Git push results are documented separately in the final handoff.']
            (ROOT/'TEST_CASES.md').write_text('\n'.join(report)+'\n',encoding='utf-8')


if __name__=='__main__':
    main()
