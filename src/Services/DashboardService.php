<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Auth;
use Crms\Database;
use Crms\Money;
use Crms\Rbac;

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
            'System Administrator' => 'System Overview',
            'Business Manager' => 'Business Performance',
            'Rental Officer' => 'Rental Operations',
            'Accountant/Cashier' => 'Financial Management',
            'Vehicle Inspector/Maintenance Staff' => 'Fleet Inspection and Maintenance',
            'Customer' => 'My Account',
            'Driver' => 'My Trips',
            default => 'Dashboard',
        };
    }

    public function data(array $user): array
    {
        $role = (string) ($user['role_name'] ?? '');
        $notificationService = new NotificationService();
        $notes = $notificationService->forUser(Auth::id(), Auth::customerId(), 12);
        $base = [
            'user' => $user,
            'notes' => $notes,
            'unreadNotificationCount' => $notificationService->unreadCount(Auth::id(), Auth::customerId()),
            'role' => $role,
            'shortcuts' => $this->shortcuts($role),
            'duties' => $this->duties($role),
            'restriction' => $this->restriction($role),
        ];
        if ($role === 'Business Manager' && Rbac::can('transactions.approve')) {
            array_unshift($base['shortcuts'], ['route' => 'finance/approvals', 'icon' => 'bi-shield-check', 'label' => 'Financial approvals', 'hint' => 'Independently review refunds and adjustments']);
        }
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
                ['route' => 'admin/users', 'icon' => 'bi-people', 'label' => 'Users & roles', 'hint' => 'Manage accounts, roles and permissions'],
                ['route' => 'admin/settings', 'icon' => 'bi-sliders', 'label' => 'System settings', 'hint' => 'Manage configuration'],
                ['route' => 'admin/audit', 'icon' => 'bi-shield-check', 'label' => 'Audit & security', 'hint' => 'Review user activity and account access'],
                ['route' => 'admin/backup', 'icon' => 'bi-hdd', 'label' => 'Backup & recovery', 'hint' => 'Manage system backups'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'System records', 'hint' => 'View fleet and operating records'],
                ['route' => 'reports', 'icon' => 'bi-graph-up', 'label' => 'System reports', 'hint' => 'Generate company-wide reports'],
            ],
            'Business Manager' => [
                ['route' => 'reports', 'icon' => 'bi-graph-up', 'label' => 'Reports & exports', 'hint' => 'Review permitted business reports'],
                ['route' => 'reservations', 'icon' => 'bi-calendar3', 'label' => 'Reservations', 'hint' => 'Booking pipeline and performance'],
                ['route' => 'rentals', 'icon' => 'bi-key', 'label' => 'Rentals & returns', 'hint' => 'Active hires and upcoming returns'],
                ['route' => 'drivers', 'icon' => 'bi-person-badge', 'label' => 'Drivers', 'hint' => 'View driver records and manage assignments'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Fleet availability', 'hint' => 'Utilization and vehicle status'],
                ['route' => 'maintenance', 'icon' => 'bi-wrench-adjustable', 'label' => 'Maintenance alerts', 'hint' => 'Open service and repair work'],
                ['route' => 'invoices', 'icon' => 'bi-receipt', 'label' => 'Outstanding payments', 'hint' => 'Review unpaid invoice balances'],
            ],
            'Rental Officer' => [
                ['route' => 'search', 'icon' => 'bi-search', 'label' => 'Find available vehicles', 'hint' => 'Check dates, category, model and features'],
                ['route' => 'reservations/form', 'icon' => 'bi-plus-circle', 'label' => 'New reservation', 'hint' => 'Create a booking request'],
                ['route' => 'reservations', 'icon' => 'bi-calendar3', 'label' => 'Reservations', 'hint' => 'Review and update bookings'],
                ['route' => 'customers', 'icon' => 'bi-people', 'label' => 'Customers', 'hint' => 'Register and update customer records'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Vehicles', 'hint' => 'Register vehicles and permitted details'],
                ['route' => 'rentals', 'icon' => 'bi-key', 'label' => 'Pickups and returns', 'hint' => 'Agreements and handover records'],
            ],
            'Vehicle Inspector/Maintenance Staff' => [
                ['route' => 'maintenance', 'icon' => 'bi-wrench-adjustable', 'label' => 'Maintenance vehicles', 'hint' => 'Review open work and record readiness decisions'],
                ['route' => 'vehicles', 'icon' => 'bi-car-front', 'label' => 'Vehicles in maintenance', 'hint' => 'View vehicles currently held out of service'],
            ],
            'Customer' => [
                ['route' => 'search', 'icon' => 'bi-search', 'label' => 'Search vehicles', 'hint' => 'Filter dates, category, price and features'],
                ['route' => 'reservations/form', 'icon' => 'bi-plus-circle', 'label' => 'Request a reservation', 'hint' => 'Choose a vehicle and rental dates'],
                ['route' => 'fleet', 'icon' => 'bi-car-front', 'label' => 'Browse fleet', 'hint' => 'Photos and daily rates'],
                ['route' => 'reservations', 'icon' => 'bi-journal-text', 'label' => 'My reservations', 'hint' => 'Status, booking details and eligible changes'],
                ['route' => 'rentals', 'icon' => 'bi-key', 'label' => 'My rentals & agreements', 'hint' => 'View your rental and agreement records'],
                ['route' => 'invoices', 'icon' => 'bi-receipt', 'label' => 'My invoices & receipts', 'hint' => 'Balances, payments and deposit activity'],
                ['route' => 'customers/profile', 'icon' => 'bi-person', 'label' => 'My profile', 'hint' => 'Update your contact details'],
            ],
            'Driver' => [
                ['route' => 'drivers/schedule', 'icon' => 'bi-calendar2-week', 'label' => 'My schedule', 'hint' => 'View your assigned trips and times'],
            ],
            default => [],
        };
    }

    /** @return list<string> */
    private function duties(string $role): array
    {
        return match ($role) {
            'System Administrator' => [
                'Create, edit, activate, deactivate and manage user accounts',
                'Assign roles and permissions',
                'Manage system settings and configuration',
                'View system-wide vehicles, reservations, rentals, payments and maintenance records',
                'Review audit logs and user activities',
                'Manage backup and recovery procedures',
                'Monitor security events and account access',
                'Generate system-wide reports',
            ],
            'Business Manager' => [
                'View business KPIs, revenue, bookings, active rentals and vehicle utilization',
                'Review operational and financial reports',
                'Monitor outstanding payments and upcoming returns',
                'Review fleet availability and maintenance alerts',
                'Monitor rental officers\' operational activity',
                'Review reservation and rental performance',
                'Export permitted reports to PDF or Excel',
                'Review business trends to support decisions',
            ],
            'Rental Officer' => [
                'Register and update customer records',
                'Register vehicles and update permitted vehicle details',
                'Search available vehicles by dates, category, model, price, fuel and transmission',
                'Create, review, confirm, update and cancel reservations according to business rules',
                'Prevent overlapping confirmed bookings for the same vehicle',
                'Generate rental agreements',
                'Record vehicle pickup and return details',
                'Record mileage, fuel levels and handover information',
                'Flag late returns, damage and additional charges for processing',
                'View upcoming pickups, returns and reservation notifications',
            ],
            'Accountant/Cashier' => [
                'View invoices and rental charges',
                'Create or finalize invoices according to the assigned workflow',
                'Record full and partial payments',
                'Record payment method, transaction reference, date and status',
                'Generate and print receipts',
                'Track outstanding balances and overdue payments',
                'Record security deposits, deductions and refunds',
                'Reconcile payment records',
                'Generate revenue, payment, deposit and refund reports',
            ],
            'Vehicle Inspector/Maintenance Staff' => [
                'View vehicles currently held in maintenance and their service records',
                'Update maintenance job progress and completion details',
                'Choose whether a vehicle is ready for service or must remain on a safety hold',
                'Record an inspection note for every vehicle readiness decision',
                'Release a vehicle only after all open maintenance jobs are resolved',
                'Do not manage rental schedules, reservations, or driver assignments',
            ],
            'Customer' => [
                'Register an account and log in securely',
                'Update permitted personal and contact information',
                'Search vehicles by availability, rental dates, category, price, fuel and transmission',
                'View vehicle details, photographs and rental rates',
                'Request a reservation',
                'View reservation status and booking details',
                'Cancel or modify eligible reservations under business rules',
                'View your own rental agreements, invoices, payments, receipts and deposit information',
                'View outstanding balances and upcoming return dates',
                'Receive booking, payment and return notifications',
            ],
            'Driver' => [
                'View your own profile and contact details',
                'View assigned trips and schedules',
                'View pickup locations, destinations and trip times',
                'View only the vehicle and customer information needed for each assigned trip',
                'Check assignment status and trip instructions',
                'Update trip progress or completion where enabled by the business',
                'Report delays, incidents or vehicle problems',
                'Receive assignment notifications',
            ],
            default => [],
        };
    }

    private function restriction(string $role): string
    {
        return match ($role) {
            'System Administrator' => 'Do not automatically approve financial transactions or refunds. Approve them only when explicitly authorized by business policy.',
            'Business Manager' => 'Do not manage system security settings or change payment records without an explicitly assigned permission.',
            'Rental Officer' => 'Should not access unrestricted system administration or perform unauthorized financial adjustments.',
            'Accountant/Cashier' => 'Refunds, reversals and financial adjustments must follow approval rules, include a reason, and be recorded in the audit log. Posted rows are never deleted.',
            'Vehicle Inspector/Maintenance Staff' => 'Must not delete financial transactions or confirm rentals unless specifically authorized.',
            'Customer' => 'You can only access your own account, reservations, rentals and financial records. Changing a URL or record ID cannot show another customer’s records.',
            'Driver' => 'You cannot see other drivers\' private records, all customer accounts, financial reports or unassigned trips.',
            default => '',
        };
    }

    private function admin(): array
    {
        return [
            'kpis' => [
                ['label' => 'Total Users', 'value' => $this->count('SELECT COUNT(*) c FROM users'), 'hint' => 'Accounts across all roles', 'icon' => 'bi-people-fill', 'href' => url('admin/users')],
                ['label' => 'User Roles', 'value' => $this->count('SELECT COUNT(*) c FROM roles'), 'hint' => 'Configured access groups', 'icon' => 'bi-person-gear', 'href' => url('admin/roles')],
                ['label' => 'Permissions', 'value' => $this->count('SELECT COUNT(*) c FROM permissions'), 'hint' => 'Available access rules', 'icon' => 'bi-shield-lock-fill', 'href' => url('admin/roles')],
                ['label' => 'Audit Events', 'value' => $this->count('SELECT COUNT(*) c FROM audit_logs'), 'hint' => 'Recorded system activity', 'icon' => 'bi-journal-check', 'href' => url('admin/audit')],
            ],
            'records' => [
                ['label' => 'Vehicles', 'value' => $this->count('SELECT COUNT(*) c FROM vehicles'), 'route' => 'vehicles', 'icon' => 'bi-car-front'],
                ['label' => 'Reservations', 'value' => $this->count('SELECT COUNT(*) c FROM reservations'), 'route' => 'reservations', 'icon' => 'bi-calendar3'],
                ['label' => 'Rentals', 'value' => $this->count('SELECT COUNT(*) c FROM rentals'), 'route' => 'rentals', 'icon' => 'bi-key'],
                ['label' => 'Payments', 'value' => $this->count('SELECT COUNT(*) c FROM financial_transactions'), 'route' => 'finance', 'icon' => 'bi-wallet2'],
                ['label' => 'Maintenance', 'value' => $this->count('SELECT COUNT(*) c FROM maintenance_records'), 'route' => 'maintenance', 'icon' => 'bi-wrench-adjustable'],
            ],
            'users' => Database::fetchAll('SELECT u.first_name, u.last_name, u.email, u.status, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id ORDER BY u.created_at DESC LIMIT 8'),
            'audit' => Database::fetchAll('SELECT action, entity_type, result, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 8'),
        ];
    }

    private function manager(): array
    {
        $canViewFinancial = Rbac::can('reports.financial');
        $monthRevenue = 0.0;
        $outstanding = 0.0;
        if ($canViewFinancial) {
            $fin = Database::fetch(
                "SELECT COALESCE(SUM(amount),0) revenue
                 FROM financial_transactions
                 WHERE transaction_type = 'payment' AND status = 'completed'
                   AND transaction_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
            );
            $monthRevenue = (float) ($fin['revenue'] ?? 0);
            $outstanding = $this->sum('SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances');
        }
        $fleetSize = $this->count("SELECT COUNT(*) c FROM vehicles WHERE status <> 'inactive'");
        $onHire = $this->count("SELECT COUNT(*) c FROM vehicles WHERE status = 'rented'");
        $util = $fleetSize > 0 ? (string) round(($onHire / $fleetSize) * 100) . '%' : '—';
        $trendSources = [
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS bookings, 0 AS rentals, 0 AS revenue
             FROM reservations
             WHERE created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')",
            "SELECT DATE_FORMAT(COALESCE(rt.actual_pickup_datetime, r.pickup_datetime), '%Y-%m') AS period,
                    0 AS bookings, COUNT(*) AS rentals, 0 AS revenue
             FROM rentals rt JOIN reservations r ON r.reservation_id = rt.reservation_id
             WHERE COALESCE(rt.actual_pickup_datetime, r.pickup_datetime) >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
               AND rt.status <> 'cancelled'
             GROUP BY DATE_FORMAT(COALESCE(rt.actual_pickup_datetime, r.pickup_datetime), '%Y-%m')",
        ];
        if ($canViewFinancial) {
            $trendSources[] = "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS period,
                    0 AS bookings, 0 AS rentals, SUM(amount) AS revenue
             FROM financial_transactions
             WHERE transaction_type = 'payment' AND status = 'completed'
               AND transaction_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
             GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')";
        }
        $trendRows = Database::fetchAll(
            'SELECT period, SUM(bookings) AS bookings, SUM(rentals) AS rentals, SUM(revenue) AS revenue
             FROM (' . implode(' UNION ALL ', $trendSources) . ') AS monthly_activity
             GROUP BY period ORDER BY period'
        );
        $trendByMonth = [];
        foreach ($trendRows as $row) {
            $trendByMonth[$row['period']] = $row;
        }
        $trends = [];
        $currentMonth = new \DateTimeImmutable(date('Y-m-01'));
        for ($offset = 5; $offset >= 0; $offset--) {
            $month = $currentMonth->modify('-' . $offset . ' months');
            $period = $month->format('Y-m');
            $row = $trendByMonth[$period] ?? [];
            $trends[] = [
                'period' => $period,
                'label' => $month->format('M Y'),
                'bookings' => (int) ($row['bookings'] ?? 0),
                'rentals' => (int) ($row['rentals'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
            ];
        }
        $dueCondition = "status IN ('active','overdue','scheduled')
                         AND (expected_return_datetime < NOW() OR expected_return_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY))";
        $kpis = [];
        if ($canViewFinancial) {
            $kpis[] = ['label' => 'Revenue this month', 'value' => Money::format($monthRevenue), 'hint' => 'Completed payments', 'href' => url('reports')];
        }
        $kpis[] = ['label' => 'Bookings this month', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"), 'hint' => 'Created this month', 'href' => url('reservations')];
        $kpis[] = ['label' => 'Active rentals', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue')"), 'hint' => 'Currently on hire', 'href' => url('rentals')];
        $kpis[] = ['label' => 'Vehicle utilization', 'value' => $util, 'hint' => $onHire . ' of ' . $fleetSize . ' active vehicles on hire', 'href' => url('vehicles')];
        if ($canViewFinancial) {
            $kpis[] = ['label' => 'Outstanding payments', 'value' => Money::format($outstanding), 'hint' => 'Unpaid invoice balances', 'href' => url('invoices')];
        }
        if (Rbac::can('transactions.approve')) {
            $kpis[] = ['label' => 'Finance approvals', 'value' => $this->count("SELECT COUNT(*) c FROM financial_transactions WHERE status = 'awaiting_approval'"), 'hint' => 'Independent review required', 'href' => url('finance/approvals')];
        }
        $pendingReservations = $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'pending'");
        $kpis[] = ['label' => 'Reservations to approve', 'value' => $pendingReservations, 'hint' => 'Awaiting a booking decision', 'href' => url('reservations')];
        $kpis[] = ['label' => 'Returns due / overdue', 'value' => $this->count('SELECT COUNT(*) c FROM rentals WHERE ' . $dueCondition), 'hint' => 'Overdue or due within 7 days', 'href' => url('rentals')];
        return [
            'kpis' => $kpis,
            'pendingReservations' => Database::fetchAll(
                "SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, r.return_datetime,
                        c.first_name, c.last_name, v.registration_no
                 FROM reservations r JOIN customers c ON c.customer_id = r.customer_id
                 JOIN vehicles v ON v.vehicle_id = r.vehicle_id
                 WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 8"
            ),
            'trends' => $trends,
            'canViewFinancial' => $canViewFinancial,
            'officers' => Database::fetchAll(
                "SELECT u.first_name, u.last_name, COALESCE(SUM(activity.bookings),0) AS bookings,
                        COALESCE(SUM(activity.rentals),0) AS rentals
                 FROM users u JOIN roles ro ON ro.role_id = u.role_id
                 LEFT JOIN (
                    SELECT created_by AS user_id, COUNT(*) AS bookings, 0 AS rentals
                    FROM reservations
                    WHERE created_by IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY created_by
                    UNION ALL
                    SELECT handled_by AS user_id, 0 AS bookings, COUNT(*) AS rentals
                    FROM rentals
                    WHERE handled_by IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY handled_by
                 ) activity ON activity.user_id = u.user_id
                 WHERE ro.role_name = 'Rental Officer'
                 GROUP BY u.user_id, u.first_name, u.last_name
                 ORDER BY COALESCE(SUM(activity.bookings),0) + COALESCE(SUM(activity.rentals),0) DESC LIMIT 6"
            ),
            'maintAlerts' => Database::fetchAll(
                "SELECT m.title, m.status, v.registration_no FROM maintenance_records m
                 JOIN vehicles v ON v.vehicle_id = m.vehicle_id
                 WHERE m.status IN ('scheduled','in_progress')
                 ORDER BY COALESCE(m.expected_completion_date, m.start_date), m.start_date LIMIT 6"
            ),
            'due' => Database::fetchAll(
                "SELECT rt.rental_id, rt.rental_number, rt.expected_return_datetime, rt.status, v.registration_no, c.first_name, c.last_name
                 FROM rentals rt JOIN vehicles v ON v.vehicle_id = rt.vehicle_id JOIN customers c ON c.customer_id = rt.customer_id
                 WHERE rt.status IN ('active','overdue','scheduled')
                   AND (rt.expected_return_datetime < NOW() OR rt.expected_return_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY))
                 ORDER BY rt.expected_return_datetime ASC LIMIT 10"
            ),
            'fleet' => Database::fetchAll('SELECT status, COUNT(*) c FROM vehicles GROUP BY status'),
            'reservationPerformance' => Database::fetchAll('SELECT status, COUNT(*) c FROM reservations GROUP BY status ORDER BY status'),
            'rentalPerformance' => Database::fetchAll('SELECT status, COUNT(*) c FROM rentals GROUP BY status ORDER BY status'),
        ];
    }

    private function officer(): array
    {
        return [
            'kpis' => [
                ['label' => 'Reservations to approve', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'pending'"), 'hint' => 'Awaiting a booking decision', 'href' => url('reservations')],
                ['label' => 'Pickups this week', 'value' => $this->count("SELECT COUNT(*) c FROM reservations WHERE status = 'confirmed' AND pickup_datetime >= NOW() AND pickup_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY)"), 'hint' => 'Confirmed reservations', 'href' => url('reservations')],
                ['label' => 'Returns this week', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue') AND expected_return_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY)"), 'hint' => 'Due back or overdue', 'href' => url('rentals')],
                ['label' => 'Late returns', 'value' => $this->count("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue') AND expected_return_datetime < NOW()"), 'hint' => 'Needs follow-up', 'href' => url('rentals')],
            ],
            'pending' => Database::fetchAll(
                "SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, r.return_datetime, r.status, c.first_name, c.last_name, v.registration_no
                 FROM reservations r JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
                 WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 8"
            ),
            'upcomingPickups' => Database::fetchAll(
                "SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, r.status, c.first_name, c.last_name, v.registration_no
                 FROM reservations r JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
                 WHERE r.status = 'confirmed' AND r.pickup_datetime >= NOW() AND r.pickup_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                 ORDER BY r.pickup_datetime LIMIT 10"
            ),
            'upcomingReturns' => Database::fetchAll(
                "SELECT rt.rental_id, rt.rental_number, rt.expected_return_datetime, rt.status,
                        CASE WHEN rt.status = 'active' AND rt.expected_return_datetime < NOW() THEN 'overdue' ELSE rt.status END AS display_status,
                        v.registration_no, c.first_name, c.last_name
                 FROM rentals rt JOIN vehicles v ON v.vehicle_id = rt.vehicle_id JOIN customers c ON c.customer_id = rt.customer_id
                 WHERE rt.status IN ('active','overdue')
                   AND (rt.expected_return_datetime < NOW() OR rt.expected_return_datetime <= DATE_ADD(NOW(), INTERVAL 7 DAY))
                 ORDER BY rt.expected_return_datetime LIMIT 10"
            ),
        ];
    }

    private function cashier(): array
    {
        $depositBalance = $this->sum(
            "SELECT COALESCE(SUM(CASE WHEN transaction_type='deposit_received' AND status='completed' THEN amount
                WHEN transaction_type IN ('deposit_refund','deposit_deduction') AND status='completed' THEN -amount END),0) s
             FROM financial_transactions"
        );
        return [
            'kpis' => [
                ['label' => 'Outstanding balance', 'value' => Money::format($this->sum('SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances')), 'hint' => 'Across open invoices', 'href' => url('invoices')],
                ['label' => 'Overdue invoices', 'value' => $this->count("SELECT COUNT(*) c FROM invoices i LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id WHERE i.status IN ('overdue','issued','partially_paid') AND i.due_date IS NOT NULL AND i.due_date < CURDATE() AND COALESCE(b.outstanding_balance, i.total_amount) > 0"), 'hint' => 'Past due with a balance', 'href' => url('invoices')],
                ['label' => 'Held deposits', 'value' => Money::format($depositBalance), 'hint' => 'Net deposit balance', 'href' => url('finance')],
                ['label' => 'Payments received today', 'value' => Money::format($this->sum("SELECT COALESCE(SUM(amount),0) s FROM financial_transactions WHERE transaction_type='payment' AND status='completed' AND DATE(transaction_date)=CURDATE()")), 'hint' => 'Completed payments', 'href' => url('finance')],
                ['label' => 'Requests awaiting approval', 'value' => $this->count("SELECT COUNT(*) c FROM financial_transactions WHERE status='awaiting_approval' AND approval_requested_by = ?", [Auth::id()]), 'hint' => 'Refunds and adjustments', 'href' => url('finance')],
                ['label' => 'Unreconciled entries', 'value' => $this->count("SELECT COUNT(*) c FROM financial_transactions WHERE status='completed' AND reconciled_at IS NULL"), 'hint' => 'Match to bank or receipt records', 'href' => url('finance')],
            ],
            'invoices' => Database::fetchAll(
                "SELECT i.invoice_id, i.invoice_number, i.total_amount, i.status, b.outstanding_balance, c.first_name, c.last_name
                 FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                 WHERE i.status IN ('draft','issued','partially_paid')
                 ORDER BY CASE WHEN i.status = 'draft' THEN 0 ELSE 1 END, i.issue_date DESC LIMIT 10"
            ),
            'unbilledRentals' => Database::fetchAll(
                "SELECT rt.rental_id, rt.rental_number, rt.actual_return_datetime, rt.expected_return_datetime,
                        rt.extra_mileage_fee, rt.late_return_fee, rt.damage_fee, rt.cleaning_fee, rt.other_charges,
                        rt.return_notes, c.first_name, c.last_name
                 FROM rentals rt JOIN customers c ON c.customer_id = rt.customer_id
                 WHERE rt.status = 'closed' AND NOT EXISTS (
                   SELECT 1 FROM invoices i WHERE i.rental_id = rt.rental_id AND i.status <> 'void'
                 )
                 ORDER BY rt.actual_return_datetime DESC LIMIT 6"
            ),
            'pendingPay' => Database::fetchAll(
                "SELECT transaction_id, transaction_number, amount, external_reference, status, transaction_date, invoice_id
                 FROM financial_transactions WHERE status = 'pending' AND payment_method = 'mobile_money' ORDER BY created_at DESC LIMIT 8"
            ),
            'pendingApproval' => Database::fetchAll(
                "SELECT transaction_id, transaction_number, transaction_type, amount, reason, created_at
                 FROM financial_transactions WHERE status = 'awaiting_approval' AND approval_requested_by = ?
                 ORDER BY created_at DESC LIMIT 6",
                [Auth::id()]
            ),
            'recentTransactions' => Database::fetchAll(
                "SELECT ft.transaction_id, ft.transaction_number, ft.transaction_type, ft.amount, ft.payment_method,
                        ft.external_reference, ft.status, ft.transaction_date, ft.reconciled_at, c.first_name, c.last_name
                 FROM financial_transactions ft JOIN customers c ON c.customer_id = ft.customer_id
                 ORDER BY ft.transaction_date DESC LIMIT 12"
            ),
            'overdue' => Database::fetchAll(
                "SELECT i.invoice_id, i.invoice_number, i.due_date, b.outstanding_balance, c.first_name, c.last_name
                 FROM invoices i JOIN customers c ON c.customer_id = i.customer_id
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                 WHERE i.status IN ('overdue','issued','partially_paid') AND i.due_date IS NOT NULL AND i.due_date < CURDATE()
                   AND COALESCE(b.outstanding_balance, i.total_amount) > 0
                 ORDER BY i.due_date ASC LIMIT 8"
            ),
        ];
    }

    private function inspector(): array
    {
        return [
            'kpis' => [
                ['label' => 'Vehicles in maintenance', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles WHERE status = 'maintenance'"), 'hint' => 'Held out of service', 'href' => url('vehicles')],
                ['label' => 'Open maintenance jobs', 'value' => $this->count("SELECT COUNT(*) c FROM maintenance_records m JOIN vehicles v ON v.vehicle_id=m.vehicle_id WHERE v.status='maintenance' AND m.status IN ('scheduled','in_progress')"), 'hint' => 'Work still in progress', 'href' => url('maintenance')],
                ['label' => 'Awaiting readiness review', 'value' => $this->count("SELECT COUNT(*) c FROM vehicles v WHERE v.status='maintenance' AND NOT EXISTS (SELECT 1 FROM maintenance_records m WHERE m.vehicle_id=v.vehicle_id AND m.status IN ('scheduled','in_progress'))"), 'hint' => 'No open jobs; inspection sign-off required', 'href' => url('maintenance')],
                ['label' => 'Overdue open jobs', 'value' => $this->count("SELECT COUNT(*) c FROM maintenance_records m JOIN vehicles v ON v.vehicle_id=m.vehicle_id WHERE v.status='maintenance' AND m.status IN ('scheduled','in_progress') AND m.expected_completion_date < CURDATE()"), 'hint' => 'Past expected completion', 'href' => url('maintenance')],
            ],
            'maintenanceVehicles' => Database::fetchAll(
                "SELECT v.vehicle_id, v.registration_no, v.make, v.model,
                        (SELECT COUNT(*) FROM maintenance_records m WHERE m.vehicle_id=v.vehicle_id AND m.status IN ('scheduled','in_progress')) AS open_jobs,
                        (SELECT m.title FROM maintenance_records m WHERE m.vehicle_id=v.vehicle_id AND m.status IN ('scheduled','in_progress') ORDER BY COALESCE(m.expected_completion_date,m.start_date),m.maintenance_id LIMIT 1) AS current_job,
                        (SELECT m.expected_completion_date FROM maintenance_records m WHERE m.vehicle_id=v.vehicle_id AND m.status IN ('scheduled','in_progress') ORDER BY COALESCE(m.expected_completion_date,m.start_date),m.maintenance_id LIMIT 1) AS expected_completion_date
                 FROM vehicles v WHERE v.status='maintenance' ORDER BY v.registration_no LIMIT 100"
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
                ['label' => 'My rentals', 'value' => $this->count('SELECT COUNT(*) c FROM rentals WHERE customer_id = ?', [$cid]), 'hint' => 'Agreements and return dates', 'href' => url('rentals')],
                ['label' => 'Open invoices', 'value' => $this->count("SELECT COUNT(*) c FROM invoices WHERE customer_id = ? AND status IN ('issued','partially_paid','overdue')", [$cid]), 'hint' => 'To pay', 'href' => url('invoices')],
                ['label' => 'To pay', 'value' => Money::format($this->sum("SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances WHERE customer_id = ? AND status IN ('issued','partially_paid','overdue')", [$cid])), 'hint' => 'Outstanding RWF', 'href' => url('invoices')],
            ],
            'reservations' => Database::fetchAll(
                'SELECT r.reservation_id, r.reservation_code, r.pickup_datetime, r.return_datetime, r.status, r.payment_status,
                        v.make, v.model, v.registration_no, v.image_path FROM reservations r
                 JOIN vehicles v ON v.vehicle_id = r.vehicle_id WHERE r.customer_id = ? ORDER BY r.created_at DESC LIMIT 8',
                [$cid]
            ),
            'invoices' => Database::fetchAll(
                "SELECT i.invoice_id, i.invoice_number, i.status, i.created_at, i.notes,
                        b.outstanding_balance, c.phone, r.status AS reservation_status,
                        r.approval_note, r.approver_signature, r.customer_signature,
                        COALESCE(pm.pending_momo, 0) AS pending_momo_amount
                 FROM invoices i
                 JOIN customers c ON c.customer_id = i.customer_id
                 LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
                 LEFT JOIN reservations r ON r.reservation_id = i.reservation_id
                 LEFT JOIN (
                    SELECT invoice_id, SUM(amount) AS pending_momo
                    FROM financial_transactions
                    WHERE transaction_type = 'payment' AND payment_method = 'mobile_money' AND status = 'pending'
                    GROUP BY invoice_id
                 ) pm ON pm.invoice_id = i.invoice_id
                 WHERE i.customer_id = ? AND i.status NOT IN ('draft','void') ORDER BY i.created_at DESC LIMIT 8",
                [$cid]
            ),
            'rentals' => Database::fetchAll(
                'SELECT rt.rental_id, rt.rental_number, rt.status, rt.agreement_status, rt.expected_return_datetime,
                        rt.actual_return_datetime, rt.created_at,
                        r.reservation_code, v.make, v.model, v.registration_no
                 FROM rentals rt
                 JOIN reservations r ON r.reservation_id = rt.reservation_id AND r.customer_id = rt.customer_id
                 JOIN vehicles v ON v.vehicle_id = rt.vehicle_id
                 WHERE rt.customer_id = ? ORDER BY rt.created_at DESC LIMIT 8',
                [$cid]
            ),
            'payments' => Database::fetchAll(
                'SELECT transaction_id, transaction_number, transaction_type, amount, payment_method,
                        external_reference, status, transaction_date, invoice_id
                 FROM financial_transactions WHERE customer_id = ?
                 ORDER BY transaction_date DESC, transaction_id DESC LIMIT 8',
                [$cid]
            ),
            'fleet' => Database::fetchAll(
                "SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id
                 WHERE v.status = 'available' ORDER BY v.daily_rate ASC LIMIT 6"
            ),
            'deposit' => Money::format($this->sum(
                "SELECT COALESCE(SUM(CASE WHEN transaction_type='deposit_received' AND status='completed' THEN amount
                    WHEN transaction_type IN ('deposit_refund','deposit_deduction') AND status='completed' THEN -amount END),0) s
                 FROM financial_transactions WHERE customer_id = ?",
                [$cid]
            )),
            'upcomingReturn' => Database::fetch(
                "SELECT rt.expected_return_datetime, rt.status, v.registration_no FROM rentals rt
                 JOIN vehicles v ON v.vehicle_id = rt.vehicle_id
                 WHERE rt.customer_id = ? AND rt.status IN ('scheduled','active','overdue')
                 ORDER BY CASE WHEN rt.expected_return_datetime < NOW() THEN 0 ELSE 1 END, rt.expected_return_datetime ASC LIMIT 1",
                [$cid]
            ),
            'profile' => Database::fetch('SELECT customer_id, first_name FROM customers WHERE customer_id = ?', [$cid]),
        ];
    }

    private function driver(): array
    {
        $userId = Auth::id() ?: 0;
        $assignments = Database::fetchAll(
            "SELECT da.assignment_id, da.notes AS assignment_notes, da.trip_status, da.incident_report, da.status_updated_at,
                    r.reservation_id, r.reservation_code, r.pickup_datetime, r.return_datetime,
                    r.pickup_location, r.return_location, r.status AS reservation_status,
                    c.first_name, c.last_name, c.phone, v.registration_no, v.make, v.model
             FROM drivers d JOIN driver_assignments da ON da.driver_id = d.driver_id
             JOIN reservations r ON r.reservation_id = da.reservation_id
             JOIN customers c ON c.customer_id = r.customer_id JOIN vehicles v ON v.vehicle_id = r.vehicle_id
             WHERE d.user_id = ? AND r.status IN ('pending','confirmed') AND da.trip_status <> 'completed'
             ORDER BY CASE WHEN r.pickup_datetime >= NOW() THEN 0 WHEN r.return_datetime >= NOW() THEN 1 ELSE 2 END,
                      CASE WHEN r.pickup_datetime >= NOW() THEN r.pickup_datetime ELSE r.return_datetime END ASC LIMIT 100",
            [$userId]
        );
        $today = array_values(array_filter($assignments, static function (array $trip): bool {
            return date('Y-m-d', strtotime((string) $trip['pickup_datetime'])) === date('Y-m-d');
        }));
        $nextTrip = null;
        foreach ($assignments as $trip) {
            if (strtotime((string) $trip['pickup_datetime']) >= time()) {
                $nextTrip = $trip;
                break;
            }
            if ($nextTrip === null) {
                $nextTrip = $trip;
            }
        }
        return [
            'kpis' => [
                ['label' => 'Assigned trips', 'value' => count($assignments), 'hint' => 'Upcoming, active and overdue assignments', 'href' => url('drivers/schedule')],
                ['label' => 'Today', 'value' => count($today), 'hint' => 'Pickups today'],
                ['label' => 'Next pickup / overdue', 'value' => $nextTrip ? date('M j, H:i', strtotime($nextTrip['pickup_datetime'])) : '—', 'hint' => $nextTrip ? $nextTrip['reservation_code'] : 'No upcoming trips'],
            ],
            'assignments' => $assignments,
            'nextTrip' => $nextTrip,
            'profile' => Database::fetch(
                'SELECT d.*, u.email AS account_email FROM drivers d LEFT JOIN users u ON u.user_id=d.user_id WHERE d.user_id = ?',
                [$userId]
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
