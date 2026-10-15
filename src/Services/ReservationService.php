<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Settings;
use InvalidArgumentException;
use PDO;

final class ReservationService
{
    private AvailabilityService $availability;
    private PricingService $pricing;

    /** @var array<string,list<string>> */
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled', 'rejected'],
        'confirmed' => ['cancelled', 'completed', 'no_show'],
        'cancelled' => [],
        'rejected' => [],
        'completed' => [],
        'no_show' => [],
    ];

    public function __construct(?AvailabilityService $availability = null, ?PricingService $pricing = null)
    {
        $this->availability = $availability ?? new AvailabilityService();
        $this->pricing = $pricing ?? new PricingService();
    }

    public function create(array $input, bool $asStaff): array
    {
        return Database::transaction(function (PDO $pdo) use ($input, $asStaff) {
            $customerId = (int) $input['customer_id'];
            $vehicleId = (int) $input['vehicle_id'];
            $pickup = $input['pickup_datetime'];
            $returnAt = $input['return_datetime'];
            $discount = $input['discount_amount'] ?? '0';

            $customer = Database::fetch('SELECT * FROM customers WHERE customer_id = ? FOR UPDATE', [$customerId]);
            if (!$customer) {
                throw new InvalidArgumentException('Customer not found.');
            }
            if ($customer['status'] === 'blacklisted') {
                throw new InvalidArgumentException('This customer is not allowed to book.');
            }
            if (!empty($customer['license_expiry_date']) && $customer['license_expiry_date'] < date('Y-m-d')) {
                throw new InvalidArgumentException('Driving licence has expired.');
            }

            $vehicle = $this->availability->lockVehicle($pdo, $vehicleId);
            $block = $this->availability->isOperationallyBookable($vehicle);
            if ($block) {
                throw new InvalidArgumentException($block);
            }
            $conflict = $this->availability->hasConflict($vehicleId, $pickup, $returnAt);
            if ($conflict) {
                throw new InvalidArgumentException($conflict);
            }

            $quote = $this->pricing->quoteRates($vehicle, $pickup, $returnAt, (string) $discount);
            $code = $this->nextCode('RSV');
            $status = 'pending';
            $needConfirm = Settings::get('reservation_confirmation_required', 'true') === 'true';
            $confirmedBy = null;
            $confirmedAt = null;
            if ($asStaff && !$needConfirm) {
                $status = 'confirmed';
                $confirmedBy = Auth::id();
                $confirmedAt = date('Y-m-d H:i:s');
            }

            Database::execute(
                'INSERT INTO reservations (
                    reservation_code, customer_id, vehicle_id, pickup_datetime, return_datetime,
                    pickup_location, return_location, agreed_daily_rate, estimated_days, estimated_amount,
                    discount_amount, tax_amount, security_deposit, status, customer_notes, internal_notes, created_by,
                    confirmed_by, confirmed_at
                 ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $code, $customerId, $vehicleId, $pickup, $returnAt,
                    $input['pickup_location'] ?? null, $input['return_location'] ?? null,
                    $quote['agreed_daily_rate'], $quote['estimated_days'], $quote['estimated_amount'],
                    $quote['discount_amount'], $quote['tax_amount'], $quote['security_deposit'],
                    $status, $input['customer_notes'] ?? null, $input['internal_notes'] ?? null, Auth::id(),
                    $confirmedBy, $confirmedAt,
                ]
            );
            $id = (int) Database::lastId();
            if ($status === 'confirmed') {
                $this->syncVehicleStatus($vehicleId);
            }
            Audit::log('reservation.created', 'reservation', (string) $id, $code . ' ' . $status);
            (new NotificationService())->notifyUser(
                Auth::id(),
                $customerId,
                'reservation_created',
                'Reservation ' . $code,
                'Reservation ' . $code . ' is ' . $status . '.'
            );
            return Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$id]);
        });
    }

    public function transition(int $id, string $to, ?string $reason = null): void
    {
        Database::transaction(function (PDO $pdo) use ($id, $to, $reason) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $res = $stmt->fetch();
            if (!$res) {
                throw new InvalidArgumentException('Reservation not found.');
            }
            $from = $res['status'];
            if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
                throw new InvalidArgumentException("Cannot change reservation from {$from} to {$to}.");
            }
            if ($to === 'confirmed') {
                $vehicle = $this->availability->lockVehicle($pdo, (int) $res['vehicle_id']);
                $block = $this->availability->isOperationallyBookable($vehicle);
                if ($block) {
                    throw new InvalidArgumentException($block);
                }
                $conflict = $this->availability->hasConflict((int) $res['vehicle_id'], $res['pickup_datetime'], $res['return_datetime'], (int) $res['reservation_id']);
                if ($conflict) {
                    throw new InvalidArgumentException($conflict);
                }
            }
            $sql = 'UPDATE reservations SET status = ?';
            $params = [$to];
            if ($to === 'confirmed') {
                $sql .= ', confirmed_by = ?, confirmed_at = NOW()';
                $params[] = Auth::id();
            }
            if ($to === 'cancelled' || $to === 'rejected') {
                $sql .= ', cancelled_at = NOW(), cancellation_reason = ?';
                $params[] = $reason;
            }
            $sql .= ' WHERE reservation_id = ?';
            $params[] = $id;
            Database::execute($sql, $params);
            $this->syncVehicleStatus((int) $res['vehicle_id']);
            Audit::log('reservation.status', 'reservation', (string) $id, "{$from} -> {$to}", ['status' => $from], ['status' => $to]);
        });
    }

    public function syncVehicleStatus(int $vehicleId): void
    {
        $v = Database::fetch('SELECT status FROM vehicles WHERE vehicle_id = ?', [$vehicleId]);
        if (!$v || in_array($v['status'], ['inactive', 'maintenance'], true)) {
            return;
        }
        $activeRental = Database::fetch(
            "SELECT rental_id FROM rentals WHERE vehicle_id = ? AND status IN ('active','overdue') LIMIT 1",
            [$vehicleId]
        );
        if ($activeRental) {
            Database::execute("UPDATE vehicles SET status = 'rented' WHERE vehicle_id = ?", [$vehicleId]);
            return;
        }
        $confirmed = Database::fetch(
            "SELECT reservation_id FROM reservations WHERE vehicle_id = ? AND status = 'confirmed'
             AND pickup_datetime <= NOW() AND return_datetime > NOW() LIMIT 1",
            [$vehicleId]
        );
        if ($confirmed) {
            Database::execute("UPDATE vehicles SET status = 'reserved' WHERE vehicle_id = ?", [$vehicleId]);
            return;
        }
        $future = Database::fetch(
            "SELECT reservation_id FROM reservations WHERE vehicle_id = ? AND status = 'confirmed' AND return_datetime > NOW() LIMIT 1",
            [$vehicleId]
        );
        Database::execute(
            'UPDATE vehicles SET status = ? WHERE vehicle_id = ?',
            [$future ? 'reserved' : 'available', $vehicleId]
        );
    }

    public function nextCode(string $prefix): string
    {
        $n = strtoupper(bin2hex(random_bytes(3)));
        return sprintf('%s-%s-%s', $prefix, date('Ymd'), $n);
    }
}
