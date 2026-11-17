<?php
declare(strict_types=1);
// Render infrastructure failures without exposing paths or SQL to the browser.
set_exception_handler(function (Throwable $error): void {
    error_log((string)$error);
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PeerLearn unavailable</title><h1>PeerLearn is temporarily unavailable</h1><p>Check database and session-directory permissions and run scripts/init_db.php if setup is incomplete. See the server log for details.</p><p><a href="?">Try again</a></p></html>';
});
// Keep PHP session files writable and outside the public document root.
$sessionDirectory = getenv('SKILL_EXCHANGE_SESSION_DIR') ?: __DIR__ . '/../database/sessions';
if (!is_dir($sessionDirectory) && !@mkdir($sessionDirectory, 0700, true) && !is_dir($sessionDirectory)) {
    throw new RuntimeException('Cannot create the local session directory.');
}
session_save_path($sessionDirectory);
if (!@session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict'])) {
    throw new RuntimeException('Cannot start the local session.');
}
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
function h(mixed $value): string {
    return htmlspecialchars(str_replace("\0", '', (string)$value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function db(): PDO {
    static $db;
    if (!$db) {
        $path = getenv('SKILL_EXCHANGE_DB') ?: __DIR__ . '/../database/skill_exchange.db';
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;');
        // Existing databases need only connection pragmas, not DDL on every request.
        $objects = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE name IN ('students','skill_listings','tutoring_sessions','feedback','learning_progress','session_insert','session_update','listing_update','feedback_insert','feedback_update','listing_owner','session_learner','session_tutor','session_listing')")->fetchColumn();
        if ((int)$objects !== 14) {
            $schema = @file_get_contents(__DIR__ . '/../database/schema.sql');
            if ($schema === false) throw new RuntimeException('Cannot read database schema.');
            $db->exec($schema);
        }
    }
    return $db;
}
function query(string $sql, array $values = []): array {
    $statement = db()->prepare($sql);
    $statement->execute($values);
    return $statement->fetchAll();
}
function fieldLabel(string $field): string {
    return ['id'=>'Record ID','student_id'=>'Student','listing_id'=>'Offered skill','learner_id'=>'Learner',
        'session_id'=>'Completed session','name'=>'Full name','skills_offered'=>'Skills offered',
        'skills_needed'=>'Skills to learn','skill_name'=>'Skill name','duration_hours'=>'Duration (hours)'][$field]
        ?? ucfirst(str_replace('_', ' ', $field));
}
function text(string $field, int $max = 200): string {
    $value = trim((string)($_POST[$field] ?? ''));
    if ($value === '' || mb_strlen($value) > $max) throw new InvalidArgumentException(fieldLabel($field)." is required (maximum $max characters).");
    return $value;
}
function number(string $field): int {
    $value = filter_var($_POST[$field] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($value === false) throw new InvalidArgumentException(fieldLabel($field)." must be a whole number from 1 to 2147483647.");
    return $value;
}
function exists(string $table, string $column, int $id): void {
    if (!query("SELECT 1 FROM $table WHERE $column=?", [$id])) throw new InvalidArgumentException('Record not found.');
}
$pages = ['overview'=>'Overview', 'students'=>'Student profiles', 'skills'=>'Skill exchange', 'sessions'=>'Tutoring sessions', 'feedback'=>'Feedback', 'progress'=>'Learning progress'];
// Routes and table names come from fixed allowlists, never from raw SQL input.
function requestText(string $key, string $default = ''): string {
    return is_string($_GET[$key] ?? null) ? $_GET[$key] : $default;
}
$page = requestText('page', 'overview');
if (!isset($pages[$page])) $page = 'overview';
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
// Mutations always require POST plus a session CSRF token, then redirect.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $value) {
            if (!is_string($value)) throw new InvalidArgumentException('Form fields must contain single values.');
            if (str_contains($value, "\0")) throw new InvalidArgumentException('NUL characters are not allowed in form fields.');
        }
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) throw new InvalidArgumentException('Form expired. Reload and try again.');
        $action = (string)($_POST['action'] ?? '');
        switch ($action) {
            case 'student_save':
                $id=number('id'); $values=[text('name'),text('skills_offered',1000),text('skills_needed',1000),$id];
                if (($_POST['editing'] ?? '') === '1') { exists('students','id',$id); query('UPDATE students SET name=?,skills_offered=?,skills_needed=? WHERE id=?',$values); }
                else query('INSERT INTO students(name,skills_offered,skills_needed,id) VALUES(?,?,?,?)',$values);
                break;
            case 'skill_save':
                $id=number('id'); $type=text('type');
                if (!in_array($type,['Offer','Learn'],true)) throw new InvalidArgumentException('Type must be Offer or Learn.');
                $values=[number('student_id'),text('skill_name'),$type,text('description',2000),$id];
                if (($_POST['editing'] ?? '') === '1') { exists('skill_listings','id',$id); query('UPDATE skill_listings SET student_id=?,skill_name=?,type=?,description=? WHERE id=?',$values); }
                else query('INSERT INTO skill_listings(student_id,skill_name,type,description,id) VALUES(?,?,?,?,?)',$values);
                break;
            case 'session_create':
                $date=text('date'); $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
                if (!$parsed || $parsed->format('Y-m-d')!==$date || (int)substr($date,0,4)<1900) throw new InvalidArgumentException('Enter a real date from 1900 onward.');
                $time=text('time'); if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$time)) throw new InvalidArgumentException('Time must be HH:MM.');
                $hours=filter_var($_POST['duration_hours'] ?? '',FILTER_VALIDATE_FLOAT);
                if ($hours===false || !is_finite($hours) || $hours<=0 || $hours>24) throw new InvalidArgumentException('Duration must be greater than 0 and at most 24 hours.');
                $listing=number('listing_id'); $offer=query("SELECT student_id FROM skill_listings WHERE id=? AND type='Offer'",[$listing]);
                if (!$offer) throw new InvalidArgumentException('Choose an existing Offer listing.');
                query('INSERT INTO tutoring_sessions VALUES(?,?,?,?,?,?,?,?)',[number('id'),$listing,number('learner_id'),$offer[0]['student_id'],$date,$time,$hours,'Pending']);
                break;
            case 'session_status':
                $id=number('id'); $status=text('status');
                $current=query('SELECT status FROM tutoring_sessions WHERE id=?',[$id]);
                if (!$current) throw new InvalidArgumentException('Session not found.');
                $allowed=['Pending'=>['Accepted','Rejected'], 'Accepted'=>['Completed']];
                if (!in_array($status,$allowed[$current[0]['status']] ?? [],true)) {
                    throw new InvalidArgumentException('Invalid status transition. Pending can be accepted or rejected; only accepted sessions can be completed.');
                }
                query('UPDATE tutoring_sessions SET status=? WHERE id=?',[$status,$id]); break;
            case 'feedback_save':
                $session=number('session_id'); $rating=number('rating');
                if ($rating>5) throw new InvalidArgumentException('Rating must be 1 to 5.');
                $sessions=query("SELECT learner_id FROM tutoring_sessions WHERE id=? AND status='Completed'",[$session]);
                if (!$sessions) throw new InvalidArgumentException('Feedback requires a completed session.');
                $values=[$rating,text('comments',2000),$session];
                if (($_POST['editing'] ?? '') === '1') { exists('feedback','session_id',$session); query('UPDATE feedback SET rating=?,comments=? WHERE session_id=?',$values); }
                else query('INSERT INTO feedback(session_id,student_id,rating,comments) VALUES(?,?,?,?)',[$session,$sessions[0]['learner_id'],$rating,$values[1]]);
                break;
            case 'delete':
                $tables=['students'=>'id','skill_listings'=>'id','tutoring_sessions'=>'id','feedback'=>'session_id'];
                $table=(string)($_POST['table'] ?? ''); if (!isset($tables[$table])) throw new InvalidArgumentException('Invalid record type.');
                $id=number('id'); exists($table,$tables[$table],$id); query("DELETE FROM $table WHERE {$tables[$table]}=?",[$id]); break;
            default: throw new InvalidArgumentException('Unknown action.');
        }
        $_SESSION['flash']=['success','Changes saved.'];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message='Database operation failed. Check the server log.';
        if (str_contains($e->getMessage(),'UNIQUE constraint')) $message='That ID or session feedback already exists.';
        elseif (str_contains($e->getMessage(),'FOREIGN KEY')) $message='A related record is missing, or this record is still in use. Remove dependent records first.';
        elseif (str_contains($e->getMessage(),'constraint') || str_contains($e->getMessage(),'SQLSTATE[23000]')) $message='The change violates a relationship, status, or field rule. Check the values and current session status.';
        $_SESSION['flash']=['error',$message]; $_SESSION['old']=array_filter($_POST, 'is_string');
    } catch (InvalidArgumentException $e) { $_SESSION['flash']=['error',$e->getMessage()]; $_SESSION['old']=array_filter($_POST, 'is_string'); }
    header('Location: ?page='.urlencode($page),true,303); exit;
}
$flash=$_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$old=$_SESSION['old'] ?? [];
// Status/delete forms must not populate an unrelated create form.
if (!in_array($old['action'] ?? '', ['student_save','skill_save','session_create','feedback_save'], true)) $old=[];
unset($_SESSION['old']);
function csrf(): void { echo '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function hidden(string $key, mixed $value): void { echo '<input type="hidden" name="'.h($key).'" value="'.h($value).'">'; }
function field(string $label,string $name,mixed $value='',string $type='text',bool $readonly=false): void {
    echo '<label>'.h($label).'<input name="'.h($name).'" type="'.h($type).'" value="'.h($value).'" required '.($readonly?'readonly ':'').($type==='number'?'min="1" max="2147483647" step="1" ':($type==='date'?'min="1900-01-01" max="9999-12-31" ':'')).'maxlength="'.(in_array($name,['skills_offered','skills_needed'])?1000:200).'"'.'></label>';
}
function area(string $label,string $name,mixed $value=''): void { echo '<label class="wide">'.h($label).'<textarea name="'.h($name).'" required maxlength="2000" rows="3">'.h($value).'</textarea></label>'; }
function select(string $label,string $name,array $options,mixed $selected=''): void {
    echo '<label>'.h($label).'<select name="'.h($name).'" required'.(!$options?' disabled':'').'><option value="">Choose…</option>';
    foreach ($options as $id=>$title) echo '<option value="'.h($id).'" '.((string)$selected===(string)$id?'selected':'').'>'.h($title).'</option>';
    echo '</select></label>';
}
function deleteButton(string $table,int $id): void {
    $label=['students'=>'student','skill_listings'=>'listing','tutoring_sessions'=>'session','feedback'=>'feedback for session'][$table].' #'.$id;
    echo '<form method="post" class="inline delete-form" data-record="'.h($label).'">'; csrf(); hidden('action','delete'); hidden('table',$table); hidden('id',$id); echo '<button class="danger small" aria-label="Delete '.h($label).'">Delete</button></form>';
}
function badge(string $value): string { return '<span class="badge '.h(strtolower($value)).'">'.h($value).'</span>'; }
function emptyRow(int $columns): void {
    global $page, $q, $type;
    $message = (in_array($page, ['students','skills'], true) && ($q !== '' || $type !== '')) ? 'No matching records. Clear the search or filter to see all records.' : match ($page) {
        'overview', 'sessions' => 'No sessions yet. Register two students and an Offer listing, then schedule a session.',
        'feedback' => 'No feedback yet. Complete a tutoring session before adding feedback.',
        'progress' => ($columns === 3 ? 'No students yet. Register students to track learning hours.' : 'No completed sessions yet. Complete a tutoring session to record learning hours.'),
        default => 'No records yet. Add your first record using the form above.'
    };
    echo '<tr><td colspan="'.$columns.'" class="empty">'.h($message).'</td></tr>';
}
$students=query('SELECT * FROM students ORDER BY id'); $studentOptions=[]; foreach ($students as $s) $studentOptions[$s['id']]=$s['name'].' · #'.$s['id'];
$edit=filter_var(requestText('edit'),FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>2147483647]]) ?: 0;
$q=trim(requestText('q')); $type=requestText('type');
$record=$old;
if (!$record && $edit && in_array($page,['students','skills','feedback'])) {
    $table=['students'=>'students','skills'=>'skill_listings','feedback'=>'feedback'][$page]; $key=$page==='feedback'?'session_id':'id';
    $record=query("SELECT * FROM $table WHERE $key=?",[$edit])[0] ?? []; if ($record) $record['editing']='1';
    else $flash=['error','The record to edit no longer exists.'];
}
$editing=($record['editing'] ?? '')==='1';

