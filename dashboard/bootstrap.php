<?php
declare(strict_types=1);
// Keep PHP session files writable and outside the public document root.
$sessionDirectory = getenv('SKILL_EXCHANGE_SESSION_DIR') ?: __DIR__ . '/../database/sessions';
if (!is_dir($sessionDirectory) && !mkdir($sessionDirectory, 0700, true) && !is_dir($sessionDirectory)) {
    throw new RuntimeException('Cannot create the local session directory.');
}
session_save_path($sessionDirectory);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
function h(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function db(): PDO {
    static $db;
    if (!$db) {
        $path = getenv('SKILL_EXCHANGE_DB') ?: __DIR__ . '/../database/skill_exchange.db';
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;');
        $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    }
    return $db;
}
function query(string $sql, array $values = []): array {
    $statement = db()->prepare($sql);
    $statement->execute($values);
    return $statement->fetchAll();
}
function text(string $field, int $max = 200): string {
    $value = trim((string)($_POST[$field] ?? ''));
    if ($value === '' || mb_strlen($value) > $max) throw new InvalidArgumentException("$field is required (maximum $max characters).");
    return $value;
}
function number(string $field): int {
    $value = filter_var($_POST[$field] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($value === false) throw new InvalidArgumentException("$field must be a whole number from 1 to 2147483647.");
    return $value;
}
function exists(string $table, string $column, int $id): void {
    if (!query("SELECT 1 FROM $table WHERE $column=?", [$id])) throw new InvalidArgumentException('Record not found.');
}
$pages = ['overview'=>'Overview', 'students'=>'Student profiles', 'skills'=>'Skill exchange', 'sessions'=>'Tutoring sessions', 'feedback'=>'Feedback', 'progress'=>'Learning progress'];
// Routes and table names come from fixed allowlists, never from raw SQL input.
$page = (string)($_GET['page'] ?? 'overview');
if (!isset($pages[$page])) $page = 'overview';
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
// Mutations always require POST plus a session CSRF token, then redirect.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
                $id=number('id'); exists('tutoring_sessions','id',$id);
                query('UPDATE tutoring_sessions SET status=? WHERE id=?',[text('status'),$id]); break;
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
        $_SESSION['flash']=['error',$message]; $_SESSION['old']=$_POST;
    } catch (InvalidArgumentException $e) { $_SESSION['flash']=['error',$e->getMessage()]; $_SESSION['old']=$_POST; }
    header('Location: ?page='.urlencode($page),true,303); exit;
}
$flash=$_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$old=$_SESSION['old'] ?? []; unset($_SESSION['old']);
function csrf(): void { echo '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function hidden(string $key, mixed $value): void { echo '<input type="hidden" name="'.h($key).'" value="'.h($value).'">'; }
function field(string $label,string $name,mixed $value='',string $type='text',bool $readonly=false): void {
    echo '<label>'.h($label).'<input name="'.h($name).'" type="'.h($type).'" value="'.h($value).'" required '.($readonly?'readonly ':'').($type==='number'?'min="1" ':'').'maxlength="'.(in_array($name,['skills_offered','skills_needed'])?1000:200).'"'.'></label>';
}
function area(string $label,string $name,mixed $value=''): void { echo '<label class="wide">'.h($label).'<textarea name="'.h($name).'" required maxlength="2000" rows="3">'.h($value).'</textarea></label>'; }
function select(string $label,string $name,array $options,mixed $selected=''): void {
    echo '<label>'.h($label).'<select name="'.h($name).'" required><option value="">Choose…</option>';
    foreach ($options as $id=>$title) echo '<option value="'.h($id).'" '.((string)$selected===(string)$id?'selected':'').'>'.h($title).'</option>';
    echo '</select></label>';
}
function deleteButton(string $table,int $id): void {
    echo '<form method="post" class="inline delete-form">'; csrf(); hidden('action','delete'); hidden('table',$table); hidden('id',$id); echo '<button class="danger small">Delete</button></form>';
}
function badge(string $value): string { return '<span class="badge '.h(strtolower($value)).'">'.h($value).'</span>'; }
function emptyRow(int $columns): void { echo '<tr><td colspan="'.$columns.'" class="empty">No records yet. Add your first record using the form above.</td></tr>'; }
$students=query('SELECT * FROM students ORDER BY id'); $studentOptions=[]; foreach ($students as $s) $studentOptions[$s['id']]=$s['name'].' · #'.$s['id'];
$edit=filter_var($_GET['edit'] ?? null,FILTER_VALIDATE_INT) ?: 0;
$q=trim((string)($_GET['q'] ?? '')); $type=(string)($_GET['type'] ?? '');
$record=$old;
if (!$record && $edit && in_array($page,['students','skills','feedback'])) {
    $table=['students'=>'students','skills'=>'skill_listings','feedback'=>'feedback'][$page]; $key=$page==='feedback'?'session_id':'id';
    $record=query("SELECT * FROM $table WHERE $key=?",[$edit])[0] ?? []; if ($record) $record['editing']='1';
}
$editing=($record['editing'] ?? '')==='1';

