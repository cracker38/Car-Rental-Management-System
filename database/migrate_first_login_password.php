<?php
declare(strict_types=1);

/**
 * Add the first-login password-change flag to existing installations.
 * Run with XAMPP PHP from the project root: php database/migrate_first_login_password.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$cfg = require dirname(__DIR__) . '/config/database.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);
    $db->set_charset('utf8mb4');

    $table = $db->query("SHOW TABLES LIKE 'users'");
    if ($table->num_rows === 0) {
        throw new RuntimeException('The users table is missing; apply the main schema first.');
    }

    $columns = [];
    $result = $db->query('SHOW COLUMNS FROM users');
    while ($column = $result->fetch_assoc()) {
        $columns[$column['Field']] = true;
    }
    if (!isset($columns['must_change_password'])) {
        $db->query('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash');
    }

    $db->close();
    fwrite(STDOUT, "First-login password-change migration applied.\n");
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    fwrite(STDERR, 'First-login password-change migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
