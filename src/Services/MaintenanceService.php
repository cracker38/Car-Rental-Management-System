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
    public function create(array $input): array
    {
        return Database::transaction(function (PDO $pdo) use ($input) {
            $vehicleId = (int) $input['vehicle_id'];
            $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE vehicle_id = ? FOR UPDATE');
            $stmt->execute([$vehicleId]);
            $v = $stmt->fetch();
            if (!$v) {
                throw new InvalidArgumentException('Vehicle not found.');
            }
            $number = (new ReservationService())->nextCode('MNT');
            Database::execute(
                'INSERT INTO maintenance_records (maintenance_number, vehicle_id, reported_by, maintenance_type, title, description, service_provider, start_date, expected_completion_date, odometer_reading, cost, status, notes)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $number, $vehicleId, Auth::id(), $input['maintenance_type'], $input['title'],
                    $input['description'] ?? null, $input['service_provider'] ?? null,
                    $input['start_date'], $input['expected_completion_date'] ?: null,
                    $input['odometer_reading'] !== '' ? (int) $input['odometer_reading'] : null,
                    $input['cost'] ?? 0, $input['status'] ?? 'scheduled', $input['notes'] ?? null,
                ]
            );
            $id = (int) Database::lastId();
            if (in_array($input['status'] ?? 'scheduled', ['scheduled', 'in_progress'], true)) {
                Database::execute("UPDATE vehicles SET status = 'maintenance' WHERE vehicle_id = ? AND status <> 'inactive'", [$vehicleId]);
            }
            Audit::log('maintenance.created', 'maintenance', (string) $id, $number);
            return Database::fetch('SELECT * FROM maintenance_records WHERE maintenance_id = ?', [$id]);
        });
    }

    public function complete(int $id): void
    {
        Database::transaction(function (PDO $pdo) use ($id) {
            $stmt = $pdo->prepare('SELECT * FROM maintenance_records WHERE maintenance_id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $m = $stmt->fetch();
            if (!$m) {
                throw new InvalidArgumentException('Maintenance record not found.');
            }
            Database::execute("UPDATE maintenance_records SET status = 'completed', completed_date = CURDATE() WHERE maintenance_id = ?", [$id]);
            $open = Database::fetch(
                "SELECT maintenance_id FROM maintenance_records WHERE vehicle_id = ? AND status IN ('scheduled','in_progress') AND maintenance_id <> ? LIMIT 1",
                [$m['vehicle_id'], $id]
            );
            if (!$open) {
                (new ReservationService())->syncVehicleStatus((int) $m['vehicle_id']);
            }
            Audit::log('maintenance.completed', 'maintenance', (string) $id, $m['maintenance_number']);
        });
    }
}
