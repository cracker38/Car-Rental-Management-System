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
use Crms\Services\HdevClient;
use Crms\Services\ReportExportService;
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
                $driverLicense = strtoupper(trim(Http::str('driving_license_number')));
                $licenseExpiry = Http::str('license_expiry_date');
                if ($role && $role['role_name'] === 'Driver') {
                    if ($phone === null) {
                        $v->errors['phone'] = 'A phone number is required for Driver accounts.';
                    }
                    if ($driverLicense === '' || strlen($driverLicense) > 100) {
                        $v->errors['driving_license_number'] = 'Enter a driving licence number of 100 characters or fewer for this Driver account.';
                    }
                    if ($licenseExpiry !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $licenseExpiry)
                        || !checkdate((int) substr($licenseExpiry, 5, 2), (int) substr($licenseExpiry, 8, 2), (int) substr($licenseExpiry, 0, 4)))) {
                        $v->errors['license_expiry_date'] = 'Enter a valid driving licence expiry date.';
                    }
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
                    Database::transaction(function () use ($roleId, $role, $email, $firstName, $lastName, $phone, $status, $password, $driverLicense, $licenseExpiry): void {
                        Database::execute(
                            'INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, must_change_password, status) VALUES (?,?,?,?,?,?,1,?)',
                            [$roleId, $firstName, $lastName, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $status]
                        );
                        $userId = Database::lastId();
                        if ($role['role_name'] === 'Driver') {
                            Database::execute(
                                'INSERT INTO drivers (user_id,first_name,last_name,phone,email,driving_license_number,license_expiry_date,status,created_by)
                                 VALUES (?,?,?,?,?,?,?,?,?)',
                                [$userId, $firstName, $lastName, $phone, $email, $driverLicense, $licenseExpiry ?: null, $status === 'active' ? 'active' : 'inactive', Auth::id()]
                            );
                            Audit::log('driver.created', 'driver', (string) Database::lastId(), $firstName . ' ' . $lastName);
                        }
                        Audit::log('user.created', 'user', (string) $userId, $email);
                    });
                    Http::flash('success', 'User account created. The temporary password must be changed at first sign-in.');
                } catch (\Throwable) {
                    Http::flash('danger', 'Could not create the account. Check whether the email or driving licence number is already in use.');
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
                            'UPDATE users SET role_id=?,first_name=?,last_name=?,email=?,phone=?,status=?,password_hash=?,must_change_password=1 WHERE user_id=?',
                            [$roleId, $firstName, $lastName, $email, $phone, $status, password_hash($newPassword, PASSWORD_DEFAULT), $userId]
                        );
                    } else {
                        Database::execute(
                            'UPDATE users SET role_id=?,first_name=?,last_name=?,email=?,phone=?,status=? WHERE user_id=?',
                            [$roleId, $firstName, $lastName, $email, $phone, $status, $userId]
                        );
                    }
                    $customerProfile = Database::fetch('SELECT customer_id,phone FROM customers WHERE linked_user_id=? FOR UPDATE', [$userId]);
                    if ($customerProfile) {
                        Database::execute(
                            'UPDATE customers SET first_name=?,last_name=?,email=?,phone=? WHERE customer_id=?',
                            [$firstName, $lastName, $email, $phone ?? $customerProfile['phone'], (int) $customerProfile['customer_id']]
                        );
                    }
                    if ($driver) {
                        Database::execute(
                            'UPDATE drivers SET first_name=?,last_name=?,phone=?,email=? WHERE driver_id=?',
                            [$firstName, $lastName, $phone ?? $driver['phone'], $email, (int) $driver['driver_id']]
                        );
                    }
                    if ($disableDriver && $driver['status'] !== 'inactive') {
                        Database::execute("UPDATE drivers SET status='inactive' WHERE driver_id=?", [(int) $driver['driver_id']]);
                    } elseif ($driver && $newRole['role_name'] === 'Driver' && $status === 'active' && $driver['status'] === 'inactive') {
                        Database::execute("UPDATE drivers SET status='active' WHERE driver_id=?", [(int) $driver['driver_id']]);
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
                Http::flash('success', $newPassword !== ''
                    ? 'User details updated. The new temporary password must be changed at next sign-in.'
                    : 'User details and account status updated.');
            }
            Http::redirect('admin/users');
        }
        $q = trim(Http::str('q'));
        $q = function_exists('mb_substr') ? mb_substr($q, 0, 120) : substr($q, 0, 120);
        $userSql = 'SELECT u.*, r.role_name, d.driver_id FROM users u JOIN roles r ON r.role_id = u.role_id LEFT JOIN drivers d ON d.user_id = u.user_id';
        $userParams = [];
        if ($q !== '') {
            $userSql .= ' WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR r.role_name LIKE ?)';
            $like = '%' . $q . '%';
            $userParams = [$like, $like, $like, $like, $like];
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
        $hdev = HdevClient::config();
        $smsCredentialsConfigured = trim($hdev['sms_api_id']) !== '' && trim($hdev['sms_api_key']) !== '';
        View::render('admin/settings', [
            'title' => 'Settings',
            'rows' => $rows,
            'smsCredentialsConfigured' => $smsCredentialsConfigured,
        ]);
    }

    public function audit(): void
    {
        Rbac::require('audit.view');
        $perPage = 50;
        $total = (int) (Database::fetch('SELECT COUNT(*) AS total FROM audit_logs')['total'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, Http::int('page', 1)), $totalPages);
        $offset = ($page - 1) * $perPage;
        $rows = Database::fetchAll(
            'SELECT a.*, u.email FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id
             ORDER BY a.created_at DESC, a.audit_log_id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        View::render('admin/audit', [
            'title' => 'Audit log', 'rows' => $rows, 'page' => $page,
            'totalPages' => $totalPages, 'totalRows' => $total, 'perPage' => $perPage,
        ]);
    }

    public function backup(): void
    {
        Rbac::require('settings.manage');
        $files = glob(dirname(__DIR__, 2) . '/storage/backups/*.sql') ?: [];
        rsort($files);
        if (Http::isPost() && Http::str('action') === 'export') {
            try {
                $path = (new BackupService())->exportSql();
                Http::flash('success', 'Backup written to ' . basename($path) . '. Restore has not been tested in this request.');
            } catch (\Throwable) {
                Http::flash('danger', 'The backup could not be created. Check the database connection and storage/backups permissions.');
            }
            Http::redirect('admin/backup');
        }
        View::render('admin/backup', ['title' => 'Backups', 'files' => $files]);
    }

    public function reports(): void
    {
        Auth::requireLogin();
        $mode = report_mode();
        if ($mode === null) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            return;
        }
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-d');
        $dateFrom = Http::str('date_from', $defaultFrom);
        $dateTo = Http::str('date_to', $defaultTo);
        $validDate = static function (string $date): bool {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
                && checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4));
        };
        if (!$validDate($dateFrom) || !$validDate($dateTo)) {
            Http::flash('danger', 'Choose valid report start and end dates.');
            $dateFrom = $defaultFrom;
            $dateTo = $defaultTo;
        } elseif ($dateFrom > $dateTo) {
            Http::flash('danger', 'The report start date must be on or before the end date.');
            $dateFrom = $defaultFrom;
            $dateTo = $defaultTo;
        }
        $rangeStart = $dateFrom . ' 00:00:00';
        $rangeEnd = (new \DateTimeImmutable($dateTo))->modify('+1 day')->format('Y-m-d 00:00:00');
        $isFullOutput = Http::str('export') === 'csv' || Http::str('print') === '1';
        $pageSize = 50;
        $requestedPage = max(1, Http::int('page', 1));
        $reportPage = 1;
        $reportPages = 1;
        $reportTotalRows = 0;
        $setPage = static function (int $total) use (&$reportPage, &$reportPages, &$reportTotalRows, $requestedPage, $pageSize, $isFullOutput): int {
            $reportTotalRows = max(0, $total);
            $reportPages = max(1, (int) ceil($reportTotalRows / $pageSize));
            $reportPage = $isFullOutput ? 1 : min($requestedPage, $reportPages);
            return $isFullOutput ? 0 : ($reportPage - 1) * $pageSize;
        };
        $limitForPage = static fn(int $offset): string => $isFullOutput ? '' : ' LIMIT ' . $pageSize . ' OFFSET ' . $offset;
        $formatDateTime = static function (?string $value): string {
            return $value ? date('d M Y, H:i', strtotime($value)) : '-';
        };
        $formatDate = static function (?string $value): string {
            return $value ? date('d M Y', strtotime($value)) : '-';
        };
        $summary = [];
        $columns = [];
        $rows = [];
        $emptyMessage = 'No report activity was found for the selected period.';
        $financialDetails = [];
        $fin = null;
        if ($mode === 'company' || $mode === 'operational') {
            $operationalTotals = Database::fetch(
                "SELECT (SELECT COUNT(*) FROM vehicles) AS fleet_vehicles,
                        (SELECT COUNT(*) FROM reservations r
                         WHERE r.status IN ('confirmed','completed') AND r.pickup_datetime >= ? AND r.pickup_datetime < ?) AS confirmed_bookings,
                        (SELECT COUNT(*) FROM rentals rt JOIN reservations r ON r.reservation_id=rt.reservation_id
                         WHERE rt.status IN ('active','overdue','returned','inspected','closed')
                           AND COALESCE(rt.actual_pickup_datetime,r.pickup_datetime) >= ?
                           AND COALESCE(rt.actual_pickup_datetime,r.pickup_datetime) < ?) AS rental_activity",
                [$rangeStart, $rangeEnd, $rangeStart, $rangeEnd]
            ) ?? [];
            $vehicleCount = (int) ($operationalTotals['fleet_vehicles'] ?? 0);
            $offset = $setPage($vehicleCount);
            $vehicleRows = Database::fetchAll(
                "SELECT v.registration_no, v.status,
                    (SELECT COUNT(*) FROM reservations r WHERE r.vehicle_id=v.vehicle_id AND r.status IN ('confirmed','completed') AND r.pickup_datetime >= ? AND r.pickup_datetime < ?) AS confirmed_bookings,
                    (SELECT COUNT(*) FROM rentals rt JOIN reservations r ON r.reservation_id=rt.reservation_id
                     WHERE rt.vehicle_id=v.vehicle_id AND rt.status IN ('active','overdue','returned','inspected','closed')
                       AND COALESCE(rt.actual_pickup_datetime,r.pickup_datetime) >= ?
                       AND COALESCE(rt.actual_pickup_datetime,r.pickup_datetime) < ?) AS rental_activity
                 FROM vehicles v ORDER BY v.registration_no" . $limitForPage($offset),
                [$rangeStart, $rangeEnd, $rangeStart, $rangeEnd]
            );
            $operationalSummary = [
                ['label' => 'Fleet vehicles', 'value' => number_format($vehicleCount)],
                ['label' => 'Confirmed or completed bookings', 'value' => number_format((int) ($operationalTotals['confirmed_bookings'] ?? 0))],
                ['label' => 'Rental pickups', 'value' => number_format((int) ($operationalTotals['rental_activity'] ?? 0))],
            ];
            $summary = $operationalSummary;
            $columns = ['Vehicle', 'Current status', 'Bookings', 'Rental pickups'];
            $rows = array_map(static fn(array $row): array => [
                $row['registration_no'], ucwords(str_replace('_', ' ', (string) $row['status'])),
                number_format((int) $row['confirmed_bookings']), number_format((int) $row['rental_activity']),
            ], $vehicleRows);
            $emptyMessage = 'No vehicles are recorded.';
        }
        if ($mode === 'company' || $mode === 'financial') {
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
                 FROM financial_transactions WHERE transaction_date >= ? AND transaction_date < ?",
                [$rangeStart, $rangeEnd]
            );
            $netPayments = (float) $fin['payments'] - (float) $fin['refunds'];
            $netDeposits = (float) $fin['deposits'] - (float) $fin['deposit_deductions'] - (float) $fin['deposit_refunds'];
            $financialDetails = [
                ['Completed payments', \Crms\Money::format($fin['payments'])],
                ['Payment refunds', \Crms\Money::format($fin['refunds'])],
                ['Net payments', \Crms\Money::format($netPayments)],
                ['Deposits received', \Crms\Money::format($fin['deposits'])],
                ['Deposit deductions', \Crms\Money::format($fin['deposit_deductions'])],
                ['Deposit refunds', \Crms\Money::format($fin['deposit_refunds'])],
                ['Net deposit movement', \Crms\Money::format($netDeposits)],
                ['Invoice adjustment credits', \Crms\Money::format($fin['adjustment_credits'])],
                ['Invoice adjustment debits', \Crms\Money::format($fin['adjustment_debits'])],
                ['Approvals awaiting decision', number_format((int) $fin['awaiting_approvals'])],
            ];
            $financialSummary = [
                ['label' => 'Completed payments', 'value' => \Crms\Money::format($fin['payments'])],
                ['label' => 'Payment refunds', 'value' => \Crms\Money::format($fin['refunds'])],
                ['label' => 'Net payments', 'value' => \Crms\Money::format($netPayments)],
                ['label' => 'Net deposit movement', 'value' => \Crms\Money::format($netDeposits)],
                ['label' => 'Awaiting approvals', 'value' => number_format((int) $fin['awaiting_approvals'])],
            ];
            if ($mode === 'company') {
                $summary = array_merge($summary, $financialSummary);
            } else {
                $summary = $financialSummary;
                $columns = ['Financial activity', 'Amount'];
                $reportTotalRows = count($financialDetails);
                $rows = array_map(static fn(array $detail): array => [$detail[0], $detail[1]], $financialDetails);
                $reportPages = 1;
                $emptyMessage = 'No financial activity was found for the selected period.';
            }
        }
        if ($mode === 'maintenance') {
            $maintenanceSummary = Database::fetch(
                "SELECT COUNT(*) AS jobs,
                        SUM(status IN ('scheduled','in_progress')) AS open_jobs,
                        SUM(status='completed') AS completed_jobs,
                        COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN cost ELSE 0 END),0) AS recorded_cost
                 FROM maintenance_records WHERE start_date BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            ) ?? [];
            $summary = [
                ['label' => 'Maintenance jobs', 'value' => number_format((int) ($maintenanceSummary['jobs'] ?? 0))],
                ['label' => 'Open jobs', 'value' => number_format((int) ($maintenanceSummary['open_jobs'] ?? 0))],
                ['label' => 'Completed jobs', 'value' => number_format((int) ($maintenanceSummary['completed_jobs'] ?? 0))],
                ['label' => 'Recorded costs', 'value' => \Crms\Money::format($maintenanceSummary['recorded_cost'] ?? 0)],
            ];
            $maintenanceCount = Database::fetch('SELECT COUNT(*) AS total FROM maintenance_records WHERE start_date BETWEEN ? AND ?', [$dateFrom, $dateTo]);
            $offset = $setPage((int) ($maintenanceCount['total'] ?? 0));
            $maintenanceRows = Database::fetchAll(
                'SELECT m.maintenance_number, v.registration_no, m.maintenance_type, m.title, m.status,
                        m.start_date, m.expected_completion_date, m.completed_date
                 FROM maintenance_records m JOIN vehicles v ON v.vehicle_id=m.vehicle_id
                 WHERE m.start_date BETWEEN ? AND ? ORDER BY m.start_date DESC, m.maintenance_id DESC' . $limitForPage($offset),
                [$dateFrom, $dateTo]
            );
            $columns = ['Job', 'Vehicle', 'Type', 'Work', 'Status', 'Started', 'Expected completion', 'Completed'];
            $rows = array_map(static fn(array $row): array => [
                $row['maintenance_number'], $row['registration_no'], $row['maintenance_type'], $row['title'],
                ucwords(str_replace('_', ' ', (string) $row['status'])), $formatDate($row['start_date']),
                $formatDate($row['expected_completion_date']), $formatDate($row['completed_date']),
            ], $maintenanceRows);
            $emptyMessage = 'No maintenance jobs started in the selected period.';
        }
        if ($mode === 'customer') {
            $customerId = (int) Auth::customerId();
            $customerSummary = Database::fetch(
                "SELECT COUNT(*) AS reservations,
                        SUM(status='confirmed') AS approved,
                        SUM(status='rejected') AS rejected
                 FROM reservations WHERE customer_id=? AND created_at >= ? AND created_at < ?",
                [$customerId, $rangeStart, $rangeEnd]
            ) ?? [];
            $paidRow = Database::fetch(
                "SELECT COALESCE(SUM(CASE WHEN transaction_type='payment' AND status='completed' THEN amount
                                          WHEN transaction_type='payment_refund' AND status='completed' THEN -amount ELSE 0 END),0) AS amount
                 FROM financial_transactions
                 WHERE customer_id=? AND transaction_type IN ('payment','payment_refund') AND transaction_date >= ? AND transaction_date < ?",
                [$customerId, $rangeStart, $rangeEnd]
            ) ?? [];
            $summary = [
                ['label' => 'My reservations', 'value' => number_format((int) ($customerSummary['reservations'] ?? 0))],
                ['label' => 'Confirmed reservations', 'value' => number_format((int) ($customerSummary['approved'] ?? 0))],
                ['label' => 'Rejected reservations', 'value' => number_format((int) ($customerSummary['rejected'] ?? 0))],
                ['label' => 'Net payments', 'value' => \Crms\Money::format($paidRow['amount'] ?? 0)],
            ];
            $customerCount = Database::fetch(
                'SELECT COUNT(*) AS total FROM reservations WHERE customer_id=? AND created_at >= ? AND created_at < ?',
                [$customerId, $rangeStart, $rangeEnd]
            );
            $offset = $setPage((int) ($customerCount['total'] ?? 0));
            $customerRows = Database::fetchAll(
                'SELECT r.reservation_id, r.reservation_code, v.make, v.model, r.pickup_datetime, r.return_datetime, r.status, r.payment_status
                 FROM reservations r JOIN vehicles v ON v.vehicle_id=r.vehicle_id
                 WHERE r.customer_id=? AND r.created_at >= ? AND r.created_at < ?
                 ORDER BY r.created_at DESC, r.reservation_id DESC' . $limitForPage($offset),
                [$customerId, $rangeStart, $rangeEnd]
            );
            $columns = ['Reservation', 'Vehicle', 'Pickup', 'Return', 'Reservation status', 'Payment status'];
            $rows = array_map(static fn(array $row): array => [
                $row['reservation_code'], $row['make'] . ' ' . $row['model'],
                $formatDateTime($row['pickup_datetime']), $formatDateTime($row['return_datetime']),
                \Crms\Ui::reservationStatusLabel((string) $row['status'], (string) $row['payment_status']),
                ucwords(str_replace('_', ' ', (string) $row['payment_status'])), '_reservation_id' => (int) $row['reservation_id'],
            ], $customerRows);
            $emptyMessage = 'You have no reservations created during the selected period.';
        }
        if ($mode === 'driver') {
            $driver = Database::fetch('SELECT driver_id FROM drivers WHERE user_id=?', [Auth::id()]);
            $driverId = (int) ($driver['driver_id'] ?? 0);
            $driverSummary = $driverId ? (Database::fetch(
                "SELECT COUNT(*) AS trips,
                        COALESCE(SUM(da.trip_status='completed'),0) AS completed,
                        COALESCE(SUM(da.trip_status='delayed'),0) AS delayed_trips
                 FROM driver_assignments da JOIN reservations r ON r.reservation_id=da.reservation_id
                 WHERE da.driver_id=? AND r.status IN ('pending','confirmed','completed')
                   AND r.pickup_datetime >= ? AND r.pickup_datetime < ?",
                [$driverId, $rangeStart, $rangeEnd]
            ) ?? []) : [];
            $summary = [
                ['label' => 'Assigned trips', 'value' => number_format((int) ($driverSummary['trips'] ?? 0))],
                ['label' => 'Completed trips', 'value' => number_format((int) ($driverSummary['completed'] ?? 0))],
                ['label' => 'Delayed trips', 'value' => number_format((int) ($driverSummary['delayed_trips'] ?? 0))],
            ];
            $driverCount = $driverId ? Database::fetch(
                "SELECT COUNT(*) AS total FROM driver_assignments da JOIN reservations r ON r.reservation_id=da.reservation_id
                 WHERE da.driver_id=? AND r.status IN ('pending','confirmed','completed')
                   AND r.pickup_datetime >= ? AND r.pickup_datetime < ?",
                [$driverId, $rangeStart, $rangeEnd]
            ) : ['total' => 0];
            $offset = $setPage((int) ($driverCount['total'] ?? 0));
            $driverRows = $driverId ? Database::fetchAll(
                'SELECT r.reservation_code, r.pickup_datetime, r.return_datetime, r.status AS reservation_status,
                        da.trip_status, v.registration_no, v.make, v.model
                 FROM driver_assignments da JOIN reservations r ON r.reservation_id=da.reservation_id
                 JOIN vehicles v ON v.vehicle_id=r.vehicle_id
                 WHERE da.driver_id=? AND r.status IN ('pending','confirmed','completed')
                   AND r.pickup_datetime >= ? AND r.pickup_datetime < ?
                 ORDER BY r.pickup_datetime DESC, da.assignment_id DESC' . $limitForPage($offset),
                [$driverId, $rangeStart, $rangeEnd]
            ) : [];
            $columns = ['Reservation', 'Pickup', 'Return', 'Vehicle', 'Trip status', 'Reservation status'];
            $rows = array_map(static fn(array $row): array => [
                $row['reservation_code'], $formatDateTime($row['pickup_datetime']), $formatDateTime($row['return_datetime']),
                $row['registration_no'] . ' - ' . $row['make'] . ' ' . $row['model'],
                ucwords(str_replace('_', ' ', (string) $row['trip_status'])),
                ucwords(str_replace('_', ' ', (string) $row['reservation_status'])),
            ], $driverRows);
            $emptyMessage = $driverId ? 'No assigned trips were found for the selected period.' : 'Your driver profile is not linked to this account.';
        }
        if (Http::str('export') === 'csv') {
            $filename = 'crms-report-' . $dateFrom . '-' . $dateTo . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map([ReportExportService::class, 'csvSafeCell'], ['Report', $mode, $dateFrom, $dateTo]));
            if ($summary) {
                fputcsv($out, []);
                fputcsv($out, ['Summary', 'Value']);
                foreach ($summary as $item) {
                    fputcsv($out, array_map([ReportExportService::class, 'csvSafeCell'], [$item['label'], $item['value']]));
                }
            }
            if ($columns) {
                fputcsv($out, []);
                fputcsv($out, array_map([ReportExportService::class, 'csvSafeCell'], $columns));
                foreach ($rows as $row) {
                    fputcsv($out, array_map([ReportExportService::class, 'csvSafeCell'], array_slice($row, 0, count($columns))));
                }
            }
            if ($mode === 'company' && $financialDetails) {
                fputcsv($out, []);
                fputcsv($out, ['Financial detail', 'Amount']);
                foreach ($financialDetails as [$label, $amount]) {
                    fputcsv($out, array_map([ReportExportService::class, 'csvSafeCell'], [$label, $amount]));
                }
            }
            fclose($out);
            exit;
        }
        $titles = [
            'company' => 'Company reports', 'operational' => 'Fleet activity report', 'financial' => 'Financial reports',
            'maintenance' => 'Maintenance reports', 'customer' => 'My reservation report', 'driver' => 'My trip report',
        ];
        $modeLabels = [
            'company' => 'Company-wide', 'operational' => 'Fleet operations', 'financial' => 'Finance',
            'maintenance' => 'Maintenance', 'customer' => 'Personal', 'driver' => 'Driver activity',
        ];
        $reportPages = max(1, $reportPages);
        View::render('admin/reports', [
            'title' => $titles[$mode] ?? 'Reports', 'reportMode' => $mode, 'summary' => $summary,
            'columns' => $columns, 'rows' => $rows, 'emptyMessage' => $emptyMessage,
            'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'financialDetails' => $financialDetails,
            'reportModeLabel' => $modeLabels[$mode] ?? 'Reports',
            'reportPage' => $reportPage, 'reportPages' => $reportPages, 'reportTotalRows' => $reportTotalRows,
            'pageSize' => $pageSize, 'generatedAt' => date('d M Y, H:i'),
            'reportPrint' => true, 'autoPrintReport' => Http::str('print') === '1', 'isFullReportOutput' => $isFullOutput,
        ]);
    }
}
