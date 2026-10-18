<?php
declare(strict_types=1);

/**
 * Restrict existing Driver accounts to their own trip schedule and trip updates.
 * Run from the project root: php database/migrate_driver_role.php
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
    $role = $db->query("SELECT role_id FROM roles WHERE role_name = 'Driver' LIMIT 1")->fetch_assoc();
    if (!$role) {
        throw new RuntimeException('Driver role is missing; apply the main schema first.');
    }

    $db->begin_transaction();
    $db->query("INSERT IGNORE INTO permissions (permission_key, description)
        VALUES ('drivers.schedule','View assigned driver schedule and update your trips')");
    $allowed = $db->query("SELECT permission_id FROM permissions WHERE permission_key = 'drivers.schedule'");
    if ($allowed->num_rows !== 1) {
        throw new RuntimeException('The driver schedule permission could not be resolved.');
    }
    $roleId = (int)$role['role_id'];
    $db->query("DELETE rp FROM role_permissions rp
        WHERE rp.role_id = {$roleId}
          AND rp.permission_id NOT IN (
            SELECT permission_id FROM permissions WHERE permission_key = 'drivers.schedule'
          )");
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT {$roleId}, permission_id FROM permissions WHERE permission_key = 'drivers.schedule'");
    $assigned = $db->query("SELECT COUNT(*) AS permission_count FROM role_permissions WHERE role_id = {$roleId}")->fetch_assoc();
    if ((int)$assigned['permission_count'] !== 1) {
        throw new RuntimeException('The Driver role still has permissions outside its assigned trip schedule.');
    }
    $db->commit();
    $db->close();
    fwrite(STDOUT, "Driver permissions synchronized.\n");
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
        $db->close();
    }
    fwrite(STDERR, 'Driver role migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
