<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Auth;
use Crms\Database;
use Crms\Money;

final class DashboardService
{
    public static function templateFor(string $roleName): string
    {
        return match ($roleName) {
            'System Administrator' => 'dashboards/admin',
            'Business Manager' => 'dashboards/manager',
            'Rental Officer' => 'dashboards/rental-officer',
            'Accountant/Cashier' => 'dashboards/accountant',
            'Vehicle Inspector/Maintenance Staff' => 'dashboards/inspector',
            'Customer' => 'dashboards/customer',
            'Driver' => 'dashboards/driver',
            default => 'dashboards/index',
        };
    }

    public static function pageTitle(string $roleName): string
    {
        return match ($roleName) {
            'System Administrator' => 'Control center',
            'Business Manager' => 'Operations overview',
            'Rental Officer' => 'Front desk',
            'Accountant/Cashier' => 'Finance desk',
            'Vehicle Inspector/Maintenance Staff' => 'Workshop',
            'Customer' => 'My account',
            'Driver' => 'My trips',
            default => 'Dashboard',
        };
    }

    public function data(array $user): array
    {
        $role = (string) ($user['role_name'] ?? '');
        $notes = Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? OR customer_id = ? ORDER BY created_at DESC LIMIT 8',
            [Auth::id(), Auth::customerId()]
        );
        $base = [
            'user' => $user,
            'notes' => $notes,
            'role' => $role,
            'shortcuts' => $this->shortcuts($role),
        ];
        return match ($role) {
            'System Administrator' => $base + $this->admin(),
            'Business Manager' => $base + $this->manager(),
            'Rental Officer' => $base + $this->officer(),
            'Accountant/Cashier' => $base + $this->cashier(),
            'Vehicle Inspector/Maintenance Staff' => $base + $this->inspector(),
            'Customer' => $base + $this->customer(),
            'Driver' => $base + $this->driver(),
            default => $base + ['kpis' => []],
        };
    }

    private function shortcuts(string $role): array
    {
        return match ($role) {
            'System Administrator' => [
                ['route' => 'admin/users', 'icon' => 'bi-people', 'label' => 'Users & roles', 'hint' => 'Accounts and permissions'],
                ['route' => 'admin/settings', 'icon' => 'bi-sliders', 'label' => 'Settings', 'hint' => 'Pricing, SMS and HDEV'],
                ['route' => 'admin/audit', 'icon' => 'bi-shield-check', 'label' => 'Audit log', 'hint' => 'Security activity'],
                ['route' => 'admin/backup', 'icon' => 'bi-hdd', 'label' => 'Backups', 'hint' => 'SQL exports'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Fleet', 'hint' => 'Photos, rates, status'],
                ['route' => 'reports', 'icon' => 'bi-graph-up', 'label' => 'Reports', 'hint' => 'Ops and finance'],
            ],
            'Business Manager' => [
                ['route' => 'reports', 'icon' => 'bi-graph-up', 'label' => 'Reports', 'hint' => 'Hire and revenue'],
                ['route' => 'reservations', 'icon' => 'bi-calendar3', 'label' => 'Reservations', 'hint' => 'Pipeline and confirmations'],
                ['route' => 'rentals', 'icon' => 'bi-key', 'label' => 'Rentals', 'hint' => 'Live hires and returns'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Fleet', 'hint' => 'Availability and rates'],
                ['route' => 'customers', 'icon' => 'bi-people', 'label' => 'Customers', 'hint' => 'Accounts and licences'],
                ['route' => 'drivers', 'icon' => 'bi-person-badge', 'label' => 'Drivers', 'hint' => 'Chauffeur assignments'],
            ],
            'Rental Officer' => [
                ['route' => 'reservations/form', 'icon' => 'bi-plus-circle', 'label' => 'New booking', 'hint' => 'Create a reservation'],
                ['route' => 'reservations', 'icon' => 'bi-calendar3', 'label' => 'Reservations', 'hint' => 'Confirm and update trips'],
                ['route' => 'customers', 'icon' => 'bi-people', 'label' => 'Customers', 'hint' => 'Profiles and documents'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Vehicles', 'hint' => 'Fleet and availability'],
                ['route' => 'rentals', 'icon' => 'bi-key', 'label' => 'Handovers', 'hint' => 'Pickup and return'],
                ['route' => 'drivers', 'icon' => 'bi-person-badge', 'label' => 'Drivers', 'hint' => 'Assign chauffeurs'],
            ],
            'Accountant/Cashier' => [
                ['route' => 'invoices', 'icon' => 'bi-receipt', 'label' => 'Invoices', 'hint' => 'Issue and collect'],
                ['route' => 'finance', 'icon' => 'bi-wallet2', 'label' => 'Payments', 'hint' => 'Ledger and MoMo'],
                ['route' => 'finance/form', 'icon' => 'bi-plus-lg', 'label' => 'Post payment', 'hint' => 'Cash, bank or refund'],
                ['route' => 'reports', 'icon' => 'bi-graph-up', 'label' => 'Finance reports', 'hint' => 'Receipts and outstanding'],
                ['route' => 'customers', 'icon' => 'bi-people', 'label' => 'Customers', 'hint' => 'Who owes what'],
            ],
            'Vehicle Inspector/Maintenance Staff' => [
                ['route' => 'rentals', 'icon' => 'bi-clipboard-check', 'label' => 'Inspections', 'hint' => 'Returned vehicles'],
                ['route' => 'maintenance', 'icon' => 'bi-wrench-adjustable', 'label' => 'Maintenance', 'hint' => 'Workshop jobs'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Fleet status', 'hint' => 'Cars in the workshop'],
                ['route' => 'reports', 'icon' => 'bi-graph-up', 'label' => 'Ops reports', 'hint' => 'Fleet availability'],
            ],
            'Customer' => [
                ['route' => 'search', 'icon' => 'bi-search', 'label' => 'Find a car', 'hint' => 'Search dates and book'],
                ['route' => 'fleet', 'icon' => 'bi-car-front', 'label' => 'Browse fleet', 'hint' => 'See rates and photos'],
                ['route' => 'reservations', 'icon' => 'bi-journal-text', 'label' => 'My trips', 'hint' => 'Bookings and status'],
                ['route' => 'invoices', 'icon' => 'bi-receipt', 'label' => 'My invoices', 'hint' => 'Pay with MoMo'],
                ['route' => 'contact', 'icon' => 'bi-telephone', 'label' => 'Contact us', 'hint' => 'Kigali support'],
            ],
            'Driver' => [
                ['route' => 'drivers/schedule', 'icon' => 'bi-calendar2-week', 'label' => 'Full schedule', 'hint' => 'Assigned chauffeur trips'],
                ['route' => 'dashboard', 'icon' => 'bi-geo-alt', 'label' => 'Today’s run', 'hint' => 'Next pickup and customer'],
            ],
            default => [],
        };
    }

    private function admin(): array
    {
        return [
            'kpis' => [
                ['label' => 'Users', 'value' => $this->count('SELECT COUNT(*) c FROM users'), 'hint' => 'All accounts', 'href' => url('admin/users')],
                ['label' => 'Fleet', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status <> 'inactive'"), 'hint' => 'Active vehicles', 'href' => url('vehicles')],
                ['label' => 'Open bookings', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status IN ('pending','confirmed')"), 'hint' => 'Pending + confirmed', 'href' => url('reservations')],
                ['label' => 'Live rentals', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue')"), 'hint' => 'On the road', 'href' => url('rentals')],
            ],
            'users' => Database::fetchAll('SELECT u.first_name, u.last_name, u.email, u.status, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id ORDER BY u.created_at DESC LIMIT 8'),
            'audit' => Database::fetchAll('SELECT action, entity_type, result, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 8'),
            'fleet' => Database::fetchAll('SELECT status, COUNT(*) c FROM vehicles GROUP BY status'),
            'smsFailed' => Database::fetchAll("SELECT subject, error_message, created_at FROM notifications WHERE channel = 'sms' AND status = 'failed' ORDER BY created_at DESC LIMIT 5"),
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
                ['label' => 'Fleet', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status <> 'inactive'"), 'hint' => 'Operational cars', 'href' => url('vehicles')],
                ['label' => 'Confirmed', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'confirmed'"), 'hint' => 'Upcoming hire', 'href' => url('reservations')],
                ['label' => 'Overdue returns', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue') AND expected_return_datetime < NOW()"), 'hint' => 'Need follow-up', 'href' => url('rentals')],
                ['label' => 'Outstanding', 'value' => Money::format($this->sum('SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances')), 'hint' => 'Unpaid invoices', 'href' => url('invoices')],
            ],
            'payments' => $fin['payments'] ?? 0,
            'refunds' => $fin['refunds'] ?? 0,
            'due' => Database::fetchAll(
                "SELECT rt.rental_id, rt.rental_number, rt.expected_return_datetime, rt.status, v.registration_no, c.first_name, c.last_name
                 FROM rentals rt JOIN vehicles v ON v.vehicle_id = rt.vehicle_id JOIN customers c ON c.customer_id = rt.customer_id
                 WHERE rt.status IN ('active','overdue','scheduled') ORDER BY rt.expected_return_datetime ASC LIMIT 10"
            ),
            'pending' => Database::fetchAll(
                "SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, c.first_name, c.last_name, v.registration_no
                 FROM reservations r JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
                 WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 6"
            ),
            'fleet' => Database::fetchAll('SELECT status, COUNT(*) c FROM vehicles GROUP BY status'),
        ];
    }

    private function officer(): array
    {
        return [
            'kpis' => [
                ['label' => 'To confirm', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'pending'"), 'hint' => 'Awaiting confirmation', 'href' => url('reservations')],
                ['label' => 'Pickups today', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'confirmed' AND DATE(pickup_datetime) = CURDATE()"), 'hint' => 'Confirmed today', 'href' => url('reservations')],
                ['label' => 'Returns today', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue') AND DATE(expected_return_datetime) = CURDATE()"), 'hint' => 'Due back', 'href' => url('rentals')],
                ['label' => 'Live hires', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue')"), 'hint' => 'Vehicles out', 'href' => url('rentals')],
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
            'returnsToday' => Database::fetchAll(
                "SELECT rt.rental_id, rt.rental_number, rt.expected_return_datetime, v.registration_no, c.first_name, c.last_name
                 FROM rentals rt JOIN vehicles v ON v.vehicle_id = rt.vehicle_id JOIN customers c ON c.customer_id = rt.customer_id
                 WHERE rt.status IN ('active','overdue') AND DATE(rt.expected_return_datetime) = CURDATE()
                 ORDER BY rt.expected_return_datetime"
            ),
        ];
    }

    private function cashier(): array
    {
        return [
            'kpis' => [
                ['label' => 'Outstanding', 'value' => Money::format($this->sum('SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances')), 'hint' => 'Open invoices', 'href' => url('invoices')],
                ['label' => 'Unpaid invoices', 'value' => $this->count("SELECT COUNT(*) c FROM invoices WHERE status IN ('issued','partially_paid','overdue')"), 'hint' => 'Need collection', 'href' => url('invoices')],
                ['label' => 'Pending MoMo', 'value' => $this->count("SELECT COUNT(*) c FROM financial_transactions WHERE status = 'pending' AND payment_method = 'mobile_money'"), 'hint' => 'Awaiting HDEV', 'href' => url('finance')],
                ['label' => 'Today receipts', 'value' => Money::format($this->sum("SELECT COALESCE(SUM(amount),0) s FROM financial_transactions WHERE transaction_type='payment' AND status='completed' AND DATE(transaction_date)=CURDATE()")), 'hint' => 'Completed payments', 'href' => url('finance')],
            ],
            'invoices' => Database::fetchAll(
                "SELECT i.invoice_id, i.invoice_number, i.total_amount, i.status, b.outstanding_balance, c.first_name, c.last_name
                 FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                 WHERE i.status IN ('issued','partially_paid','overdue','draft')
                 ORDER BY i.issue_date DESC LIMIT 10"
            ),
            'pendingPay' => Database::fetchAll(
                "SELECT transaction_id, transaction_number, amount, external_reference, status, transaction_date, invoice_id
                 FROM financial_transactions WHERE status = 'pending' ORDER BY created_at DESC LIMIT 8"
            ),
        ];
    }

    private function inspector(): array
    {
        return [
            'kpis' => [
                ['label' => 'Awaiting inspection', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status = 'returned'"), 'hint' => 'Returned cars', 'href' => url('rentals')],
                ['label' => 'Open maintenance', 'value' => $this->count("SELECT COUNT(*) c FROM maintenance_records WHERE status IN ('scheduled','in_progress')"), 'hint' => 'Blocking hire', 'href' => url('maintenance')],
                ['label' => 'In workshop', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status = 'maintenance'"), 'hint' => 'Vehicle status', 'href' => url('vehicles')],
                ['label' => 'Checks due', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE inspection_expiry_date IS NOT NULL AND inspection_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) AND status <> 'inactive'"), 'hint' => 'Next 14 days', 'href' => url('vehicles')],
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
            'dueChecks' => Database::fetchAll(
                "SELECT vehicle_id, registration_no, make, model, inspection_expiry_date
                 FROM vehicles
                 WHERE inspection_expiry_date IS NOT NULL AND inspection_expiry_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) AND status <> 'inactive'
                 ORDER BY inspection_expiry_date LIMIT 8"
            ),
        ];
    }

    private function customer(): array
    {
        $cid = Auth::customerId() ?: 0;
        return [
            'kpis' => [
                ['label' => 'My bookings', 'value' => $this->count('SELECT COUNT(*) c FROM reservations WHERE customer_id = ?', [$cid]), 'hint' => 'All reservations', 'href' => url('reservations')],
                ['label' => 'Upcoming', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE customer_id = ? AND status IN ('pending','confirmed') AND return_datetime >= NOW()", [$cid]), 'hint' => 'Still active', 'href' => url('reservations')],
                ['label' => 'Open invoices', 'value' => $this->count("SELECT COUNT(*) c FROM invoices WHERE customer_id = ? AND status IN ('issued','partially_paid','overdue')", [$cid]), 'hint' => 'To pay', 'href' => url('invoices')],
                ['label' => 'To pay', 'value' => Money::format($this->sum('SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances WHERE customer_id = ?', [$cid])), 'hint' => 'Outstanding RWF', 'href' => url('invoices')],
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

    private function driver(): array
    {
        $userId = Auth::id() ?: 0;
        $assignments = Database::fetchAll(
            "SELECT da.assignment_id, da.notes AS assignment_notes, r.reservation_id, r.reservation_code, r.pickup_datetime, r.return_datetime,
                    r.pickup_location, r.return_location, r.status AS reservation_status,
                    c.first_name, c.last_name, c.phone, v.registration_no, v.make, v.model
             FROM drivers d JOIN driver_assignments da ON da.driver_id = d.driver_id
             JOIN reservations r ON r.reservation_id = da.reservation_id
             JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
             WHERE d.user_id = ? AND r.status IN ('pending','confirmed') AND r.return_datetime >= NOW()
             ORDER BY r.pickup_datetime ASC LIMIT 100",
            [$userId]
        );
        $today = array_values(array_filter($assignments, static function (array $trip): bool {
            return date('Y-m-d', strtotime((string) $trip['pickup_datetime'])) === date('Y-m-d');
        }));
        return [
            'kpis' => [
                ['label' => 'Assigned trips', 'value' => count($assignments), 'hint' => 'Upcoming chauffeur bookings', 'href' => url('drivers/schedule')],
                ['label' => 'Today', 'value' => count($today), 'hint' => 'Pickups today'],
                ['label' => 'Next pickup', 'value' => $assignments ? date('M j, H:i', strtotime($assignments[0]['pickup_datetime'])) : '—', 'hint' => $assignments ? $assignments[0]['reservation_code'] : 'No upcoming trips'],
            ],
            'assignments' => $assignments,
            'nextTrip' => $assignments[0] ?? null,
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
