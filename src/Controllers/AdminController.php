<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Settings;
use Crms\Validator;
use Crms\View;
use Crms\Services\BackupService;
use Crms\Services\SmsService;

final class AdminController
{
    public function users(): void
    {
        Rbac::require('users.manage');
        if (Http::isPost()) {
            if (Http::str('action') === 'create') {
                $v = new Validator();
                $v->require($_POST, ['first_name', 'last_name', 'email', 'password', 'role_id', 'status']);
                $v->email('email', Http::str('email'));
                $email = strtolower(trim(Http::str('email')));
                $firstName = trim(Http::str('first_name'));
                $lastName = trim(Http::str('last_name'));
                $phone = trim(Http::str('phone')) ?: null;
                $password = (string) ($_POST['password'] ?? '');
                $roleId = Http::int('role_id');
                $status = Http::str('status');
                if (strlen($password) < 8) {
                    $v->errors['password'] = 'Use at least 8 characters.';
                }
                if (!in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
                    $v->errors['status'] = 'Choose a valid account status.';
                }
                $role = $roleId > 0 ? Database::fetch('SELECT role_id, role_name FROM roles WHERE role_id = ?', [$roleId]) : null;
                if (!$role || $role['role_name'] === 'Customer') {
                    $v->errors['role_id'] = 'Choose a staff role. Customers should register on the website.';
                }
                if (Database::fetch('SELECT user_id FROM users WHERE email = ?', [$email])) {
                    $v->errors['email'] = 'That email is already registered.';
                }
                if (!$v->ok()) {
                    $errors = $v->errors;
                    Http::flash('danger', reset($errors));
                    Http::redirect('admin/users');
                }
                try {
                    Database::transaction(function () use ($roleId, $email, $firstName, $lastName, $phone, $status, $password): void {
                        Database::execute(
                            'INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) VALUES (?,?,?,?,?,?,?)',
                            [$roleId, $firstName, $lastName, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $status]
                        );
                        Audit::log('user.created', 'user', (string) Database::lastId(), $email);
                    });
                    Http::flash('success', 'User account created.');
                } catch (\Throwable) {
                    Http::flash('danger', 'Could not create the account. Check whether the email is already in use.');
                }
                Http::redirect('admin/users');
            }
            $action = Http::str('action');
            $userId = Http::int('user_id');
            $roleId = Http::int('role_id');
            $status = Http::str('status');
            $firstName = trim(Http::str('first_name'));
            $lastName = trim(Http::str('last_name'));
            $email = strtolower(trim(Http::str('email')));
            $phone = trim(Http::str('phone')) ?: null;
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $validation = new Validator();
            $validation->require($_POST, ['first_name', 'last_name', 'email', 'role_id', 'status']);
            $validation->email('email', $email);
            if ($userId < 1 || $roleId < 1 || !in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
                $validation->errors['user'] = 'Choose a valid user, role, and status.';
            }
            if (strlen($firstName) > 100 || strlen($lastName) > 100) {
                $validation->errors['name'] = 'Names must be 100 characters or fewer.';
            }
            if ($phone !== null && strlen($phone) > 30) {
                $validation->errors['phone'] = 'Phone numbers must be 30 characters or fewer.';
            }
            if ($newPassword !== '' && strlen($newPassword) < 8) {
                $validation->errors['new_password'] = 'A new password must contain at least 8 characters.';
            }
            if ($action !== 'update' || !$validation->ok()) {
                $errors = $validation->errors;
                Http::flash('danger', $errors ? reset($errors) : 'Choose a valid user update action.');
                Http::redirect('admin/users');
            }

            $error = null;
            try {
                Database::transaction(function () use ($userId, $roleId, $status, $firstName, $lastName, $email, $phone, $newPassword, &$error): void {
                    $target = Database::fetch(
                        'SELECT u.user_id, u.role_id, u.first_name, u.last_name, u.email, u.phone, u.status, r.role_name
                         FROM users u JOIN roles r ON r.role_id = u.role_id
                         WHERE u.user_id = ? FOR UPDATE',
                        [$userId]
                    );
                    $newRole = Database::fetch('SELECT role_id, role_name FROM roles WHERE role_id = ?', [$roleId]);
                    if (!$target || !$newRole) {
                        $error = 'Choose an existing user and role.';
                        return;
                    }
                    $duplicate = Database::fetch('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1', [$email, $userId]);
                    if ($duplicate) {
                        $error = 'That email address is already used by another account.';
                        return;
                    }
                    if ((int) $target['user_id'] === Auth::id()
                        && ((int) $target['role_id'] !== $roleId || $target['status'] !== $status)) {
                        $error = 'You can edit your own details, but cannot change your own role or account status.';
                        return;
                    }
                    if ($target['role_name'] === 'System Administrator' && $target['status'] === 'active'
                        && ($newRole['role_name'] !== 'System Administrator' || $status !== 'active')) {
                        $anotherAdmin = Database::fetch(
                            "SELECT user_id FROM users
                             WHERE role_id = ? AND status = 'active' AND user_id <> ?
                             LIMIT 1 FOR UPDATE",
                            [(int) $target['role_id'], $userId]
                        );
                        if (!$anotherAdmin) {
                            $error = 'At least one active System Administrator must remain.';
                            return;
                        }
                    }

                    $driver = Database::fetch('SELECT driver_id, status FROM drivers WHERE user_id = ? FOR UPDATE', [$userId]);
                    $disableDriver = $driver && ($newRole['role_name'] !== 'Driver' || $status !== 'active');
                    if ($disableDriver && $driver['status'] === 'active') {
                        $upcoming = Database::fetch(
                            "SELECT r.reservation_code FROM driver_assignments da
                             JOIN reservations r ON r.reservation_id = da.reservation_id
                             WHERE da.driver_id = ? AND r.status IN ('pending','confirmed') AND r.return_datetime > NOW()
                             LIMIT 1",
                            [(int) $driver['driver_id']]
                        );
                        if ($upcoming) {
                            $error = 'Reassign upcoming driver trips before deactivating or changing this linked Driver account.';
                            return;
                        }
                    }

                    $before = [
                        'first_name' => $target['first_name'], 'last_name' => $target['last_name'],
                        'email' => $target['email'], 'phone' => $target['phone'],
                        'role_id' => (int) $target['role_id'], 'role_name' => $target['role_name'], 'status' => $target['status'],
                    ];
                    if ($newPassword !== '') {
                        Database::execute(
                            'UPDATE users SET role_id=?,first_name=?,last_name=?,email=?,phone=?,status=?,password_hash=? WHERE user_id=?',
                            [$roleId, $firstName, $lastName, $email, $phone, $status, password_hash($newPassword, PASSWORD_DEFAULT), $userId]
                        );
                    } else {
                        Database::execute(
                            'UPDATE users SET role_id=?,first_name=?,last_name=?,email=?,phone=?,status=? WHERE user_id=?',
                            [$roleId, $firstName, $lastName, $email, $phone, $status, $userId]
                        );
                    }
                    if ($disableDriver && $driver['status'] !== 'inactive') {
                        Database::execute("UPDATE drivers SET status='inactive' WHERE driver_id=?", [(int) $driver['driver_id']]);
                    }
                    Audit::log('user.updated', 'user', (string) $userId, $email, $before, [
                        'first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'phone' => $phone,
                        'role_id' => $roleId, 'role_name' => $newRole['role_name'], 'status' => $status,
                        'password_changed' => $newPassword !== '',
                    ]);
                });
            } catch (\Throwable) {
                $error = 'Could not update the account. Check that the email is unique and the details are valid.';
            }
            if ($error !== null) {
                Http::flash('danger', $error);
            } else {
                Http::flash('success', 'User details and account status updated.');
            }
            Http::redirect('admin/users');
        }
        $q = trim(Http::str('q'));
        $userSql = 'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id';
        $userParams = [];
        if ($q !== '') {
            $userSql .= ' WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR r.role_name LIKE ?';
            $like = '%' . $q . '%';
            $userParams = [$like, $like, $like, $like];
        }
        $userSql .= ' ORDER BY u.created_at DESC';
        $users = Database::fetchAll($userSql, $userParams);
        $roles = Database::fetchAll('SELECT * FROM roles ORDER BY role_name');
        View::render('admin/users', ['title' => 'Users', 'users' => $users, 'roles' => $roles, 'q' => $q]);
    }

