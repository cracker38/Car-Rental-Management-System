<?php
declare(strict_types=1);

/**
 * Restrict existing Vehicle Inspector/Maintenance Staff accounts to their
 * vehicle, inspection, and maintenance permissions. Run from the project root:
 * php database/migrate_inspector_role.php
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
    $role = $db->query("SELECT role_id FROM roles WHERE role_name = 'Vehicle Inspector/Maintenance Staff' LIMIT 1")->fetch_assoc();
    if (!$role) {
        throw new RuntimeException('Vehicle Inspector/Maintenance Staff role is missing; apply the main schema first.');
    }

    $db->begin_transaction();
    $db->query("INSERT IGNORE INTO permissions (permission_key, description) VALUES
        ('vehicles.view','View vehicles'),
        ('maintenance.manage','Manage maintenance'),
        ('inspections.manage','Record vehicle inspections')");
    $allowed = $db->query("SELECT permission_id FROM permissions WHERE permission_key IN ('vehicles.view','maintenance.manage','inspections.manage')");
    if ($allowed->num_rows !== 3) {
        throw new RuntimeException('Required inspector permissions could not be resolved.');
    }
    $db->query("DELETE rp FROM role_permissions rp
        WHERE rp.role_id = " . (int) $role['role_id'] . "
          AND rp.permission_id NOT IN (
            SELECT permission_id FROM permissions WHERE permission_key IN ('vehicles.view','maintenance.manage','inspections.manage')
          )");
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT " . (int) $role['role_id'] . ", permission_id FROM permissions
        WHERE permission_key IN ('vehicles.view','maintenance.manage','inspections.manage')");
    $db->commit();
    $db->close();
    fwrite(STDOUT, "Vehicle Inspector/Maintenance Staff permissions synchronized.\n");
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
        $db->close();
    }
    fwrite(STDERR, 'Inspector role migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
