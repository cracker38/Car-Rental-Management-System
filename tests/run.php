<?php
declare(strict_types=1);

/**
 * Run: C:\xampp\php\php.exe tests/run.php
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use Crms\Money;
use Crms\Period;
use Crms\Phone;
use Crms\Services\AvailabilityService;
use Crms\Services\HdevClient;
use Crms\Services\PricingService;
use Crms\Services\ReservationService;
use Crms\Services\RentalService;
use Crms\Services\DashboardService;
use Crms\Services\ReportExportService;

$failed = 0;
$passed = 0;

function expect($cond, string $name): void
{
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "PASS  {$name}\n";
    } else {
        $failed++;
        echo "FAIL  {$name}\n";
    }
}

function testSignaturePng(bool $withInk): string
{
    $width = 120;
    $height = 40;
    $rows = [];
    for ($y = 0; $y < $height; $y++) {
        $row = "\0" . str_repeat("\0", $width * 4);
        if ($withInk && $y >= 8 && $y <= 20) {
            for ($x = 10; $x <= 100; $x++) {
                if ($y === 8 + intdiv($x - 10, 7)) {
                    $offset = 1 + $x * 4;
                    $row[$offset] = chr(23);
                    $row[$offset + 1] = chr(37);
                    $row[$offset + 2] = chr(84);
                    $row[$offset + 3] = chr(255);
                }
            }
        }
        $rows[] = $row;
    }
    $chunk = static fn(string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    $png = "\x89PNG\r\n\x1a\n";
    $png .= $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0));
    $png .= $chunk('IDAT', gzcompress(implode('', $rows)));
    $png .= $chunk('IEND', '');
    return 'data:image/png;base64,' . base64_encode($png);
}

echo "=== Unit tests (no database required for most) ===\n";

$fleetFallbacks = [
    'Economy' => 'hatchback1.jpg',
    'Sedan' => 'sedan1.jpg',
    'SUV' => 'SUV3.jpg',
    'Van' => 'van1.jpg',
    'Luxury' => 'luxury car1.jpg',
];
foreach ($fleetFallbacks as $category => $photo) {
    expect(is_file(dirname(__DIR__) . '/public/img/fleet/' . $photo), $category . ' fallback photo exists');
    expect(\Crms\Ui::vehicleImage(null, $category) === asset('public/img/fleet/' . $photo), $category . ' vehicles use their local fleet photo');
}

$dashboardRoles = [
    'System Administrator' => 'dashboards/admin',
    'Business Manager' => 'dashboards/manager',
    'Rental Officer' => 'dashboards/rental-officer',
    'Accountant/Cashier' => 'dashboards/accountant',
    'Vehicle Inspector/Maintenance Staff' => 'dashboards/inspector',
    'Customer' => 'dashboards/customer',
    'Driver' => 'dashboards/driver',
];
$dashboardViews = [];
foreach ($dashboardRoles as $roleName => $expectedTemplate) {
    $template = DashboardService::templateFor($roleName);
    $dashboardViews[] = $template;
    expect($template === $expectedTemplate, $roleName . ' routes to its dashboard');
    expect(is_file(dirname(__DIR__) . '/views/' . $template . '.php'), $roleName . ' dashboard view exists');
}
expect(count(array_unique($dashboardViews)) === count($dashboardRoles), 'each role has a distinct dashboard view');

expect(!Period::overlaps('2026-10-01 10:00:00', '2026-10-03 10:00:00', '2026-10-03 10:00:00', '2026-10-05 10:00:00'), 'adjacent half-open periods do not overlap');
expect(Period::overlaps('2026-10-01 10:00:00', '2026-10-03 10:00:00', '2026-10-03 09:59:00', '2026-10-05 10:00:00'), 'one-minute overlap is a conflict');
expect(Period::overlaps('2026-10-01 10:00:00', '2026-10-05 10:00:00', '2026-10-02 10:00:00', '2026-10-03 10:00:00'), 'inner period overlaps');
expect(!Period::overlaps('2026-10-01 10:00:00', '2026-10-03 10:00:00', '2026-10-03 10:00:00', '2026-10-05 10:00:00', 0), 'buffer 0 allows adjacent');
expect(Period::overlaps('2026-10-01 10:00:00', '2026-10-03 10:00:00', '2026-10-03 10:00:00', '2026-10-05 10:00:00', 60), '60-minute turnaround blocks adjacent pickup');

$availability = new AvailabilityService();
try {
    $availability->assertValidBookingPeriod('2026-10-01 08:00:00', '2026-10-01 15:59:00');
    expect(false, 'booking shorter than 8 hours is rejected');
} catch (InvalidArgumentException) {
    expect(true, 'booking shorter than 8 hours is rejected');
}
try {
    $availability->assertValidBookingPeriod('2026-10-01 08:00:00', '2026-10-01 16:00:00');
    expect(true, 'booking of exactly 8 hours is accepted');
} catch (InvalidArgumentException) {
    expect(false, 'booking of exactly 8 hours is accepted');
}
try {
    $availability->assertValidBookingPeriod('2026-10-01 08:00:00', '2026-10-01 17:00:00');
    expect(true, 'booking longer than 8 hours is accepted');
} catch (InvalidArgumentException) {
    expect(false, 'booking longer than 8 hours is accepted');
}

$pricing = new PricingService();
expect($pricing->billableDays('2026-10-01 10:00:00', '2026-10-02 10:00:00') === 1, 'exactly 24h is 1 day');
expect($pricing->billableDays('2026-10-01 10:00:00', '2026-10-02 10:00:01') === 2, '24h+1s is 2 days');
try {
    $pricing->billableDays('2026-10-02 10:00:00', '2026-10-02 09:00:00');
    expect(false, 'return before pickup throws');
} catch (InvalidArgumentException) {
    expect(true, 'return before pickup throws');
}

expect(Money::round('10.005') === '10.01', 'half-up rounding 10.005 -> 10.01');
expect(Money::add('10.10', '0.05') === '10.15', 'money add');
expect(Money::sub('100.00', '40.50') === '59.50', 'money sub');
expect(ReportExportService::csvSafeCell('=HYPERLINK("https://example.com")') === "'=HYPERLINK(\"https://example.com\")", 'CSV export neutralizes spreadsheet formulas');
expect(ReportExportService::csvSafeCell("  @SUM(A1:A2)") === "'  @SUM(A1:A2)", 'CSV export neutralizes formulas after whitespace');
expect(ReportExportService::csvSafeCell('Vehicle RAD-001') === 'Vehicle RAD-001', 'CSV export keeps ordinary report labels readable');
expect(ReportExportService::csvSafeCell(-1250.5) === -1250.5, 'CSV export keeps numeric values typed');

$vehicle = ['daily_rate' => '35000', 'weekly_rate' => '210000', 'monthly_rate' => '700000'];
$quote = $pricing->quoteRates($vehicle, '2026-10-01 08:00:00', '2026-10-03 08:00:00', '0');
expect((int) $quote['estimated_days'] === 2, '2-day quote days');
expect($quote['estimated_amount'] === '70000.00', '2 * 35000 hire');

$quoteW = $pricing->quoteRates($vehicle, '2026-10-01 08:00:00', '2026-10-08 08:00:00', '0');
expect($quoteW['weeks'] === 1 && $quoteW['day_units'] === 0, '7 days uses weekly rate when present');
expect($quoteW['estimated_amount'] === '210000.00', 'weekly amount');

try {
    $pricing->quoteRates($vehicle, '2026-10-01 08:00:00', '2026-10-03 08:00:00', '999999');
    expect(false, 'discount over subtotal throws');
} catch (InvalidArgumentException) {
    expect(true, 'discount over subtotal throws');
}

try {
    $pricing->extras(1000, 900, null, null, null, 1);
    expect(false, 'return mileage below pickup should throw');
} catch (InvalidArgumentException) {
    expect(true, 'return mileage below pickup throws');
}

$extras = $pricing->extras(1000, 1000, '2026-10-01 08:00:00', '2026-10-03 08:00:00', '2026-10-03 08:00:00', 2);
expect($extras['extra_mileage_fee'] === '0.00' && $extras['late_return_fee'] === '0.00', 'no extra km/late when settings default 0');

expect(!in_array('confirmed', ReservationService::TRANSITIONS['pending'], true), 'pending reservations require a signed decision instead of a direct status transition');
expect(in_array('cancelled', ReservationService::TRANSITIONS['pending'], true), 'customer may cancel a pending request');
expect(!in_array('confirmed', ReservationService::TRANSITIONS['cancelled'], true), 'cancelled cannot confirm');
expect(in_array('active', RentalService::TRANSITIONS['scheduled'], true), 'scheduled can pickup');
expect(!in_array('closed', RentalService::TRANSITIONS['active'], true), 'cannot close from active without return/inspect');

$refundable = (float) Money::sub('100000', '40000');
expect($refundable === 60000.0, 'partial refund remaining');
expect((float) Money::sub('100000', '100000') === 0.0, 'full refund remaining is 0');

expect(Phone::rwandaMobile('0788123456') === '250788123456', 'normalize local MoMo number');
expect(Phone::rwandaMobile('+250 788 123 456') === '250788123456', 'normalize +250 MoMo number');
expect(Phone::hdevTel('250788123456') === '0788123456', 'HDEV expects local 07 number');
expect(Phone::rwandaMobile('12345') === null, 'reject invalid phone');
expect(HdevClient::statusOf((object) ['status' => 'success']) === 'success', 'HDEV success status mapping');
expect(HdevClient::statusOf((object) ['status' => 'pending']) === 'pending', 'HDEV pending does not complete ledger');
expect(HdevClient::statusOf((object) ['status' => 'failed']) === 'failed', 'HDEV failed status mapping');
expect(HdevClient::statusOf((object) ['status' => 'cancelled']) === 'cancelled', 'HDEV cancelled status remains distinct');
expect(HdevClient::statusOf((object) ['data' => ['status' => 'successful', 'amount' => 1000]]) === 'success', 'nested HDEV success');
expect(HdevClient::accepted((object) ['status' => 'error', 'message' => 'pending later']) === false, 'error status is not accepted even if message mentions pending');
expect(\Crms\Ui::reservationStatusLabel('pending') === 'Pending Approval', 'pending reservation has customer-facing approval label');
expect(\Crms\Ui::reservationStatusLabel('confirmed', 'successful') === 'Paid', 'fully paid reservation has paid customer-facing label');
expect(\Crms\Ui::reservationStatusLabel('confirmed', 'unpaid') === 'Approved — Awaiting Payment', 'approved unpaid reservation has payment label');
try {
    \Crms\Signature::decodePng('');
    expect(false, 'signature input must contain a valid PNG');
} catch (InvalidArgumentException) {
    expect(true, 'signature input must contain a valid PNG');
}
expect(strlen(\Crms\Signature::decodePng(testSignaturePng(true))) > 100, 'nonblank drawn PNG signature passes backend validation');
try {
    \Crms\Signature::decodePng(testSignaturePng(false));
    expect(false, 'blank PNG signature is rejected');
} catch (InvalidArgumentException) {
    expect(true, 'blank PNG signature is rejected');
}
expect(str_contains((string) file_get_contents(dirname(__DIR__) . '/database/carrental.sql'), 'approver_signature MEDIUMBLOB'), 'fresh schema stores approver signatures privately');
expect(str_contains((string) file_get_contents(dirname(__DIR__) . '/src/Services/HdevPaymentService.php'), 'get_pay($txRef)'), 'payment settlement verifies through HDEV backend status');
expect(\Crms\Services\DashboardService::templateFor('System Administrator') === 'dashboards/admin', 'admin dashboard template');
expect(\Crms\Services\DashboardService::templateFor('Business Manager') === 'dashboards/manager', 'manager dashboard template');
expect(\Crms\Services\DashboardService::templateFor('Rental Officer') === 'dashboards/rental-officer', 'officer dashboard template');
expect(\Crms\Services\DashboardService::templateFor('Accountant/Cashier') === 'dashboards/accountant', 'cashier dashboard template');
expect(\Crms\Services\DashboardService::templateFor('Vehicle Inspector/Maintenance Staff') === 'dashboards/inspector', 'inspector dashboard template');
expect(\Crms\Services\DashboardService::templateFor('Customer') === 'dashboards/customer', 'customer dashboard template');
expect(\Crms\Services\DashboardService::templateFor('Driver') === 'dashboards/driver', 'driver dashboard template');
expect(\Crms\Services\DashboardService::pageTitle('System Administrator') === 'System Overview', 'admin dashboard title');
expect(\Crms\Services\DashboardService::pageTitle('Business Manager') === 'Business Performance', 'manager dashboard title');
expect(\Crms\Services\DashboardService::pageTitle('Rental Officer') === 'Rental Operations', 'officer dashboard title');
expect(\Crms\Services\DashboardService::pageTitle('Accountant/Cashier') === 'Financial Management', 'cashier dashboard title');
expect(\Crms\Services\DashboardService::pageTitle('Vehicle Inspector/Maintenance Staff') === 'Fleet Inspection and Maintenance', 'inspector dashboard title');
expect(\Crms\Services\DashboardService::pageTitle('Customer') === 'My Account', 'customer dashboard title');
expect(\Crms\Services\DashboardService::pageTitle('Driver') === 'My Trips', 'driver dashboard title');

echo "\n=== Optional database checks ===\n";
try {
    $pdo = \Crms\Database::pdo();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    expect(in_array('reservations', $tables, true) && in_array('financial_transactions', $tables, true), 'core tables exist');
    $userColumns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    expect(in_array('must_change_password', $userColumns, true), 'user schema supports forced first-login password changes');
    $reservationColumns = $pdo->query('SHOW COLUMNS FROM reservations')->fetchAll(PDO::FETCH_COLUMN);
    expect(count(array_diff(['payment_status', 'customer_signature', 'customer_signature_user_id', 'customer_signature_captured_by', 'customer_signed_at', 'approval_note', 'approver_signature', 'decision_by', 'decision_at'], $reservationColumns)) === 0, 'reservation workflow migration columns are installed');
    $reservationIndexes = $pdo->query('SHOW INDEX FROM reservations')->fetchAll(PDO::FETCH_COLUMN, 2);
    expect(in_array('idx_res_payment_status', $reservationIndexes, true), 'reservation payment status index is installed');
    $reservationForeignKeys = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'reservations'")->fetchAll(PDO::FETCH_COLUMN);
    expect(count(array_diff(['fk_res_customer_signature_user', 'fk_res_customer_signature_captor', 'fk_res_decision_user'], $reservationForeignKeys)) === 0, 'reservation signature and decision foreign keys are installed');
    $roles = $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    expect((int) $roles === 7, 'all seven roles seeded');
    $rolePermissions = [
        'System Administrator' => ['users.manage', 'settings.manage', 'audit.view', 'drivers.manage'],
        'Business Manager' => ['reports.operational', 'reports.financial', 'rentals.manage', 'drivers.manage', 'drivers.schedule', 'reservations.view'],
        'Rental Officer' => ['reservations.manage', 'reservations.decide', 'rentals.manage'],
        'Accountant/Cashier' => ['invoices.manage', 'transactions.manage', 'reports.financial'],
        'Vehicle Inspector/Maintenance Staff' => ['maintenance.manage', 'vehicles.view'],
        'Customer' => ['reservations.view', 'invoices.view'],
        'Driver' => ['drivers.schedule'],
    ];
    foreach ($rolePermissions as $roleName => $requiredPermissions) {
        $stmt = $pdo->prepare(
            'SELECT p.permission_key FROM role_permissions rp
             JOIN roles r ON r.role_id = rp.role_id
             JOIN permissions p ON p.permission_id = rp.permission_id
             WHERE r.role_name = ?'
        );
        $stmt->execute([$roleName]);
        $assigned = $stmt->fetchAll(PDO::FETCH_COLUMN);
        expect(count(array_diff($requiredPermissions, $assigned)) === 0, $roleName . ' has required permissions');
        if ($roleName !== 'System Administrator') {
            expect(!in_array('users.manage', $assigned, true), $roleName . ' cannot manage user roles');
        }
        if ($roleName === 'Vehicle Inspector/Maintenance Staff') {
            expect(!in_array('rentals.view', $assigned, true) && !in_array('inspections.manage', $assigned, true), 'maintenance inspectors cannot browse or schedule rental work');
        }
        if ($roleName === 'Business Manager') {
            expect(!in_array('reservations.manage', $assigned, true) && !in_array('reservations.decide', $assigned, true), 'manager has read-only reservation access and cannot sign decisions');
        }
        if ($roleName === 'Rental Officer') {
            expect(in_array('reservations.view', $assigned, true), 'Rental Officer can view reservation requests they review');
        }
    }
    expect(\Crms\Services\DriverAccess::scopeSql('d') === '1=1', 'driver management scope includes System Administrator-created driver records');

    // Exercise reservation, decision, payment-gate and notification persistence inside a rollback-only fixture.
    $sessionHadUser = array_key_exists('user_id', $_SESSION);
    $previousSessionUser = $_SESSION['user_id'] ?? null;
    $pdo->beginTransaction();
    try {
        $tag = strtoupper(bin2hex(random_bytes(4)));
        $customerRole = \Crms\Database::fetch("SELECT role_id FROM roles WHERE role_name = 'Customer'");
        $managerRole = \Crms\Database::fetch("SELECT role_id FROM roles WHERE role_name = 'Business Manager'");
        $rentalRole = \Crms\Database::fetch("SELECT role_id FROM roles WHERE role_name = 'Rental Officer'");
        $category = \Crms\Database::fetch('SELECT category_id FROM vehicle_categories ORDER BY category_id LIMIT 1');
        if (!$customerRole || !$managerRole || !$rentalRole || !$category) {
            throw new RuntimeException('Workflow fixture needs seeded roles and a vehicle category.');
        }
        $customerEmail = 'workflow-customer-' . strtolower($tag) . '@example.invalid';
        $managerEmail = 'workflow-manager-' . strtolower($tag) . '@example.invalid';
        \Crms\Database::execute(
            'INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,status) VALUES (?,?,?,?,?,?,\'active\')',
            [$customerRole['role_id'], 'Workflow', 'Customer', $customerEmail, '123', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]
        );
        $customerUserId = (int) \Crms\Database::lastId();
        \Crms\Database::execute(
            'INSERT INTO customers (linked_user_id,customer_code,first_name,last_name,phone,email,created_by) VALUES (?,?,?,?,?,?,?)',
            [$customerUserId, 'TST-' . $tag, 'Workflow', 'Customer', '123', $customerEmail, $customerUserId]
        );
        $customerId = (int) \Crms\Database::lastId();
        \Crms\Database::execute(
            'INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,status) VALUES (?,?,?,?,?,?,\'active\')',
            [$managerRole['role_id'], 'Workflow', 'Manager', $managerEmail, '123', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]
        );
        $managerUserId = (int) \Crms\Database::lastId();
        $rentalEmail = 'workflow-rental-' . strtolower($tag) . '@example.invalid';
        \Crms\Database::execute(
            'INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,status) VALUES (?,?,?,?,?,?,\'active\')',
            [$rentalRole['role_id'], 'Workflow', 'Rental Officer', $rentalEmail, '123', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]
        );
        $rentalUserId = (int) \Crms\Database::lastId();
        \Crms\Database::execute(
            'INSERT INTO vehicles (category_id,registration_no,make,model,daily_rate,status,created_by) VALUES (?,?,?,?,?,\'available\',?)',
            [$category['category_id'], 'TST-' . $tag, 'Workflow', 'Test Car', '10000.00', $managerUserId]
        );
        $vehicleId = (int) \Crms\Database::lastId();

        $_SESSION['user_id'] = $customerUserId;
        $year = (int) date('Y') + 5;
        $pickup = sprintf('%04d-07-01 10:00:00', $year);
        $returnAt = sprintf('%04d-07-03 10:00:00', $year);
        $signature = testSignaturePng(true);
        $createRequest = static function () use ($customerId, $vehicleId, $pickup, $returnAt, $signature): array {
            return (new ReservationService())->create([
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'pickup_datetime' => $pickup,
                'return_datetime' => $returnAt,
                'pickup_location' => 'Test pickup',
                'return_location' => 'Test return',
                'discount_amount' => '0',
                'customer_notes' => 'Rollback-only workflow fixture',
                'internal_notes' => null,
                'customer_signature' => $signature,
                'reservation_reviewed' => '1',
            ], false);
        };
        $first = $createRequest();
        $second = $createRequest();
        expect($first['status'] === 'pending' && $second['status'] === 'pending', 'signed customer reservations persist as pending approval');
        expect(!empty($first['customer_signature']) && !empty($first['customer_signed_at']), 'customer signature and signing time persist with reservation');
        try {
            (new ReservationService())->create(['customer_id' => $customerId + 1], false);
            expect(false, 'customer cannot create a reservation for another customer');
        } catch (InvalidArgumentException) {
            expect(true, 'customer cannot create a reservation for another customer');
        }
        try {
            (new ReservationService())->decide((int) $first['reservation_id'], 'approve', 'Unauthorized approval attempt', $signature);
            expect(false, 'customer cannot approve a reservation');
        } catch (InvalidArgumentException) {
            expect(true, 'customer cannot approve a reservation');
        }
        $firstInvoice = \Crms\Database::fetch("SELECT * FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%'", [$first['reservation_id']]);
        $secondInvoice = \Crms\Database::fetch("SELECT * FROM invoices WHERE reservation_id = ? AND notes LIKE 'Booking prepayment:%'", [$second['reservation_id']]);
        expect($firstInvoice && $firstInvoice['status'] === 'draft' && $secondInvoice && $secondInvoice['status'] === 'draft', 'pending reservation invoices remain unpaid drafts');
        try {
            \Crms\Services\HdevPaymentService::assertBookingPaymentAllowed((int) $first['reservation_id']);
            expect(false, 'pending reservations fail the backend payment eligibility gate');
        } catch (InvalidArgumentException) {
            expect(true, 'pending reservations fail the backend payment eligibility gate');
        }
        try {
            (new ReservationService())->transition((int) $first['reservation_id'], 'confirmed');
            expect(false, 'direct reservation confirmation is rejected');
        } catch (InvalidArgumentException) {
            expect(true, 'direct reservation confirmation is rejected');
        }

        $_SESSION['user_id'] = $managerUserId;
        expect(\Crms\Rbac::can('reservations.view') && !\Crms\Rbac::can('reservations.manage'), 'manager can view reservations without edit or decision access');
        try {
            $createRequest();
            expect(false, 'manager cannot submit a reservation with a staff-provided customer signature');
        } catch (InvalidArgumentException) {
            expect(true, 'manager cannot submit a reservation with a staff-provided customer signature');
        }
        try {
            (new ReservationService())->decide((int) $first['reservation_id'], 'approve', 'Manager must not sign decisions', $signature);
            expect(false, 'manager cannot sign or record reservation decisions');
        } catch (InvalidArgumentException) {
            expect(true, 'manager cannot sign or record reservation decisions');
        }
        $_SESSION['user_id'] = $rentalUserId;
        try {
            (new ReservationService())->decide((int) $second['reservation_id'], 'reject', '', $signature);
            expect(false, 'approval decision requires a note');
        } catch (InvalidArgumentException) {
            expect(true, 'approval decision requires a note');
        }
        (new ReservationService())->decide((int) $first['reservation_id'], 'approve', 'Workflow test approval', $signature);
        $approved = \Crms\Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$first['reservation_id']]);
        $firstInvoice = \Crms\Database::fetch('SELECT * FROM invoices WHERE invoice_id = ?', [$firstInvoice['invoice_id']]);
        expect($approved['status'] === 'confirmed' && $approved['decision_by'] == $rentalUserId && !empty($approved['approver_signature']), 'Rental Officer approval records reviewer, note, date, and signature');
        expect($firstInvoice['status'] === 'issued', 'approval issues the booking invoice');
        \Crms\Services\HdevPaymentService::assertBookingPaymentAllowed((int) $first['reservation_id']);
        expect(true, 'signed approved reservations pass the backend eligibility gate');
        try {
            (new ReservationService())->decide((int) $first['reservation_id'], 'reject', 'Conflicting second decision', $signature);
            expect(false, 'duplicate approval decisions are rejected');
        } catch (InvalidArgumentException) {
            expect(true, 'duplicate approval decisions are rejected');
        }
        try {
            (new ReservationService())->decide((int) $second['reservation_id'], 'approve', 'Conflict test', $signature);
            expect(false, 'overlapping reservation approval is rejected');
        } catch (InvalidArgumentException) {
            expect(true, 'overlapping reservation approval is rejected');
        }
        $pendingSecond = \Crms\Database::fetch('SELECT status FROM reservations WHERE reservation_id = ?', [$second['reservation_id']]);
        expect($pendingSecond['status'] === 'pending', 'conflicting approval leaves the second request pending');
        (new ReservationService())->decide((int) $second['reservation_id'], 'reject', 'Vehicle is already booked for those dates', $signature);
        $rejected = \Crms\Database::fetch('SELECT status,payment_status,approval_note FROM reservations WHERE reservation_id = ?', [$second['reservation_id']]);
        expect($rejected['status'] === 'rejected' && $rejected['payment_status'] === 'cancelled' && $rejected['approval_note'] !== '', 'rejection records decision and blocks reservation payment');
        try {
            \Crms\Services\HdevPaymentService::assertBookingPaymentAllowed((int) $second['reservation_id']);
            expect(false, 'rejected reservations fail the backend payment eligibility gate');
        } catch (InvalidArgumentException) {
            expect(true, 'rejected reservations fail the backend payment eligibility gate');
        }
        $postedPayments = \Crms\Database::fetch(
            "SELECT COUNT(*) AS total FROM financial_transactions WHERE invoice_id IN (?,?)",
            [$firstInvoice['invoice_id'], $secondInvoice['invoice_id']]
        );
        expect((int) $postedPayments['total'] === 0, 'eligibility checks create no payment ledger rows');

        $_SESSION['user_id'] = $customerUserId;
        $notifications = new \Crms\Services\NotificationService();
        expect($notifications->unreadCount($customerUserId, $customerId) > 0, 'customer reservation and decision notifications persist as unread');
        $customerNotes = $notifications->forUser($customerUserId, $customerId);
        if ($customerNotes) {
            $notifications->markRead((int) $customerNotes[0]['notification_id'], $customerUserId, $customerId);
            $readNote = \Crms\Database::fetch('SELECT status,read_at FROM notifications WHERE notification_id = ?', [$customerNotes[0]['notification_id']]);
            expect($readNote['status'] === 'read' && !empty($readNote['read_at']), 'customer can persist a notification read state');
        } else {
            expect(false, 'customer can persist a notification read state');
        }
        $_SESSION['user_id'] = $managerUserId;
        expect($notifications->unreadCount($managerUserId, null) > 0, 'manager receives persisted reservation workflow notifications');

        $adminRole = \Crms\Database::fetch("SELECT role_id FROM roles WHERE role_name='System Administrator'");
        $inspectorRole = \Crms\Database::fetch("SELECT role_id FROM roles WHERE role_name='Vehicle Inspector/Maintenance Staff'");
        if (!$adminRole || !$inspectorRole) {
            throw new RuntimeException('Workflow fixture needs the System Administrator and inspector roles.');
        }
        $adminEmail = 'workflow-admin-' . strtolower($tag) . '@example.invalid';
        \Crms\Database::execute(
            'INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,status) VALUES (?,?,?,?,?,?,\'active\')',
            [$adminRole['role_id'], 'Workflow', 'Administrator', $adminEmail, '123', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]
        );
        $adminUserId = (int) \Crms\Database::lastId();
        $inspectorEmail = 'workflow-inspector-' . strtolower($tag) . '@example.invalid';
        \Crms\Database::execute(
            'INSERT INTO users (role_id,first_name,last_name,email,phone,password_hash,status) VALUES (?,?,?,?,?,?,\'active\')',
            [$inspectorRole['role_id'], 'Workflow', 'Inspector', $inspectorEmail, '123', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]
        );
        $inspectorUserId = (int) \Crms\Database::lastId();
        \Crms\Database::execute(
            'INSERT INTO drivers (first_name,last_name,phone,email,driving_license_number,status,created_by) VALUES (?,?,?,?,?,\'active\',?)',
            ['AdminCreated', 'Driver', '123', 'admin-driver-' . strtolower($tag) . '@example.invalid', 'LIC-' . $tag, $adminUserId]
        );
        $adminCreatedDriverId = (int) \Crms\Database::lastId();
        expect(\Crms\Rbac::can('drivers.manage') && \Crms\Services\DriverAccess::canAccess($adminCreatedDriverId), 'manager can access System Administrator-created drivers');
        $updatedDriver = (new \Crms\Services\DriverService())->save([
            'first_name' => 'Managed', 'last_name' => 'ByManager', 'phone' => '123', 'email' => 'managed-driver-' . strtolower($tag) . '@example.invalid',
            'driving_license_number' => 'LIC-' . $tag, 'license_expiry_date' => '', 'status' => 'active', 'notes' => '', 'user_id' => '',
        ], $adminCreatedDriverId);
        expect($updatedDriver['first_name'] === 'Managed' && (int)$updatedDriver['created_by'] === $adminUserId, 'manager can edit an administrator-created driver without changing its audit ownership');

        $maintenanceService = new \Crms\Services\MaintenanceService();
        $maintenanceInput = [
            'vehicle_id' => (string) $vehicleId, 'maintenance_type' => 'repair', 'title' => 'Workflow safety repair',
            'start_date' => date('Y-m-d'), 'expected_completion_date' => date('Y-m-d'), 'next_service_date' => '',
            'cost' => '0', 'odometer_reading' => '', 'description' => 'Rollback-only maintenance fixture',
            'service_provider' => '', 'notes' => '',
        ];
        $_SESSION['user_id'] = $inspectorUserId;
        expect(\Crms\Rbac::can('maintenance.manage') && \Crms\Rbac::can('maintenance.schedule')
            && !\Crms\Rbac::can('drivers.manage') && !\Crms\Rbac::can('inspections.manage'),
            'maintenance staff can schedule and manage service work without driver or rental scheduling access');
        $maintenanceJob = $maintenanceService->create($maintenanceInput);
        expect((int) $maintenanceJob['vehicle_id'] === $vehicleId, 'maintenance staff can schedule a service job');

        $_SESSION['user_id'] = $managerUserId;
        try {
            $maintenanceService->create($maintenanceInput);
            expect(false, 'business manager cannot schedule maintenance jobs');
        } catch (InvalidArgumentException) {
            expect(true, 'business manager cannot schedule maintenance jobs');
        }
        $_SESSION['user_id'] = $inspectorUserId;
        try {
            $maintenanceService->updateVehicleReadiness($vehicleId, 'available', 'Premature release');
            expect(false, 'maintenance vehicle cannot be released while a job is open');
        } catch (InvalidArgumentException) {
            expect(true, 'maintenance vehicle cannot be released while a job is open');
        }
        $maintenanceService->updateStatus((int)$maintenanceJob['maintenance_id'], 'in_progress');
        try {
            $maintenanceService->updateStatus((int)$maintenanceJob['maintenance_id'], 'completed', '', false, 'available');
            expect(false, 'maintenance completion requires an inspection note');
        } catch (InvalidArgumentException) {
            expect(true, 'maintenance completion requires an inspection note');
        }
        $maintenanceService->updateStatus((int)$maintenanceJob['maintenance_id'], 'completed', 'Repair complete; road test still required.', false, 'maintenance');
        $heldVehicle = \Crms\Database::fetch('SELECT status FROM vehicles WHERE vehicle_id=?', [$vehicleId]);
        expect($heldVehicle['status'] === 'maintenance', 'inspector can complete work and keep the vehicle in maintenance');
        $maintenanceService->updateVehicleReadiness($vehicleId, 'available', 'Road test passed and vehicle is safe.');
        $reservedAfterRelease = \Crms\Database::fetch('SELECT status FROM vehicles WHERE vehicle_id=?', [$vehicleId]);
        expect($reservedAfterRelease['status'] === 'reserved', 'readiness release preserves an existing confirmed reservation status');

        \Crms\Database::execute(
            'INSERT INTO vehicles (category_id,registration_no,make,model,daily_rate,status,created_by) VALUES (?,?,?,?,?,\'available\',?)',
            [$category['category_id'], 'MNT-' . $tag, 'Workflow', 'Service Car', '10000.00', $managerUserId]
        );
        $releaseVehicleId = (int) \Crms\Database::lastId();
        $maintenanceInput['vehicle_id'] = (string) $releaseVehicleId;
        $_SESSION['user_id'] = $inspectorUserId;
        $maintenanceJob = $maintenanceService->create($maintenanceInput);
        $maintenanceService->updateStatus((int)$maintenanceJob['maintenance_id'], 'in_progress');
        $maintenanceService->updateStatus((int)$maintenanceJob['maintenance_id'], 'completed', 'Inspection passed; ready for hire.', false, 'available');
        $releasedVehicle = \Crms\Database::fetch('SELECT status FROM vehicles WHERE vehicle_id=?', [$releaseVehicleId]);
        expect($releasedVehicle['status'] === 'available', 'inspector can return a safe vehicle to available status');
    } catch (Throwable $workflowError) {
        expect(false, 'database reservation workflow integration: ' . $workflowError->getMessage());
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($sessionHadUser) {
            $_SESSION['user_id'] = $previousSessionUser;
        } else {
            unset($_SESSION['user_id']);
        }
    }
} catch (Throwable $e) {
    echo "SKIP  database not available (" . $e->getMessage() . ")\n";
}

echo "\n{$passed} passed, {$failed} failed\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}
exit($failed === 0 ? 0 : 1);
