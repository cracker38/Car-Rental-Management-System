<?php
declare(strict_types=1);

namespace Crms\Services;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use InvalidArgumentException;
use PDO;

final class DriverService
{
    public function save(array $input, ?int $id = null): array
    {
        $first = trim((string) ($input['first_name'] ?? ''));
        $last = trim((string) ($input['last_name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $license = strtoupper(trim((string) ($input['driving_license_number'] ?? '')));
        $status = (string) ($input['status'] ?? 'active');
        if ($first === '' || $last === '' || $phone === '' || $license === '') {
            throw new InvalidArgumentException('Name, phone, and driving licence number are required.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new InvalidArgumentException('Choose a valid driver status.');
        }
        $expiry = trim((string) ($input['license_expiry_date'] ?? ''));
        if ($expiry !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry) || !checkdate((int)substr($expiry, 5, 2), (int)substr($expiry, 8, 2), (int)substr($expiry, 0, 4)))) {
            throw new InvalidArgumentException('Enter a valid licence expiry date.');
        }
        $userId = !empty($input['user_id']) ? (int) $input['user_id'] : null;
        if ($userId) {
            $user = Database::fetch("SELECT u.user_id FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.user_id=? AND r.role_name='Driver' AND u.status='active'", [$userId]);
            if (!$user) {
                throw new InvalidArgumentException('Link an active account with the Driver role, or leave it unlinked.');
            }
        }

        return Database::transaction(function (PDO $pdo) use ($input, $id, $first, $last, $phone, $license, $status, $expiry, $userId) {
            $before = null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM drivers WHERE driver_id=? FOR UPDATE');
                $stmt->execute([$id]);
                $before = $stmt->fetch();
                if (!$before) throw new InvalidArgumentException('Driver record not found.');
                if ($status === 'inactive' && $before['status'] !== 'inactive') {
                    $future = Database::fetch("SELECT r.reservation_code FROM driver_assignments da JOIN reservations r ON r.reservation_id=da.reservation_id WHERE da.driver_id=? AND r.status IN ('pending','confirmed') AND r.return_datetime>NOW() LIMIT 1", [$id]);
                    if ($future) throw new InvalidArgumentException('Remove upcoming assignments before marking this driver inactive.');
                }
                Database::execute('UPDATE drivers SET user_id=?, first_name=?, last_name=?, phone=?, email=?, driving_license_number=?, license_expiry_date=?, status=?, notes=? WHERE driver_id=?', [
                    $userId, $first, $last, $phone, trim((string)($input['email'] ?? '')) ?: null, $license, $expiry ?: null, $status, trim((string)($input['notes'] ?? '')) ?: null, $id,
                ]);
            } else {
                Database::execute('INSERT INTO drivers (user_id,first_name,last_name,phone,email,driving_license_number,license_expiry_date,status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)', [
                    $userId, $first, $last, $phone, trim((string)($input['email'] ?? '')) ?: null, $license, $expiry ?: null, $status, trim((string)($input['notes'] ?? '')) ?: null, Auth::id(),
                ]);
                $id = (int) Database::lastId();
            }
            $after = Database::fetch('SELECT * FROM drivers WHERE driver_id=?', [$id]);
            Audit::log($before ? 'driver.updated' : 'driver.created', 'driver', (string)$id, $first . ' ' . $last, $before, $after);
            return $after;
        });
    }

    public function assign(int $reservationId, ?int $driverId, ?string $notes = null): void
    {
        $notify = Database::transaction(function (PDO $pdo) use ($reservationId, $driverId, $notes) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id=? FOR UPDATE');
            $stmt->execute([$reservationId]);
            $reservation = $stmt->fetch();
            if (!$reservation) throw new InvalidArgumentException('Reservation not found.');
            if (!in_array($reservation['status'], ['pending', 'confirmed'], true)) {
                throw new InvalidArgumentException('Drivers can only be assigned to pending or confirmed reservations.');
            }
            $current = Database::fetch('SELECT * FROM driver_assignments WHERE reservation_id=? FOR UPDATE', [$reservationId]);
            if (!$driverId) {
                if ($current) Database::execute('DELETE FROM driver_assignments WHERE assignment_id=?', [(int)$current['assignment_id']]);
                Audit::log('driver.unassigned', 'reservation', (string)$reservationId, $reservation['reservation_code']);
                return null;
            }
            $stmt = $pdo->prepare('SELECT * FROM drivers WHERE driver_id=? FOR UPDATE');
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch();
            if (!$driver || $driver['status'] !== 'active') throw new InvalidArgumentException('Choose an active driver.');
            if (!empty($driver['license_expiry_date']) && $driver['license_expiry_date'] < date('Y-m-d')) {
                throw new InvalidArgumentException('The selected driver licence has expired.');
            }
            $conflict = Database::fetch(
                "SELECT r.reservation_code FROM driver_assignments da JOIN reservations r ON r.reservation_id=da.reservation_id
                 WHERE da.driver_id=? AND da.reservation_id<>? AND r.status IN ('pending','confirmed')
                   AND r.pickup_datetime < ? AND r.return_datetime > ? LIMIT 1",
                [$driverId, $reservationId, $reservation['return_datetime'], $reservation['pickup_datetime']]
            );
            if ($conflict) throw new InvalidArgumentException('Driver is already assigned to overlapping reservation ' . $conflict['reservation_code'] . '.');
            if ($current) {
                Database::execute('UPDATE driver_assignments SET driver_id=?, assigned_by=?, notes=? WHERE assignment_id=?', [$driverId, Auth::id(), trim((string)$notes) ?: null, $current['assignment_id']]);
            } else {
                Database::execute('INSERT INTO driver_assignments (driver_id,reservation_id,assigned_by,notes) VALUES (?,?,?,?)', [$driverId, $reservationId, Auth::id(), trim((string)$notes) ?: null]);
            }
            Audit::log('driver.assigned', 'reservation', (string)$reservationId, $reservation['reservation_code'] . ' assigned to ' . $driver['first_name'] . ' ' . $driver['last_name']);
            return ['user_id' => $driver['user_id'] ? (int)$driver['user_id'] : null, 'customer_id' => (int)$reservation['customer_id'], 'code' => $reservation['reservation_code'], 'driver_name' => $driver['first_name'] . ' ' . $driver['last_name']];
        });
        if ($notify && $notify['user_id']) {
            (new NotificationService())->notifyUser($notify['user_id'], null, 'driver_assignment', 'Chauffeur trip assigned', 'You have been assigned to reservation ' . $notify['code'] . '.', 'reservation', $reservationId);
        }
    }