    public function roles(): void
    {
        Rbac::require('users.manage');
        $rows = Database::fetchAll(
            'SELECT r.role_name, r.description AS role_description, p.permission_key, p.description AS permission_description
             FROM roles r
             LEFT JOIN role_permissions rp ON rp.role_id = r.role_id
             LEFT JOIN permissions p ON p.permission_id = rp.permission_id
             ORDER BY r.role_name, p.permission_key'
        );
        $roles = [];
        foreach ($rows as $row) {
            $name = $row['role_name'];
            $roles[$name] ??= ['name' => $name, 'description' => $row['role_description'], 'permissions' => []];
            if ($row['permission_key'] !== null) {
                $roles[$name]['permissions'][] = [
                    'key' => $row['permission_key'],
                    'description' => $row['permission_description'],
                ];
            }
        }
        View::render('admin/roles', ['title' => 'Roles & Permissions', 'roles' => array_values($roles)]);
    }

    public function settings(): void
    {
        Rbac::require('settings.manage');
        if (Http::isPost()) {
            if (Http::str('_action') === 'test_sms') {
                $result = (new SmsService())->deliver(
                    Http::str('test_phone'),
                    Http::str('test_message') ?: 'Rent Car Rwanda SMS test from settings.'
                );
                Http::flash(
                    $result['status'] === 'sent' ? 'success' : 'danger',
                    $result['status'] === 'sent'
                        ? 'HDEV accepted the test SMS.'
                        : ('SMS was not sent: ' . ($result['error'] ?: $result['status']))
                );
                Http::redirect('admin/settings');
            }
            $keys = [
                'business_name', 'tax_rate_percent', 'default_deposit_amount', 'turnaround_minutes',
                'extra_km_rate', 'included_km_per_day', 'late_fee_per_hour',
                'hdev_callback_url', 'hdev_sms_sender', 'hdev_sms_enabled',
            ];
            foreach ($keys as $k) {
                if (isset($_POST[$k])) {
                    Settings::set($k, Http::str($k), Auth::id());
                }
            }
            Audit::log('settings.updated', 'system_settings', null, 'Settings saved');
            Http::flash('success', 'Settings saved. Pricing, SMS and availability use these values immediately.');
            Http::redirect('admin/settings');
        }
        foreach ([
            ['hdev_callback_url', '', 'string', 'Public HTTPS URL for hdev_callback.php'],
            ['hdev_sms_sender', 'CRMS', 'string', 'Approved HDEV SMS sender ID'],
            ['hdev_sms_enabled', 'true', 'boolean', 'Send customer SMS via HDEV when true'],
        ] as $row) {
            Database::execute(
                'INSERT IGNORE INTO system_settings (setting_key, setting_value, value_type, description) VALUES (?,?,?,?)',
                $row
            );
        }
        $rows = Database::fetchAll('SELECT * FROM system_settings ORDER BY setting_key');
        View::render('admin/settings', ['title' => 'Settings', 'rows' => $rows]);
    }

