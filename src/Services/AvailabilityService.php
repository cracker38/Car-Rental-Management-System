<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Database;
use Crms\Settings;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * Date/time availability. Vehicle.status alone is not sufficient.
 *
 * Overlap: existing.pickup < requested.return AND existing.return > requested.pickup
 * Periods are treated as half-open [pickup, return): a return at 10:00 and a later pickup
 * at 10:00 do not overlap when no turnaround buffer is configured.
 * Turnaround applies between either pair of adjacent bookings, so a buffer is respected
 * both after an existing booking and before a later booking.
 */
final class AvailabilityService
{
    public function assertValidPeriod(string $pickup, string $returnAt): void
    {
        $p = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $pickup);
        $pErrors = DateTimeImmutable::getLastErrors();
        $r = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $returnAt);
        $rErrors = DateTimeImmutable::getLastErrors();
        if (!$p || !$r || ($pErrors !== false && ($pErrors['warning_count'] > 0 || $pErrors['error_count'] > 0)) || ($rErrors !== false && ($rErrors['warning_count'] > 0 || $rErrors['error_count'] > 0))) {
            throw new InvalidArgumentException('Enter valid pickup and return date/times.');
        }
        if ($r <= $p) {
            throw new InvalidArgumentException('Return date/time must be after pickup.');
        }
    }

    /**
     * Caller must already hold SELECT ... FOR UPDATE on the vehicle row.
     */
    public function hasConflict(int $vehicleId, string $pickup, string $returnAt, ?int $ignoreReservationId = null, ?int $ignoreRentalId = null): ?string
    {
        $this->assertValidPeriod($pickup, $returnAt);
        $buffer = (int) Settings::get('turnaround_minutes', '0');

        $sql = 'SELECT reservation_code, pickup_datetime, return_datetime
                FROM reservations
                WHERE vehicle_id = ?
                  AND status IN (\'confirmed\')
                  AND pickup_datetime < DATE_ADD(?, INTERVAL ? MINUTE)
                  AND DATE_ADD(return_datetime, INTERVAL ? MINUTE) > ?';
        $params = [$vehicleId, $returnAt, $buffer, $buffer, $pickup];
        if ($ignoreReservationId) {
            $sql .= ' AND reservation_id <> ?';
            $params[] = $ignoreReservationId;
        }
        $hit = Database::fetch($sql, $params);
        if ($hit) {
            return 'Conflicts with confirmed reservation ' . $hit['reservation_code'] . '.';
        }

        $sql = 'SELECT rental_number, status, actual_pickup_datetime, expected_return_datetime, actual_return_datetime
                FROM rentals
                WHERE vehicle_id = ?
                  AND status IN (\'scheduled\',\'active\',\'overdue\',\'returned\')';
        $params = [$vehicleId];
        if ($ignoreRentalId) {
            $sql .= ' AND rental_id <> ?';
            $params[] = $ignoreRentalId;
        }
        foreach (Database::fetchAll($sql, $params) as $r) {
            $start = $r['actual_pickup_datetime'] ?? null;
            $end = $r['actual_return_datetime'];
            if (!$end) {
                $end = in_array($r['status'], ['active', 'overdue', 'returned'], true)
                    ? '9999-12-31 23:59:59'
                    : $r['expected_return_datetime'];
            }
            if (!$start) {
                $res = Database::fetch(
                    'SELECT pickup_datetime FROM reservations rsv JOIN rentals rt ON rt.reservation_id = rsv.reservation_id WHERE rt.rental_number = ?',
                    [$r['rental_number']]
                );
                $start = $res['pickup_datetime'] ?? $end;
            }
            if (\Crms\Period::overlaps((string) $start, (string) $end, $pickup, $returnAt, $buffer)) {
                return 'Conflicts with rental ' . $r['rental_number'] . '.';
            }
        }

        $m = Database::fetch(
            'SELECT maintenance_number FROM maintenance_records
             WHERE vehicle_id = ? AND status IN (\'scheduled\',\'in_progress\')
               AND start_date < DATE(?)
               AND COALESCE(expected_completion_date, DATE(\'9999-12-31\')) >= DATE(?)',
            [$vehicleId, $returnAt, $pickup]
        );
        if ($m) {
            return 'Vehicle is blocked by maintenance ' . $m['maintenance_number'] . '.';
        }
        return null;
    }

    public function lockVehicle(PDO $pdo, int $vehicleId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE vehicle_id = ? FOR UPDATE');
        $stmt->execute([$vehicleId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('Vehicle not found.');
        }
        return $row;
    }

    public function isOperationallyBookable(array $vehicle): ?string
    {
        if ($vehicle['status'] === 'inactive') {
            return 'This vehicle is inactive.';
        }
        if ($vehicle['status'] === 'maintenance') {
            return 'This vehicle is in maintenance.';
        }
        return null;
    }

    public function search(string $pickup, string $returnAt, ?int $categoryId = null, array $filters = [], string $sort = 'recommended'): array
    {
        $this->assertValidPeriod($pickup, $returnAt);
        $out = [];
        foreach ($this->matchingVehicles("v.status NOT IN ('inactive','maintenance')", $categoryId, $filters, $sort) as $v) {
            if ($this->hasConflict((int) $v['vehicle_id'], $pickup, $returnAt) === null) {
                $out[] = $v;
            }
        }
        return $out;
    }

    /** Browse currently available cars before the visitor enters rental dates. */
    public function browse(?int $categoryId = null, array $filters = [], string $sort = 'recommended'): array
    {
        return array_slice($this->matchingVehicles("v.status = 'available'", $categoryId, $filters, $sort), 0, 60);
    }

    private function matchingVehicles(string $availability, ?int $categoryId, array $filters, string $sort): array
    {
        $sql = 'SELECT v.*, c.category_name FROM vehicles v
                JOIN vehicle_categories c ON c.category_id = v.category_id
                WHERE ' . $availability;
        $params = [];
        if ($categoryId) {
            $sql .= ' AND v.category_id = ?';
            $params[] = $categoryId;
        }
        $model = trim((string) ($filters['model'] ?? ''));
        if ($model !== '') {
            $sql .= ' AND (v.make LIKE ? OR v.model LIKE ?)';
            $params[] = '%' . $model . '%';
            $params[] = '%' . $model . '%';
        }
        if (($filters['min_rate'] ?? '') !== '') {
            $sql .= ' AND v.daily_rate >= ?';
            $params[] = $filters['min_rate'];
        }
        if (($filters['max_rate'] ?? '') !== '') {
            $sql .= ' AND v.daily_rate <= ?';
            $params[] = $filters['max_rate'];
        }
        if (($filters['fuel_type'] ?? '') !== '') {
            $sql .= ' AND v.fuel_type = ?';
            $params[] = $filters['fuel_type'];
        }
        if (($filters['transmission'] ?? '') !== '') {
            $sql .= ' AND v.transmission = ?';
            $params[] = $filters['transmission'];
        }
        $orderBy = match ($sort) {
            'price_low' => 'v.daily_rate ASC, v.make ASC, v.model ASC',
            'price_high' => 'v.daily_rate DESC, v.make ASC, v.model ASC',
            default => 'v.updated_at DESC, v.daily_rate ASC, v.make ASC',
        };
        $sql .= ' ORDER BY ' . $orderBy;
        return Database::fetchAll($sql, $params);
    }
}