    public function updateTrip(int $assignmentId, string $status, string $incident = ''): void
    {
        $allowed = ['assigned', 'en_route', 'on_site', 'completed', 'delayed'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Choose a valid trip status.');
        }
        $driver = Database::fetch('SELECT driver_id FROM drivers WHERE user_id = ?', [Auth::id()]);
        if (!$driver) {
            throw new InvalidArgumentException('Your user account is not linked to a driver record.');
        }
        $row = Database::fetch(
            'SELECT da.*, r.reservation_code FROM driver_assignments da
             JOIN reservations r ON r.reservation_id = da.reservation_id
             WHERE da.assignment_id = ? AND da.driver_id = ?',
            [$assignmentId, $driver['driver_id']]
        );
        if (!$row) {
            throw new InvalidArgumentException('That trip is not assigned to you.');
        }
        Database::execute(
            'UPDATE driver_assignments SET trip_status = ?, incident_report = ?, status_updated_at = NOW() WHERE assignment_id = ?',
            [$status, $incident !== '' ? $incident : ($row['incident_report'] ?? null), $assignmentId]
        );
        Audit::log('driver.trip_status', 'driver_assignment', (string) $assignmentId, $row['reservation_code'] . ' -> ' . $status);
        if ($incident !== '') {
            $reservation = Database::fetch('SELECT customer_id FROM reservations WHERE reservation_id = ?', [$row['reservation_id']]);
            (new NotificationService())->notifyUser(
                $row['assigned_by'] ? (int) $row['assigned_by'] : Auth::id(),
                $reservation ? (int) $reservation['customer_id'] : null,
                'driver_incident',
                'Driver report ' . $row['reservation_code'],
                $incident,
                'reservation',
                (int) $row['reservation_id']
            );
        }
    }
}