    public function audit(): void
    {
        Rbac::require('audit.view');
        $rows = Database::fetchAll(
            'SELECT a.*, u.email FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id
             ORDER BY a.created_at DESC LIMIT 300'
        );
        View::render('admin/audit', ['title' => 'Audit log', 'rows' => $rows]);
    }

    public function backup(): void
    {
        Rbac::require('settings.manage');
        $files = glob(dirname(__DIR__, 2) . '/storage/backups/*.sql') ?: [];
        rsort($files);
        if (Http::isPost() && Http::str('action') === 'export') {
            $path = (new BackupService())->exportSql();
            Http::flash('success', 'Backup written to ' . basename($path) . '. Restore has not been auto-tested in this request.');
            Http::redirect('admin/backup');
        }
        View::render('admin/backup', ['title' => 'Backups', 'files' => $files]);
    }

    public function reports(): void
    {
        if (!Rbac::can('reports.operational') && !Rbac::can('reports.financial')) {
            Rbac::require('reports.operational');
        }
        $dateFrom = Http::str('date_from', date('Y-m-01'));
        $dateTo = Http::str('date_to', date('Y-m-d'));
        foreach (['date_from' => $dateFrom, 'date_to' => $dateTo] as $key => $date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
                Http::flash('danger', 'Choose valid report start and end dates.');
                $dateFrom = date('Y-m-01');
                $dateTo = date('Y-m-d');
                break;
            }
        }
        if ($dateFrom > $dateTo) {
            Http::flash('danger', 'The report start date must be on or before the end date.');
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-d');
        }
        $ops = [];
        if (Rbac::can('reports.operational')) {
            $ops = Database::fetchAll(
                "SELECT v.registration_no, v.status,
                    (SELECT COUNT(*) FROM reservations r WHERE r.vehicle_id=v.vehicle_id AND r.status IN ('confirmed','completed') AND DATE(r.pickup_datetime) BETWEEN ? AND ?) AS confirmed_bookings,
                    (SELECT COUNT(*) FROM rentals rt JOIN reservations r ON r.reservation_id=rt.reservation_id
                     WHERE rt.vehicle_id=v.vehicle_id AND rt.status IN ('active','overdue','returned','inspected','closed')
                       AND DATE(COALESCE(rt.actual_pickup_datetime,r.pickup_datetime)) BETWEEN ? AND ?) AS live_rentals
                 FROM vehicles v ORDER BY v.registration_no",
                [$dateFrom, $dateTo, $dateFrom, $dateTo]
            );
        }
        $fin = [];
        if (Rbac::can('reports.financial')) {
            $fin = Database::fetch(
                "SELECT
                    COALESCE(SUM(CASE WHEN transaction_type='payment' AND status='completed' THEN amount END),0) AS payments,
                    COALESCE(SUM(CASE WHEN transaction_type='payment_refund' AND status='completed' THEN amount END),0) AS refunds,
                    COALESCE(SUM(CASE WHEN transaction_type='deposit_received' AND status='completed' THEN amount END),0) AS deposits,
                    COALESCE(SUM(CASE WHEN transaction_type='deposit_deduction' AND status='completed' THEN amount END),0) AS deposit_deductions,
                    COALESCE(SUM(CASE WHEN transaction_type='deposit_refund' AND status='completed' THEN amount END),0) AS deposit_refunds,
                    COALESCE(SUM(CASE WHEN transaction_type='adjustment' AND adjustment_direction='credit' AND status='completed' THEN amount END),0) AS adjustment_credits,
                    COALESCE(SUM(CASE WHEN transaction_type='adjustment' AND adjustment_direction='debit' AND status='completed' THEN amount END),0) AS adjustment_debits,
                    COALESCE(SUM(CASE WHEN status='awaiting_approval' THEN 1 ELSE 0 END),0) AS awaiting_approvals
                 FROM financial_transactions WHERE DATE(transaction_date) BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            );
        }
        if (Http::str('export') === 'csv') {
            $filename = 'crms-report-' . $dateFrom . '-' . $dateTo . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $out = fopen('php://output', 'w');
            if ($fin) {
                fputcsv($out, ['Financial summary', $dateFrom, $dateTo]);
                fputcsv($out, ['Completed payments', $fin['payments']]);
                fputcsv($out, ['Refunds', $fin['refunds']]);
                fputcsv($out, ['Deposits received', $fin['deposits']]);
                fputcsv($out, ['Deposit deductions', $fin['deposit_deductions']]);
                fputcsv($out, ['Deposit refunds', $fin['deposit_refunds']]);
                fputcsv($out, ['Invoice adjustment credits', $fin['adjustment_credits']]);
                fputcsv($out, ['Invoice adjustment debits', $fin['adjustment_debits']]);
                fputcsv($out, ['Requests awaiting approval', $fin['awaiting_approvals']]);
                fputcsv($out, []);
            }
            if ($ops) {
                fputcsv($out, ['Vehicle', 'Status', 'Confirmed bookings', 'Rentals']);
                foreach ($ops as $row) {
                    fputcsv($out, [$row['registration_no'], $row['status'], $row['confirmed_bookings'], $row['live_rentals']]);
                }
            }
            fclose($out);
            exit;
        }
        View::render('admin/reports', ['title' => 'Reports', 'ops' => $ops, 'fin' => $fin, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
    }
}
