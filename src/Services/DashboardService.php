<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Auth;
use Crms\Database;

final class DashboardService
{
    public static function templateFor(string $roleName): string
    {
        return match ($roleName) {
            'System Administrator' => 'dashboards/admin',
            'Business Manager' => 'dashboards/manager',
            'Rental Officer' => 'dashboards/officer',
            'Accountant/Cashier' => 'dashboards/cashier',
            'Vehicle Inspector/Maintenance Staff' => 'dashboards/inspector',
            'Customer' => 'dashboards/customer',
            default => 'dashboards/officer',
        };
    }

    public function data(array $user): array
    {
        $role = $user['role_name'];
        $notes = Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? OR customer_id = ? ORDER BY created_at DESC LIMIT 8',
            [Auth::id(), Auth::customerId()]
        );
        $base = ['user' => $user, 'notes' => $notes, 'role' => $role];
        return match ($role) {
            'System Administrator' => $base + $this->admin(),
            'Business Manager' => $base + $this->manager(),
            'Rental Officer' => $base + $this->officer(),
            'Accountant/Cashier' => $base + $this->cashier(),
            'Vehicle Inspector/Maintenance Staff' => $base + $this->inspector(),
            'Customer' => $base + $this->customer(),
            default => $base + $this->officer(),
        };
    }

    private function admin(): array
    {
        return [
            'kpis' => [
                ['label' => 'Users', 'value' => $this->count('SELECT COUNT(*) c FROM users'), 'hint' => 'All accounts'],
                ['label' => 'Fleet', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status <> 'inactive'"), 'hint' => 'Active vehicles'],
                ['label' => 'Open bookings', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status IN ('pending','confirmed')"), 'hint' => 'Pending + confirmed'],
                ['label' => 'Live rentals', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue')"), 'hint' => 'On the road'],
            ],
            'users' => Database::fetchAll('SELECT u.first_name, u.last_name, u.email, u.status, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id ORDER BY u.created_at DESC LIMIT 8'),
            'audit' => Database::fetchAll('SELECT action, entity_type, result, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 8'),
            'fleet' => Database::fetchAll("SELECT status, COUNT(*) c FROM vehicles GROUP BY status"),
        ];
    }

    private function manager(): array
    {
        $fin = Database::fetch(
            "SELECT COALESCE(SUM(CASE WHEN transaction_type='payment' AND status='completed' THEN amount END),0) payments,
                    COALESCE(SUM(CASE WHEN transaction_type='payment_refund' AND status='completed' THEN amount END),0) refunds
             FROM financial_transactions"
        );
        return [
            'kpis' => [
                ['label' => 'Fleet', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status <> 'inactive'"), 'hint' => 'Operational cars'],
                ['label' => 'Confirmed', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'confirmed'"), 'hint' => 'Upcoming hire'],
                ['label' => 'Overdue returns', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue') AND expected_return_datetime < NOW()"), 'hint' => 'Need follow-up'],
                ['label' => 'Outstanding', 'value' => \Crms\Money::format($this->sum("SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances")), 'hint' => 'Unpaid invoices'],
            ],
            'payments' => $fin['payments'] ?? 0,
            'refunds' => $fin['refunds'] ?? 0,
            'due' => Database::fetchAll(
                "SELECT rt.rental_number, rt.expected_return_datetime, rt.status, v.registration_no, c.first_name, c.last_name
                 FROM rentals rt JOIN vehicles v ON v.vehicle_id = rt.vehicle_id JOIN customers c ON c.customer_id = rt.customer_id
                 WHERE rt.status IN ('active','overdue','scheduled') ORDER BY rt.expected_return_datetime ASC LIMIT 10"
            ),
        ];
    }

    private function officer(): array
    {
        return [
            'kpis' => [
                ['label' => 'To confirm', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'pending'"), 'hint' => 'Awaiting confirmation'],
                ['label' => 'Pickups today', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'confirmed' AND DATE(pickup_datetime) = CURDATE()"), 'hint' => 'Confirmed today'],
                ['label' => 'Returns today', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue') AND DATE(expected_return_datetime) = CURDATE()"), 'hint' => 'Due back'],
                ['label' => 'Live hires', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue')"), 'hint' => 'Vehicles out'],
            ],
            'pending' => Database::fetchAll(
                "SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, r.return_datetime, r.status, c.first_name, c.last_name, v.registration_no
                 FROM reservations r JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
                 WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 8"
            ),
            'today' => Database::fetchAll(
                "SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, r.status, c.first_name, c.last_name, v.registration_no
                 FROM reservations r JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
                 WHERE r.status = 'confirmed' AND DATE(r.pickup_datetime) = CURDATE() ORDER BY r.pickup_datetime"
            ),
        ];
    }

    private function cashier(): array
    {
        return [
            'kpis' => [
                ['label' => 'Outstanding', 'value' => \Crms\Money::format($this->sum("SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances")), 'hint' => 'Open invoices'],
                ['label' => 'Unpaid invoices', 'value' => $this->count("SELECT COUNT(*) c FROM invoices WHERE status IN ('issued','partially_paid','overdue')"), 'hint' => 'Need collection'],
                ['label' => 'Pending MoMo', 'value' => $this->count("SELECT COUNT(*) c FROM financial_transactions WHERE status = 'pending' AND payment_method = 'mobile_money'"), 'hint' => 'Awaiting HDEV'],
                ['label' => 'Today receipts', 'value' => \Crms\Money::format($this->sum("SELECT COALESCE(SUM(amount),0) s FROM financial_transactions WHERE transaction_type='payment' AND status='completed' AND DATE(transaction_date)=CURDATE()")), 'hint' => 'Completed payments'],
            ],
            'invoices' => Database::fetchAll(
                "SELECT i.invoice_id, i.invoice_number, i.total_amount, i.status, b.outstanding_balance, c.first_name, c.last_name
                 FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                 WHERE i.status IN ('issued','partially_paid','overdue','draft')
                 ORDER BY i.issue_date DESC LIMIT 10"
            ),
            'pendingPay' => Database::fetchAll(
                "SELECT transaction_id, transaction_number, amount, external_reference, status, transaction_date
                 FROM financial_transactions WHERE status = 'pending' ORDER BY created_at DESC LIMIT 8"
            ),
        ];
    }

    private function inspector(): array
    {
        return [
            'kpis' => [
                ['label' => 'Awaiting inspection', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status = 'returned'"), 'hint' => 'Returned cars'],
                ['label' => 'Open maintenance', 'value' => $this->count("SELECT COUNT(*) c FROM maintenance_records WHERE status IN ('scheduled','in_progress')"), 'hint' => 'Blocking hire'],
                ['label' => 'In workshop', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status = 'maintenance'"), 'hint' => 'Vehicle status'],
                ['label' => 'Fleet checks due', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE inspection_expiry_date IS NOT NULL AND inspection_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) AND status <> 'inactive'"), 'hint' => 'Next 14 days'],
            ],
            'returns' => Database::fetchAll(
                "SELECT rt.rental_id, rt.rental_number, rt.actual_return_datetime, v.registration_no, v.make, v.model
                 FROM rentals rt JOIN vehicles v ON v.vehicle_id = rt.vehicle_id
                 WHERE rt.status = 'returned' ORDER BY rt.updated_at DESC LIMIT 10"
            ),
            'maint' => Database::fetchAll(
                "SELECT m.maintenance_id, m.maintenance_number, m.title, m.status, m.start_date, v.registration_no
                 FROM maintenance_records m JOIN vehicles v ON v.vehicle_id = m.vehicle_id
                 WHERE m.status IN ('scheduled','in_progress') ORDER BY m.start_date LIMIT 10"
            ),
        ];
    }

    private function customer(): array
    {
        $cid = Auth::customerId() ?: 0;
        return [
            'kpis' => [
                ['label' => 'My bookings', 'value' => $this->count('SELECT COUNT(*) c FROM reservations WHERE customer_id = ?', [$cid]), 'hint' => 'All reservations'],
                ['label' => 'Upcoming', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE customer_id = ? AND status IN ('pending','confirmed') AND return_datetime >= NOW()", [$cid]), 'hint' => 'Still active'],
                ['label' => 'Open invoices', 'value' => $this->count("SELECT COUNT(*) c FROM invoices WHERE customer_id = ? AND status IN ('issued','partially_paid','overdue')", [$cid]), 'hint' => 'To pay'],
                ['label' => 'To pay', 'value' => \Crms\Money::format($this->sum("SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances WHERE customer_id = ?", [$cid])), 'hint' => 'Outstanding RWF'],
            ],
            'reservations' => Database::fetchAll(
                'SELECT r.*, v.make, v.model, v.registration_no, v.image_path FROM reservations r
                 JOIN vehicles v ON v.vehicle_id = r.vehicle_id WHERE r.customer_id = ? ORDER BY r.created_at DESC LIMIT 8',
                [$cid]
            ),
            'invoices' => Database::fetchAll(
                'SELECT i.*, b.outstanding_balance FROM invoices i
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                 WHERE i.customer_id = ? ORDER BY i.created_at DESC LIMIT 8',
                [$cid]
            ),
            'fleet' => Database::fetchAll(
                "SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id
                 WHERE v.status NOT IN ('inactive','maintenance') ORDER BY v.daily_rate ASC LIMIT 6"
            ),
        ];
    }

    private function count(string $sql, array $params = []): int
    {
        $row = Database::fetch($sql, $params);
        return (int) ($row['c'] ?? 0);
    }

    private function sum(string $sql, array $params = []): float
    {
        $row = Database::fetch($sql, $params);
        return (float) ($row['s'] ?? 0);
    }
}
