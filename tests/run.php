<?php
declare(strict_types=1);

/**
 * Run: C:\xampp\php\php.exe tests/run.php
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use Crms\Money;
use Crms\Period;
use Crms\Phone;
use Crms\Services\HdevClient;
use Crms\Services\PricingService;
use Crms\Services\ReservationService;
use Crms\Services\RentalService;
use Crms\Services\DashboardService;

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

expect(in_array('confirmed', ReservationService::TRANSITIONS['pending'], true), 'pending can confirm');
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
expect(HdevClient::statusOf((object) ['data' => ['status' => 'successful', 'amount' => 1000]]) === 'success', 'nested HDEV success');
expect(HdevClient::accepted((object) ['status' => 'error', 'message' => 'pending later']) === false, 'error status is not accepted even if message mentions pending');

echo "\n=== Optional database checks ===\n";
try {
    $pdo = \Crms\Database::pdo();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    expect(in_array('reservations', $tables, true) && in_array('financial_transactions', $tables, true), 'core tables exist');
    $roles = $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    expect((int) $roles === 7, 'all seven roles seeded');
    $rolePermissions = [
        'System Administrator' => ['users.manage', 'settings.manage', 'audit.view'],
        'Business Manager' => ['reports.operational', 'reports.financial', 'rentals.manage'],
        'Rental Officer' => ['reservations.manage', 'rentals.manage', 'drivers.manage'],
        'Accountant/Cashier' => ['invoices.manage', 'transactions.manage', 'reports.financial'],
        'Vehicle Inspector/Maintenance Staff' => ['maintenance.manage', 'inspections.manage'],
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
    }
} catch (Throwable $e) {
    echo "SKIP  database not available (" . $e->getMessage() . ")\n";
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
