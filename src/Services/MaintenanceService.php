<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use InvalidArgumentException;
use PDO;

final class MaintenanceService
{
    private const TYPES = ['routine_service', 'repair', 'inspection', 'tyres', 'oil_change', 'bodywork', 'other'];
    private const TRANSITIONS = [
        'scheduled' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function create(array $input): array
    {
        $vehicleId = (int) ($input['vehicle_id'] ?? 0);
        $type = trim((string) ($input['maintenance_type'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $startDate = $this->date($input['start_date'] ?? '', 'Start date', true);
        $expectedDate = $this->date($input['expected_completion_date'] ?? '', 'Expected completion date');
        $nextServiceDate = $this->date($input['next_service_date'] ?? '', 'Next service date');
        $cost = trim((string) ($input['cost'] ?? '0'));
        $odometerInput = trim((string) ($input['odometer_reading'] ?? ''));

        if ($vehicleId < 1) {
            throw new InvalidArgumentException('Choose a vehicle for this maintenance record.');
        }
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Choose a valid maintenance type.');
        }
        if ($title === '' || $this->length($title) > 180) {
            throw new InvalidArgumentException('Enter a maintenance title of 180 characters or fewer.');
        }
        if ($expectedDate !== null && $expectedDate < $startDate) {
            throw new InvalidArgumentException('Expected completion cannot be before the service start date.');
        }
        if ($nextServiceDate !== null && $nextServiceDate < $startDate) {
            throw new InvalidArgumentException('Next service date cannot be before the service start date.');
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $cost)) {
            throw new InvalidArgumentException('Enter a non-negative maintenance cost with up to two decimal places.');
        }
        if ($odometerInput !== '' && (!preg_match('/^\d{1,10}$/', $odometerInput) || (float) $odometerInput > 4294967295)) {
            throw new InvalidArgumentException('Enter a valid whole-number service odometer reading.');
        }

        return Database::transaction(function (PDO $pdo) use (
            $vehicleId, $type, $title, $startDate, $expectedDate, $nextServiceDate, $cost, $odometerInput, $input
        ) {
            $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE vehicle_id = ? FOR UPDATE');
            $stmt->execute([$vehicleId]);
            $vehicle = $stmt->fetch();
            if (!$vehicle) {
                throw new InvalidArgumentException('Vehicle not found.');
            }
            if ($vehicle['status'] === 'inactive') {
                throw new InvalidArgumentException('Inactive vehicles cannot receive maintenance records.');
            }

            $odometer = $odometerInput !== '' ? (int) $odometerInput : null;
            if ($odometer !== null && $odometer < (int) $vehicle['current_mileage']) {
                throw new InvalidArgumentException('Service odometer cannot be below the vehicle odometer.');
            }

            $number = (new ReservationService())->nextCode('MNT');
            Database::execute(
                'INSERT INTO maintenance_records (maintenance_number, vehicle_id, reported_by, maintenance_type, title, description, service_provider, start_date, expected_completion_date, odometer_reading, cost, next_service_date, status, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $number, $vehicleId, Auth::id(), $type, $title,
                    $this->optionalText($input['description'] ?? null, 10000),
                    $this->optionalText($input['service_provider'] ?? null, 180),
                    $startDate, $expectedDate, $odometer, $cost, $nextServiceDate,
                    'scheduled', $this->optionalText($input['notes'] ?? null, 10000),
                ]
            );
            $id = (int) Database::lastId();
            if ($odometer !== null) {
                Database::execute('UPDATE vehicles SET current_mileage = GREATEST(current_mileage, ?) WHERE vehicle_id = ?', [$odometer, $vehicleId]);
            }
            Database::execute("UPDATE vehicles SET status = 'maintenance' WHERE vehicle_id = ? AND status <> 'inactive'", [$vehicleId]);
            Audit::log('maintenance.created', 'maintenance', (string) $id, $number, null, [
                'vehicle_id' => $vehicleId,
                'status' => 'scheduled',
                'cost' => $cost,
                'next_service_date' => $nextServiceDate,
            ]);
            return Database::fetch('SELECT * FROM maintenance_records WHERE maintenance_id = ?', [$id]);
        });
    }

    public function updateDetails(int $id, array $input): void
    {
        $title = trim((string) ($input['title'] ?? ''));
        $startDate = $this->date($input['start_date'] ?? '', 'Start date', true);
        $expectedDate = $this->date($input['expected_completion_date'] ?? '', 'Expected completion date');
        $nextServiceDate = $this->date($input['next_service_date'] ?? '', 'Next service date');
        $cost = trim((string) ($input['cost'] ?? ''));
        $odometerInput = trim((string) ($input['odometer_reading'] ?? ''));

        if ($title === '' || $this->length($title) > 180) {
            throw new InvalidArgumentException('Enter a maintenance title of 180 characters or fewer.');
        }
        if ($expectedDate !== null && $expectedDate < $startDate) {
            throw new InvalidArgumentException('Expected completion cannot be before the service start date.');
        }
        if ($nextServiceDate !== null && $nextServiceDate < $startDate) {
            throw new InvalidArgumentException('Next service date cannot be before the service start date.');
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $cost)) {
            throw new InvalidArgumentException('Enter a non-negative maintenance cost with up to two decimal places.');
        }
        if ($odometerInput !== '' && (!preg_match('/^\d{1,10}$/', $odometerInput) || (float) $odometerInput > 4294967295)) {
            throw new InvalidArgumentException('Enter a valid whole-number service odometer reading.');
        }

        Database::transaction(function (PDO $pdo) use ($id, $input, $title, $startDate, $expectedDate, $nextServiceDate, $cost, $odometerInput) {
            $stmt = $pdo->prepare('SELECT * FROM maintenance_records WHERE maintenance_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $record = $stmt->fetch();
            if (!$record) {
                throw new InvalidArgumentException('Maintenance record not found.');
            }
            $odometer = $odometerInput !== '' ? (int) $odometerInput : ($record['odometer_reading'] !== null ? (int) $record['odometer_reading'] : null);
            if ($odometer !== null && $record['odometer_reading'] !== null && $odometer < (int) $record['odometer_reading']) {
                throw new InvalidArgumentException('Service odometer cannot be lower than the reading already saved for this job.');
            }
            if ($record['completed_date'] && $nextServiceDate !== null && $nextServiceDate < $record['completed_date']) {
                throw new InvalidArgumentException('Next service date cannot be before the last completed service date.');
            }
            Database::execute(
                'UPDATE maintenance_records SET title = ?, description = ?, service_provider = ?, start_date = ?, expected_completion_date = ?, odometer_reading = ?, cost = ?, next_service_date = ?, notes = ? WHERE maintenance_id = ?',
                [
                    $title,
                    $this->optionalText($input['description'] ?? null, 10000),
                    $this->optionalText($input['service_provider'] ?? null, 180),
                    $startDate,
                    $expectedDate,
                    $odometer,
                    $cost,
                    $nextServiceDate,
                    $this->optionalText($input['notes'] ?? null, 10000),
                    $id,
                ]
            );
            if ($odometer !== null) {
                Database::execute('UPDATE vehicles SET current_mileage = GREATEST(current_mileage, ?) WHERE vehicle_id = ?', [$odometer, $record['vehicle_id']]);
            }
            Audit::log('maintenance.details_updated', 'maintenance', (string) $id, $record['maintenance_number'], [
                'title' => $record['title'], 'description' => $record['description'], 'cost' => $record['cost'],
                'odometer_reading' => $record['odometer_reading'], 'next_service_date' => $record['next_service_date'],
            ], [
                'title' => $title, 'description' => $input['description'] ?? null, 'cost' => $cost,
                'odometer_reading' => $odometer, 'next_service_date' => $nextServiceDate,
            ]);
        });
    }

    public function updateStatus(int $id, string $newStatus, string $reason = ''): void
    {
        Database::transaction(function (PDO $pdo) use ($id, $newStatus, $reason) {
            $stmt = $pdo->prepare('SELECT * FROM maintenance_records WHERE maintenance_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $record = $stmt->fetch();
            if (!$record) {
                throw new InvalidArgumentException('Maintenance record not found.');
            }

            $oldStatus = (string) $record['status'];
            if (!in_array($newStatus, self::TRANSITIONS[$oldStatus] ?? [], true)) {
                throw new InvalidArgumentException('That maintenance status change is not allowed.');
            }
            if (in_array($newStatus, ['in_progress', 'completed'], true) && $record['start_date'] > date('Y-m-d')) {
                throw new InvalidArgumentException('A future service job cannot be started or completed.');
            }
            if ($newStatus === 'cancelled' && trim($reason) === '') {
                throw new InvalidArgumentException('Enter a reason when cancelling a maintenance job.');
            }
            if ($this->length($reason) > 500) {
                throw new InvalidArgumentException('The status note must be 500 characters or fewer.');
            }

            Database::execute(
                'UPDATE maintenance_records SET status = ?, completed_date = ? WHERE maintenance_id = ?',
                [$newStatus, $newStatus === 'completed' ? date('Y-m-d') : null, $id]
            );

            if ($newStatus === 'completed') {
                $otherOpen = Database::fetch(
                    "SELECT maintenance_id FROM maintenance_records WHERE vehicle_id = ? AND status IN ('scheduled','in_progress') AND maintenance_id <> ? LIMIT 1",
                    [$record['vehicle_id'], $id]
                );
                if (!$otherOpen) {
                    // Completing the last open job releases the vehicle from its maintenance hold.
                    Database::execute("UPDATE vehicles SET status = 'available' WHERE vehicle_id = ? AND status = 'maintenance'", [$record['vehicle_id']]);
                    (new ReservationService())->syncVehicleStatus((int) $record['vehicle_id']);
                }
            }

            Audit::log(
                'maintenance.status_changed',
                'maintenance',
                (string) $id,
                $record['maintenance_number'],
                ['status' => $oldStatus],
                ['status' => $newStatus, 'reason' => trim($reason)]
            );
        });
    }

    private function date(mixed $value, string $label, bool $required = false): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            if ($required) {
                throw new InvalidArgumentException($label . ' is required.');
            }
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Enter a valid ' . strtolower($label) . '.');
        }
        return $value;
    }

    private function optionalText(mixed $value, int $maxLength): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        if ($this->length($text) > $maxLength) {
            throw new InvalidArgumentException('A maintenance text field is too long.');
        }
        return $text;
    }

    private function length(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }
}
