<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Money;
use InvalidArgumentException;
use PDO;

final class RentalService
{
    /** @var array<string,list<string>> */
    public const TRANSITIONS = [
        'scheduled' => ['active', 'cancelled'],
        'active' => ['returned'],
        'overdue' => ['returned'],
        'returned' => ['inspected'],
        'inspected' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private AvailabilityService $availability = new AvailabilityService(),
        private PricingService $pricing = new PricingService(),
        private ReservationService $reservations = new ReservationService()
    ) {
    }

    public function createFromReservation(int $reservationId, string $terms): array
    {
        return Database::transaction(function (PDO $pdo) use ($reservationId, $terms) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
            $stmt->execute([$reservationId]);
            $res = $stmt->fetch();
            if (!$res) {
                throw new InvalidArgumentException('Reservation not found.');
            }
            if ($res['status'] !== 'confirmed') {
                throw new InvalidArgumentException('Only confirmed reservations can become rentals.');
            }
            $exists = Database::fetch('SELECT rental_id FROM rentals WHERE reservation_id = ?', [$reservationId]);
            if ($exists) {
                throw new InvalidArgumentException('A rental already exists for this reservation.');
            }
            $vehicle = $this->availability->lockVehicle($pdo, (int) $res['vehicle_id']);
            $block = $this->availability->isOperationallyBookable($vehicle);
            if ($block) {
                throw new InvalidArgumentException($block);
            }
            $conflict = $this->availability->hasConflict((int) $res['vehicle_id'], $res['pickup_datetime'], $res['return_datetime'], (int) $res['reservation_id']);
            if ($conflict) {
                throw new InvalidArgumentException($conflict);
            }
            $number = (new ReservationService())->nextCode('RNT');
            $base = Money::add($res['estimated_amount'], '0');
            $final = Money::add(Money::sub($base, $res['discount_amount']), $res['tax_amount']);
            Database::execute(
                'INSERT INTO rentals (
                    rental_number, reservation_id, customer_id, vehicle_id, agreement_terms, agreement_status,
                    expected_return_datetime, base_amount, discount_amount, tax_amount, final_amount, status, handled_by
                 ) VALUES (?,?,?,?,?,\'issued\',?,?,?,?,?,\'scheduled\',?)',
                [
                    $number, $reservationId, $res['customer_id'], $res['vehicle_id'], $terms,
                    $res['return_datetime'], $base, $res['discount_amount'], $res['tax_amount'], $final, Auth::id(),
                ]
            );
            $id = (int) Database::lastId();
            Audit::log('rental.created', 'rental', (string) $id, $number);
            return Database::fetch('SELECT * FROM rentals WHERE rental_id = ?', [$id]);
        });
    }

    public function pickup(int $rentalId, array $data): void
    {
        Database::transaction(function (PDO $pdo) use ($rentalId, $data) {
            $rental = $this->lockRental($pdo, $rentalId);
            if ($rental['status'] !== 'scheduled') {
                throw new InvalidArgumentException('Pickup is only allowed from scheduled rentals.');
            }
            $reservation = Database::fetch('SELECT pickup_datetime FROM reservations WHERE reservation_id = ?', [(int) $rental['reservation_id']]);
            $actualPickup = (string) ($data['actual_pickup_datetime'] ?? '');
            $this->availability->assertValidPeriod($actualPickup, (string) $rental['expected_return_datetime']);
            if (!$reservation || $actualPickup < (string) $reservation['pickup_datetime']) {
                throw new InvalidArgumentException('Pickup cannot be earlier than the confirmed reservation pickup time.');
            }
            if ($actualPickup > (string) $pdo->query('SELECT NOW()')->fetchColumn()) {
                throw new InvalidArgumentException('Pickup time cannot be in the future.');
            }
            $fuel = trim((string) ($data['pickup_fuel_percent'] ?? ''));
            if ($fuel !== '' && (!is_numeric($fuel) || (float) $fuel < 0 || (float) $fuel > 100)) {
                throw new InvalidArgumentException('Pickup fuel level must be between 0 and 100 percent.');
            }
            $mileageInput = trim((string) ($data['pickup_mileage'] ?? ''));
            if (!preg_match('/^\d+$/', $mileageInput)) {
                throw new InvalidArgumentException('Enter a whole-number pickup mileage of zero or more.');
            }
            $mileage = (int) $mileageInput;
            $vehicle = $this->availability->lockVehicle($pdo, (int) $rental['vehicle_id']);
            if ($mileage < (int) $vehicle['current_mileage']) {
                throw new InvalidArgumentException('Pickup mileage cannot be below the vehicle odometer.');
            }
            Database::execute(
                'UPDATE rentals SET status = \'active\', agreement_status = \'active\', signed_at = COALESCE(signed_at, NOW()),
                    actual_pickup_datetime = ?, pickup_mileage = ?, pickup_fuel_percent = ?, pickup_notes = ?, handled_by = ?
                 WHERE rental_id = ?',
                [
                    $actualPickup,
                    $mileage,
                    $fuel !== '' ? $fuel : null,
                    $data['pickup_notes'] ?? null,
                    Auth::id(),
                    $rentalId,
                ]
            );
            Database::execute(
                'UPDATE vehicles SET status = \'rented\', current_mileage = ?, fuel_level_percent = ? WHERE vehicle_id = ?',
                [$mileage, $fuel !== '' ? $fuel : $vehicle['fuel_level_percent'], $rental['vehicle_id']]
            );
            $this->addInspection($rentalId, 'pickup', $mileage, $data['pickup_fuel_percent'] ?? null, $data);
            Audit::log('rental.pickup', 'rental', (string) $rentalId, $rental['rental_number']);
        });
    }

    public function returnVehicle(int $rentalId, array $data): void
    {
        Database::transaction(function (PDO $pdo) use ($rentalId, $data) {
            $rental = $this->lockRental($pdo, $rentalId);
            if (!in_array($rental['status'], ['active', 'overdue'], true)) {
                throw new InvalidArgumentException('Return is only allowed for active or overdue rentals.');
            }
            $mileageInput = trim((string) ($data['return_mileage'] ?? ''));
            if (!preg_match('/^\d+$/', $mileageInput)) {
                throw new InvalidArgumentException('Enter a whole-number return mileage of zero or more.');
            }
            $returnMileage = (int) $mileageInput;
            $pickupMileage = (int) $rental['pickup_mileage'];
            if ($returnMileage < $pickupMileage) {
                throw new InvalidArgumentException('Return mileage cannot be below pickup mileage.');
            }
            if (empty($rental['actual_pickup_datetime'])) {
                throw new InvalidArgumentException('This rental has no recorded pickup time.');
            }
            $actualReturn = (string) ($data['actual_return_datetime'] ?? '');
            $this->availability->assertValidPeriod((string) $rental['actual_pickup_datetime'], $actualReturn);
            $fuel = trim((string) ($data['return_fuel_percent'] ?? ''));
            if ($fuel !== '' && (!is_numeric($fuel) || (float) $fuel < 0 || (float) $fuel > 100)) {
                throw new InvalidArgumentException('Return fuel level must be between 0 and 100 percent.');
            }
            $manualCharges = [];
            foreach (['damage_fee' => 'Damage', 'cleaning_fee' => 'Cleaning', 'other_charges' => 'Additional'] as $key => $label) {
                $raw = trim((string) ($data[$key] ?? '0'));
                if (!is_numeric($raw) || !is_finite((float) $raw) || (float) $raw < 0 || (float) $raw > 9999999999.99) {
                    throw new InvalidArgumentException($label . ' charge must be a non-negative amount.');
                }
                $manualCharges[$key] = Money::round($raw);
            }
            $returnNotes = trim((string) ($data['return_notes'] ?? ''));
            if (array_sum(array_map('floatval', $manualCharges)) > 0 && $returnNotes === '') {
                throw new InvalidArgumentException('Add handover notes to explain any damage, cleaning or additional charges for accountant review.');
            }
            $res = Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$rental['reservation_id']]);
            $days = $this->pricing->billableDays($res['pickup_datetime'], $res['return_datetime']);
            $extras = $this->pricing->extras(
                $pickupMileage,
                $returnMileage,
                $rental['actual_pickup_datetime'],
                $rental['expected_return_datetime'],
                $actualReturn,
                $days
            );
            $damage = $manualCharges['damage_fee'];
            $cleaning = $manualCharges['cleaning_fee'];
            $other = $manualCharges['other_charges'];
            $final = Money::round(
                (float) $rental['base_amount']
                + (float) $extras['extra_mileage_fee']
                + (float) $extras['late_return_fee']
                + (float) $damage
                + (float) $cleaning
                + (float) $other
                - (float) $rental['discount_amount']
                + (float) $rental['tax_amount']
            );
            Database::execute(
                'UPDATE rentals SET status = \'returned\',
                    actual_return_datetime = ?, return_mileage = ?, return_fuel_percent = ?, return_notes = ?,
                    extra_mileage_fee = ?, late_return_fee = ?, damage_fee = ?, cleaning_fee = ?, other_charges = ?,
                    final_amount = ?, handled_by = ?
                 WHERE rental_id = ?',
                [
                    $actualReturn, $returnMileage,
                    $fuel !== '' ? $fuel : null,
                    $returnNotes !== '' ? $returnNotes : null,
                    $extras['extra_mileage_fee'], $extras['late_return_fee'], $damage, $cleaning, $other,
                    $final, Auth::id(), $rentalId,
                ]
            );
            Database::execute(
                'UPDATE vehicles SET current_mileage = ?, fuel_level_percent = ? WHERE vehicle_id = ?',
                [$returnMileage, $fuel !== '' ? $fuel : null, $rental['vehicle_id']]
            );
            $this->reservations->syncVehicleStatus((int) $rental['vehicle_id']);
            Audit::log(
                'rental.return',
                'rental',
                (string) $rentalId,
                $rental['rental_number'] . ' charges recorded for accountant review',
                null,
                [
                    'late_return_fee' => $extras['late_return_fee'],
                    'extra_mileage_fee' => $extras['extra_mileage_fee'],
                    'damage_fee' => $damage,
                    'cleaning_fee' => $cleaning,
                    'other_charges' => $other,
                    'return_notes' => $returnNotes,
                ]
            );
        });
    }

    public function cancelScheduled(int $rentalId, string $reason): array
    {
        $reason = trim($reason);
        $reasonLength = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);
        if ($reason === '' || $reasonLength > 500) {
            throw new InvalidArgumentException('Enter a cancellation reason of up to 500 characters.');
        }

        $cancelled = Database::transaction(function (PDO $pdo) use ($rentalId, $reason) {
            $lookup = $pdo->prepare('SELECT reservation_id FROM rentals WHERE rental_id = ?');
            $lookup->execute([$rentalId]);
            $reservationId = (int) $lookup->fetchColumn();
            if ($reservationId <= 0) {
                throw new InvalidArgumentException('Rental not found.');
            }

            // Use the same reservation-first lock order as confirmation and agreement creation.
            $reservationStmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id = ? FOR UPDATE');
            $reservationStmt->execute([$reservationId]);
            $reservation = $reservationStmt->fetch();
            $rental = $this->lockRental($pdo, $rentalId);
            if (!$reservation || (int) $rental['reservation_id'] !== $reservationId) {
                throw new InvalidArgumentException('Reservation or rental link is no longer valid.');
            }
            if ($rental['status'] !== 'scheduled') {
                throw new InvalidArgumentException('Only a rental that has not been picked up can be cancelled. Active rentals must be returned.');
            }
            if ($reservation['status'] !== 'confirmed') {
                throw new InvalidArgumentException('Only a confirmed reservation can be cancelled with its rental agreement.');
            }

            Database::execute(
                "UPDATE rentals SET status = 'cancelled', agreement_status = 'terminated', handled_by = ? WHERE rental_id = ?",
                [Auth::id(), $rentalId]
            );
            Database::execute(
                'UPDATE reservations SET status = \'cancelled\', cancelled_at = NOW(), cancellation_reason = ? WHERE reservation_id = ?',
                [$reason, $reservationId]
            );
            $this->reservations->syncVehicleStatus((int) $rental['vehicle_id']);
            Audit::log('rental.cancelled', 'rental', (string) $rentalId, $reason, ['status' => 'scheduled'], ['status' => 'cancelled', 'reason' => $reason]);
            Audit::log('reservation.status', 'reservation', (string) $reservationId, 'confirmed -> cancelled with rental agreement', ['status' => 'confirmed'], ['status' => 'cancelled', 'reason' => $reason]);
            return ['rental' => $rental, 'reservation' => $reservation];
        });

        $message = 'Reservation ' . $cancelled['reservation']['reservation_code'] . ' and rental agreement ' . $cancelled['rental']['rental_number'] . ' were cancelled. Reason: ' . $reason;
        $notifications = new NotificationService();
        $notifications->notifyUser(
            Auth::id(), (int) $cancelled['rental']['customer_id'], 'reservation_status',
            'Reservation ' . $cancelled['reservation']['reservation_code'], $message,
            'reservation', (int) $cancelled['reservation']['reservation_id']
        );
        $notifications->notifyRentalOfficers(
            'reservation_status', 'Reservation ' . $cancelled['reservation']['reservation_code'], $message,
            'reservation', (int) $cancelled['reservation']['reservation_id'], Auth::id()
        );
        (new SmsService())->sendToCustomer(
            (int) $cancelled['rental']['customer_id'], 'reservation_status',
            'Reservation ' . $cancelled['reservation']['reservation_code'], $message,
            'reservation', (int) $cancelled['reservation']['reservation_id']
        );
        return $cancelled['rental'];
    }

    public function recordInspection(int $rentalId, array $data): void
    {
        Database::transaction(function (PDO $pdo) use ($rentalId, $data) {
            $rental = $this->lockRental($pdo, $rentalId);
            if ($rental['status'] !== 'returned') {
                throw new InvalidArgumentException('Return inspection is recorded after the vehicle is returned.');
            }
            $this->addInspection($rentalId, 'return', isset($data['odometer_reading']) ? (int) $data['odometer_reading'] : null, $data['fuel_level_percent'] ?? null, $data);
            Database::execute("UPDATE rentals SET status = 'inspected' WHERE rental_id = ?", [$rentalId]);
            Audit::log('rental.inspected', 'rental', (string) $rentalId, $rental['rental_number']);
        });
    }

    public function close(int $rentalId): void
    {
        Database::transaction(function (PDO $pdo) use ($rentalId) {
            $rental = $this->lockRental($pdo, $rentalId);
            if ($rental['status'] !== 'inspected') {
                throw new InvalidArgumentException('Close is allowed only after inspection.');
            }
            Database::execute("UPDATE rentals SET status = 'closed', agreement_status = 'completed' WHERE rental_id = ?", [$rentalId]);
            Database::execute("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?", [$rental['reservation_id']]);
            $this->reservations->syncVehicleStatus((int) $rental['vehicle_id']);
            Audit::log('rental.closed', 'rental', (string) $rentalId, $rental['rental_number']);
        });
    }

    private function lockRental(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare('SELECT * FROM rentals WHERE rental_id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Rental not found.');
        }
        return $row;
    }

    private function addInspection(int $rentalId, string $type, ?int $odometer, ?string $fuel, array $data): void
    {
        Database::execute(
            'INSERT INTO inspections (rental_id, inspected_by, inspection_type, inspected_at, odometer_reading, fuel_level_percent, exterior_condition, interior_condition, cleanliness, damage_notes, image_path, notes)
             VALUES (?,?,?,NOW(),?,?,?,?,?,?,?,?)',
            [
                $rentalId, Auth::id(), $type, $odometer, $fuel !== '' ? $fuel : null,
                $data['exterior_condition'] ?? null, $data['interior_condition'] ?? null,
                $data['cleanliness'] ?? null, $data['damage_notes'] ?? null,
                $data['image_path'] ?? null, $data['notes'] ?? null,
            ]
        );
    }
}
