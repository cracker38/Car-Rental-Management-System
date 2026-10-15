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
        'active' => ['returned', 'cancelled'],
        'overdue' => ['returned', 'cancelled'],
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
            $mileage = (int) $data['pickup_mileage'];
            $vehicle = $this->availability->lockVehicle($pdo, (int) $rental['vehicle_id']);
            if ($mileage < (int) $vehicle['current_mileage']) {
                throw new InvalidArgumentException('Pickup mileage cannot be below the vehicle odometer.');
            }
            Database::execute(
                'UPDATE rentals SET status = \'active\', agreement_status = \'active\', signed_at = COALESCE(signed_at, NOW()),
                    actual_pickup_datetime = ?, pickup_mileage = ?, pickup_fuel_percent = ?, pickup_notes = ?, handled_by = ?
                 WHERE rental_id = ?',
                [
                    $data['actual_pickup_datetime'],
                    $mileage,
                    $data['pickup_fuel_percent'] !== '' ? $data['pickup_fuel_percent'] : null,
                    $data['pickup_notes'] ?? null,
                    Auth::id(),
                    $rentalId,
                ]
            );
            Database::execute(
                'UPDATE vehicles SET status = \'rented\', current_mileage = ?, fuel_level_percent = ? WHERE vehicle_id = ?',
                [$mileage, $data['pickup_fuel_percent'] !== '' ? $data['pickup_fuel_percent'] : $vehicle['fuel_level_percent'], $rental['vehicle_id']]
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
            $returnMileage = (int) $data['return_mileage'];
            $pickupMileage = (int) $rental['pickup_mileage'];
            if ($returnMileage < $pickupMileage) {
                throw new InvalidArgumentException('Return mileage cannot be below pickup mileage.');
            }
            $res = Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$rental['reservation_id']]);
            $days = $this->pricing->billableDays($res['pickup_datetime'], $res['return_datetime']);
            $extras = $this->pricing->extras(
                $pickupMileage,
                $returnMileage,
                $rental['actual_pickup_datetime'],
                $rental['expected_return_datetime'],
                $data['actual_return_datetime'],
                $days
            );
            $damage = Money::round($data['damage_fee'] ?? 0);
            $cleaning = Money::round($data['cleaning_fee'] ?? 0);
            $other = Money::round($data['other_charges'] ?? 0);
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
                    $data['actual_return_datetime'], $returnMileage,
                    $data['return_fuel_percent'] !== '' ? $data['return_fuel_percent'] : null,
                    $data['return_notes'] ?? null,
                    $extras['extra_mileage_fee'], $extras['late_return_fee'], $damage, $cleaning, $other,
                    $final, Auth::id(), $rentalId,
                ]
            );
            Database::execute(
                'UPDATE vehicles SET current_mileage = ?, fuel_level_percent = ? WHERE vehicle_id = ?',
                [$returnMileage, $data['return_fuel_percent'] !== '' ? $data['return_fuel_percent'] : null, $rental['vehicle_id']]
            );
            $this->reservations->syncVehicleStatus((int) $rental['vehicle_id']);
            Audit::log('rental.return', 'rental', (string) $rentalId, $rental['rental_number']);
        });
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
            'INSERT INTO inspections (rental_id, inspected_by, inspection_type, inspected_at, odometer_reading, fuel_level_percent, exterior_condition, interior_condition, cleanliness, damage_notes, notes)
             VALUES (?,?,?,NOW(),?,?,?,?,?,?,?)',
            [
                $rentalId, Auth::id(), $type, $odometer, $fuel !== '' ? $fuel : null,
                $data['exterior_condition'] ?? null, $data['interior_condition'] ?? null,
                $data['cleanliness'] ?? null, $data['damage_notes'] ?? null, $data['notes'] ?? null,
            ]
        );
    }
}
