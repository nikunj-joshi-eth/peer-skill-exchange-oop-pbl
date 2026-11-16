<?php
// Run from any directory: php scripts/init_db.php [database-path]
$path = $argv[1] ?? __DIR__ . '/../database/skill_exchange.db';
try {
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;');
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    echo "Database initialized without replacing existing records: $path\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
