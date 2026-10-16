<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Settings;
use Crms\View;
use Crms\Services\BackupService;
use Crms\Services\SmsService;

final class AdminController
{
    public function users(): void
    {
        Rbac::require('users.manage');
        $users = Database::fetchAll('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id ORDER BY u.created_at DESC');
        $roles = Database::fetchAll('SELECT * FROM roles ORDER BY role_name');
        if (Http::isPost()) {
            $status = Http::str('status');
            if (!in_array($status, ['active', 'inactive', 'suspended', 'pending'], true)) {
                Http::flash('danger', 'Invalid status.');
            } else {
                Database::execute('UPDATE users SET role_id = ?, status = ? WHERE user_id = ?', [Http::int('role_id'), $status, Http::int('user_id')]);
                Audit::log('user.updated', 'user', (string) Http::int('user_id'));
                Http::flash('success', 'User updated.');
            }
            Http::redirect('admin/users');
        }
        View::render('admin/users', ['title' => 'Users', 'users' => $users, 'roles' => $roles]);
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
                'extra_km_rate', 'included_km_per_day', 'late_fee_per_hour', 'reservation_confirmation_required',
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
                    COALESCE(SUM(CASE WHEN transaction_type='deposit_received' AND status='completed' THEN amount END),0) AS deposits
                 FROM financial_transactions WHERE DATE(transaction_date) BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            );
        }
        View::render('admin/reports', ['title' => 'Reports', 'ops' => $ops, 'fin' => $fin, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
    }
}
