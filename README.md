# Peer-to-Peer Skill Exchange and Tutoring Platform

A second-year C++ OOP PBL project supporting **SDG 4: Quality Education**. Students register their profiles, offer or request skills, schedule peer tutoring, and track completed learning with feedback. **PeerLearn** is the local web dashboard for the same platform.

The Phase 2 console source was reconstructed from earlier documented module prototypes; it is not a recovered original integrated source. Its four identifiable OOP classes are retained. Both interfaces read and write **one SQLite database**: `database/skill_exchange.db`.

## Team

| Member | University ID | Module responsibility |
| --- | --- | --- |
| Nikunj Joshi | 25/SET/BCSE/137 | Student Profile Management |
| Khushi | 25/SET/BCSE/148 | Skill Listings |
| Mansi | 25/SET/BCSE/131 | Tutoring Session Management |
| Sumit Kumar | 25/SET/BCSE/145 | Feedback and Learning Progress |

See [team responsibilities](docs/TEAM_CONTRIBUTIONS.md). These are assigned module responsibilities, not claims of individual Git authorship.

## Prerequisites

- Windows PowerShell, Git, and MinGW **GCC and G++** in `PATH`. Tested with GCC/G++ 6.3.0 (32-bit), using C++11.
- PHP 8.0 or newer with `pdo_sqlite`, `PDO`, and `mbstring`. Tested with XAMPP PHP 8.2.12 at `C:\xampp\php\php.exe`. Apache and MySQL are not needed.
- Internet access for the first SQLite source download. The build pins SQLite **3.51.3** and compiles its official C amalgamation; no SQLite CLI, database server, or separately installed SQLite library is required.
- Python 3 for the optional integration test suite (standard library only).

## Build and initialize (Windows PowerShell)

Run these commands from the repository root:

```powershell
# Verify prerequisites
Get-Command gcc, g++
& 'C:\xampp\php\php.exe' -m

# Download the pinned SQLite C source and compile
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1 -FetchSQLite

# Create tables, indexes, triggers and the progress view; keeps existing records
& 'C:\xampp\php\php.exe' .\scripts\init_db.php

# Start the console
.\build\skill_exchange.exe
```

If `php` is already in `PATH`, replace `& 'C:\xampp\php\php.exe'` with `php`. If the extension is missing, enable `extension=pdo_sqlite` in the loaded `php.ini` (find it with `php --ini`) and restart PHP. Enable `mbstring` too if needed. The database directory must be writable. PHP session files are created automatically in ignored `database/sessions/`, outside the public directory; no XAMPP session-directory configuration is needed.

For subsequent builds, omit `-FetchSQLite`. To prepare offline, extract the official `sqlite-amalgamation-3510300.zip` into `dependencies/` so that `dependencies/sqlite-amalgamation-3510300/sqlite3.c` and `sqlite3.h` exist.

Equivalent manual build commands after obtaining that source:

```powershell
New-Item -ItemType Directory -Force build | Out-Null
gcc -O2 -DSQLITE_THREADSAFE=0 -c dependencies/sqlite-amalgamation-3510300/sqlite3.c -o build/sqlite3.o
g++ -std=c++11 -Wall -Wextra -pedantic -I dependencies/sqlite-amalgamation-3510300 cpp/main.cpp build/sqlite3.o -o build/skill_exchange.exe
```

`SQLITE_THREADSAFE=0` is appropriate for the single-threaded console. SQLite still coordinates separate C++ and PHP processes through file locks. Both clients enable foreign keys and wait up to five seconds for a busy database.

## Run the web dashboard

