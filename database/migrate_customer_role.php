<?php
declare(strict_types=1);

/**
 * Keep Customer accounts limited to their own-facing vehicle, reservation,
 * rental, and invoice pages. Ownership checks scope each personal record.
 * Run from the project root: php database/migrate_customer_role.php
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
    $role = $db->query("SELECT role_id FROM roles WHERE role_name = 'Customer' LIMIT 1")->fetch_assoc();
    if (!$role) {
        throw new RuntimeException('Customer role is missing; apply the main schema first.');
    }

    $db->begin_transaction();
    $db->query("INSERT IGNORE INTO permissions (permission_key, description) VALUES
        ('vehicles.view','View vehicles'),
        ('reservations.view','View reservations'),
        ('rentals.view','View rental records'),
        ('invoices.view','View invoices')");
    $allowed = $db->query("SELECT permission_id FROM permissions WHERE permission_key IN
        ('vehicles.view','reservations.view','rentals.view','invoices.view')");
    if ($allowed->num_rows !== 4) {
        throw new RuntimeException('One or more Customer page permissions could not be resolved.');
    }
    $roleId = (int)$role['role_id'];
    $db->query("DELETE rp FROM role_permissions rp
        WHERE rp.role_id = {$roleId}
          AND rp.permission_id NOT IN (
            SELECT permission_id FROM permissions WHERE permission_key IN
              ('vehicles.view','reservations.view','rentals.view','invoices.view')
          )");
    $db->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT {$roleId}, permission_id FROM permissions WHERE permission_key IN
          ('vehicles.view','reservations.view','rentals.view','invoices.view')");
    $assigned = $db->query("SELECT COUNT(*) AS permission_count FROM role_permissions WHERE role_id = {$roleId}")->fetch_assoc();
    if ((int)$assigned['permission_count'] !== 4) {
        throw new RuntimeException('The Customer role still has permissions outside its assigned account pages.');
    }
    $db->commit();
    $db->close();
    fwrite(STDOUT, "Customer permissions synchronized.\n");
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
        $db->close();
    }
    fwrite(STDERR, 'Customer role migration failed: ' . $error->getMessage() . "\n");
    exit(1);
}
