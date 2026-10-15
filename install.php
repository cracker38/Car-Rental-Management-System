<?php
declare(strict_types=1);

/**
 * Fresh-install helper. Does not drop existing tables.
 * Open in the browser once: http://localhost/CarrentalMS/install.php
 */

$root = __DIR__;
$cfg = require $root . '/config/database.php';
$messages = [];
$ok = true;

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], '', $cfg['port']);
if ($mysqli->connect_error) {
    $ok = false;
    $messages[] = 'Cannot connect to MySQL: ' . $mysqli->connect_error;
} else {
    $mysqli->set_charset('utf8mb4');
    $db = $mysqli->real_escape_string($cfg['name']);
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($cfg['name']);
    $sql = file_get_contents($root . '/database/carrental.sql');
    if ($sql === false) {
        $ok = false;
        $messages[] = 'Could not read database/carrental.sql';
    } else {
        if (!$mysqli->multi_query($sql)) {
            $ok = false;
            $messages[] = 'Schema import error: ' . $mysqli->error;
        } else {
            while ($mysqli->more_results()) {
                $mysqli->next_result();
            }
            $messages[] = 'Schema applied with CREATE TABLE IF NOT EXISTS / INSERT IGNORE (existing data kept).';
        }
    }

    $extras = [
        ['tax_rate_percent', '0', 'number', 'VAT/tax percent applied to (subtotal - discount). 0 until configured.'],
        ['turnaround_minutes', '0', 'number', 'Buffer added after a booking before the next pickup. 0 allows adjacent half-open periods.'],
        ['extra_km_rate', '0', 'number', 'RWF per km above included_km_per_day * billable days.'],
        ['included_km_per_day', '0', 'number', 'Kilometres included per billable day before extra mileage fees.'],
        ['late_fee_per_hour', '0', 'number', 'RWF per started hour after expected return.'],
        ['login_max_attempts', '5', 'number', 'Failed logins per email+IP before throttle.'],
        ['login_window_minutes', '15', 'number', 'Throttle window in minutes.'],
    ];
    $stmt = $mysqli->prepare('INSERT IGNORE INTO system_settings (setting_key, setting_value, value_type, description) VALUES (?,?,?,?)');
    foreach ($extras as $row) {
        $stmt->bind_param('ssss', $row[0], $row[1], $row[2], $row[3]);
        $stmt->execute();
    }
    $stmt->close();

    $adminEmail = 'admin@crms.local';
    $exists = $mysqli->query("SELECT user_id FROM users WHERE email = '" . $mysqli->real_escape_string($adminEmail) . "'");
    if ($exists && $exists->num_rows === 0) {
        $hash = password_hash('Admin@12345', PASSWORD_DEFAULT);
        $role = $mysqli->query("SELECT role_id FROM roles WHERE role_name = 'System Administrator'")->fetch_assoc();
        $ins = $mysqli->prepare('INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) VALUES (?,?,?,?,?,?,\'active\')');
        $fn = 'System';
        $ln = 'Administrator';
        $phone = '+250000000000';
        $ins->bind_param('isssss', $role['role_id'], $fn, $ln, $adminEmail, $phone, $hash);
        $ins->execute();
        $messages[] = 'Admin user created: admin@crms.local / Admin@12345 (change this password after first login).';
    } else {
        $messages[] = 'Admin user already exists; password was not changed.';
    }

    $cat = $mysqli->query('SELECT category_id FROM vehicle_categories WHERE category_name = \'Sedan\'');
    $catId = $cat && ($c = $cat->fetch_assoc()) ? (int) $c['category_id'] : 0;
    if ($catId && $mysqli->query('SELECT COUNT(*) c FROM vehicles')->fetch_assoc()['c'] == 0) {
        $adminId = $mysqli->query("SELECT user_id FROM users WHERE email = 'admin@crms.local'")->fetch_assoc()['user_id'] ?? null;
        $insv = $mysqli->prepare('INSERT INTO vehicles (category_id, registration_no, make, model, manufacture_year, colour, transmission, fuel_type, daily_rate, weekly_rate, monthly_rate, current_mileage, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,\'available\',?)');
        $samples = [
            [$catId, 'RAD-001-A', 'Toyota', 'Corolla', 2020, 'White', 'automatic', 'petrol', '35000', '210000', '700000', 12000, $adminId],
            [$catId, 'RAD-002-B', 'Honda', 'Civic', 2021, 'Silver', 'automatic', 'petrol', '38000', '228000', '760000', 8000, $adminId],
        ];
        foreach ($samples as $s) {
            $insv->bind_param('isssissssssii', $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], $s[11], $s[12]);
            $insv->execute();
        }
        $suv = $mysqli->query("SELECT category_id FROM vehicle_categories WHERE category_name='SUV'")->fetch_assoc();
        if ($suv) {
            $sid = (int) $suv['category_id'];
            $reg = 'RAD-100-S';
            $make = 'Toyota';
            $model = 'RAV4';
            $year = 2022;
            $colour = 'Black';
            $tr = 'automatic';
            $fuel = 'petrol';
            $d = '60000';
            $w = '360000';
            $m = '1200000';
            $km = 5000;
            $insv->bind_param('isssissssssii', $sid, $reg, $make, $model, $year, $colour, $tr, $fuel, $d, $w, $m, $km, $adminId);
            $insv->execute();
        }
        $messages[] = 'Sample vehicles added because the fleet was empty.';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CRMS install</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:720px">
  <h1 class="h3 mb-3">CRMS installer</h1>
  <p class="text-muted">Additive install only. Existing tables and rows are not dropped.</p>
  <?php foreach ($messages as $m): ?>
    <div class="alert <?= $ok ? 'alert-success' : 'alert-danger' ?>"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endforeach; ?>
  <?php if ($ok): ?>
    <a class="btn btn-primary" href="index.php">Open CRMS</a>
    <p class="mt-3 small">Delete or protect <code>install.php</code> after setup. Change the admin password immediately.</p>
  <?php endif; ?>
</div>
</body>
</html>