In another PowerShell window, from the repository root:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t dashboard
```

Open **http://127.0.0.1:8080**. Stop the server with `Ctrl+C`. Serve **only `dashboard/`**, which keeps source, test reports, and database files outside the public document root. This is a local university demo with no login or role checks; every local user can manage all records.

The default C++ and PHP paths resolve to the same file when the console runs from the repository root. Console changes appear on the next dashboard load. Dashboard changes appear on the next console menu cycle. A form already open in either interface may contain old values; reload after using the other interface.

For a separate test database, select the **same absolute path in both clients**:

```powershell
$env:SKILL_EXCHANGE_DB = Join-Path (Get-Location) 'database\demo.db'
& 'C:\xampp\php\php.exe' .\scripts\init_db.php $env:SKILL_EXCHANGE_DB
.\build\skill_exchange.exe $env:SKILL_EXCHANGE_DB
# Start this in a second shell with the same SKILL_EXCHANGE_DB value:
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t dashboard
```

The PHP environment override is optional; C++ uses its first command-line argument as the override. Both applications check the shared schema objects and initialize only when objects are missing; ordinary dashboard requests do not execute schema DDL. Use the initializer for an explicit, reproducible setup.

## Workflow and rules

1. Register a tutor and a different learner, using positive IDs up to **2147483647** (the C++ signed integer range).
2. Create an **Offer** skill listing for the tutor. **Learn** listings describe skills wanted and cannot be booked.
3. Schedule a session with an existing learner, a real `YYYY-MM-DD` date, `HH:MM` time, and a duration greater than zero and at most 24 hours. Past dates are allowed for recording university demonstrations; overlap detection is outside this scope.
4. Move a session from **Pending → Accepted or Rejected**, then **Accepted → Completed**. Completed and rejected sessions are terminal. Booking details stay fixed; delete an unreviewed session and schedule again to correct them.
5. Submit one feedback entry per completed session, attributed to its learner, with an integer rating from **1 to 5** and nonempty comments. Feedback can be edited or deleted through the dashboard.
6. Progress counts **completed sessions and their scheduled duration**, even without feedback. Dashboard tutor averages and overall review averages use feedback ratings. The console learner average describes the ratings that learner submitted.

Profiles and listings can be created, read, edited, and deleted in both interfaces. Console options 1–14 preserve the original workflow; 15–17 add profile editing, listing editing, and deletion. The dashboard also offers name/ID/skill search, listing filters, feedback editing, and tutor ratings.

Foreign keys restrict deletion of referenced records. To remove a whole chain, delete feedback first, then sessions, listings, and students. A booked listing's owner and type cannot change. Session durations are the source for learning hours; users do not enter a second conflicting hours value with feedback.

## Architecture and data preservation

- **C++:** `StudentProfile`, `SkillListing`, `TutoringSession`, and `FeedbackProgress` encapsulate module data, constructors, input, display, and persistence member functions. Small menu functions coordinate them. `Database` owns the SQLite connection and finalizes prepared statements. Vectors replace the old 50-record arrays and refresh from disk each menu cycle.
- **Database:** four related tables, unique IDs, foreign keys, field checks, and triggers enforce rules across both interfaces. `learning_progress` is a SQL view derived from completed sessions; it does not duplicate progress records.
- **Web:** PHP/PDO processes server-validated forms through prepared statements. POST/redirect/GET avoids accidental resubmission. Session CSRF tokens, escaped output, a restrictive content policy, and delete confirmation support safe local form handling. HTML, custom CSS and a small vanilla JavaScript file provide the UI.

Initialization uses `CREATE ... IF NOT EXISTS`; it never drops tables, renumbers IDs, or inserts sample users. The reconstructed Phase 2 baseline stored data only in memory and included no persisted records to migrate. Existing databases from this schema are retained. The initializer is **not a general migration tool** for unrelated or older table layouts. Back up a database before future schema changes; close both clients before copying `database/skill_exchange.db`. Local data, binaries, downloaded dependencies and secrets are ignored by Git.

## Project structure

```text
cpp/main.cpp                 Reconstructed Phase 2 four OOP modules and console menu
cpp/database.h               SQLite connection and prepared-statement helper
database/schema.sql         Shared tables, constraints, indexes, triggers and view
dashboard/index.php         Six dashboard page templates
dashboard/bootstrap.php     PDO, validation, form actions and rendering helpers
dashboard/assets/style.css  Responsive dashboard styles
dashboard/assets/app.js     Delete confirmation
scripts/build.ps1           Pinned SQLite download and Windows build
scripts/init_db.php         Additive database initialization
tests/integration.py        Isolated console / HTTP / SQLite integration suite
docs/TEAM_CONTRIBUTIONS.md   Team responsibilities and module map
docs/ARCHITECTURE.md         Components, request lifecycles and shared storage
docs/OOP_CONCEPTS.md         Implemented classes and actual syllabus concepts
docs/DATABASE_DESIGN.md      Keys, relationships, rules and derived progress
docs/TIME_COMPLEXITY.md      In-memory and database operation costs
docs/DEMONSTRATION_GUIDE.md  Repeatable viva and screenshot checklist
docs/LIMITATIONS.md          Supported scope and possible future work
TEST_CASES.md               Results of tests actually executed
```

## Verification

```powershell
& 'C:\xampp\php\php.exe' -l dashboard/bootstrap.php
& 'C:\xampp\php\php.exe' -l dashboard/index.php
& 'C:\xampp\php\php.exe' -l scripts/init_db.php
python tests/integration.py --php 'C:\xampp\php\php.exe'
```

The integration suite starts its own PHP server on an available loopback port and gives PHP and C++ one temporary database under `build/`. It checks both directions of integration, restart persistence, editing/deletion, progress, invalid inputs, relationship/status rules, duplicate feedback, escaping, CSRF and foreign-key integrity. It removes test data and rewrites `TEST_CASES.md` with actual results. It does not touch the normal database.

See [TEST_CASES.md](TEST_CASES.md) for actual executed results. HTTP checks do not establish rendered layout, screen-reader behavior or JavaScript interaction. Browser visual review, confirmation interaction, and load testing remain unverified.

## Academic documentation and viva

Read the [architecture](docs/ARCHITECTURE.md), [actual OOP concepts](docs/OOP_CONCEPTS.md), [database design](docs/DATABASE_DESIGN.md), and [operation-wise complexity](docs/TIME_COMPLEXITY.md). The [demonstration guide](docs/DEMONSTRATION_GUIDE.md) provides one complete workflow using a newly named demonstration database, expected outcomes, rejection cases and an unchecked screenshot checklist. The [team map](docs/TEAM_CONTRIBUTIONS.md) preserves assigned responsibilities without attributing particular Git commits to individual students.

Current [limitations and future scope](docs/LIMITATIONS.md) include local access without roles, no overlap detection or timezone handling, unpaginated lists, snapshot refresh rather than live updates, and additive setup rather than database migrations. This finalization adds no authentication, external framework or unrelated feature.

The mandatory university DOCX template has been supplied and a separate template-based report draft exists outside this source repository. Finalize it with actual screenshots and confirmed faculty details before submission. Release notes are in RELEASE_NOTES.md; complete the manual Windows demonstration and publish the verified release through GitHub. No fabricated screenshots are included.

## Troubleshooting

| Symptom | Action |
| --- | --- |
| `gcc` or `g++` not found | Install MinGW GCC/G++, add its `bin` directory to PATH, open a new PowerShell window, and run `Get-Command gcc, g++`. Both compilers are needed. |
| SQLite source missing or download failed | Run `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\build.ps1 -FetchSQLite` with internet access. For offline setup, use the exact extraction path documented above. |
| `could not find driver` | Run `& 'C:\xampp\php\php.exe' --ini`, enable `extension=pdo_sqlite` in that loaded PHP configuration, and restart PHP. Verify with `-m`. |
| `mb_strlen` unavailable | Enable `extension=mbstring` in the loaded PHP configuration and restart PHP. |
| Database cannot be opened / dashboard HTTP 503 | Run from the repository root, ensure the selected database's parent directory exists and is writable, and inspect the PHP server log. Run the initializer on the same path. Session storage also needs write access. |
| Console and dashboard show different records | Pass the same absolute database path to C++ and PHP; restart PHP after changing its environment. Advance a console menu cycle and reload the dashboard. |
| Port 8080 in use | Stop the existing server or start PHP on `127.0.0.1:8081`, then browse that port. |
| Database locked | Finish the other write, close external SQLite tools/transactions and retry. Avoid simultaneous cloud-sync access to an active database. Both clients wait up to five seconds. |
| Deletion or status change rejected | Follow the lifecycle and dependency order above. Do not disable foreign keys or triggers to make a demonstration succeed. |
| Console text appears garbled | Use an appropriate terminal encoding; `chcp 65001` may help UTF-8 input/output. ASCII demonstration names avoid code-page differences. Unicode search remains limited. |

A fresh source checkout contains no binaries, database or downloaded dependencies. The `-FetchSQLite` build creates `dependencies/` and `build/`; the initializer creates the database without sample records. Never copy an old binary into a submission as a substitute for a successful source build.
