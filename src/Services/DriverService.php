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
        $notifications = Database::transaction(function (PDO $pdo) use ($reservationId, $driverId, $notes) {
            $stmt = $pdo->prepare('SELECT * FROM reservations WHERE reservation_id=? FOR UPDATE');
            $stmt->execute([$reservationId]);
            $reservation = $stmt->fetch();
            if (!$reservation) throw new InvalidArgumentException('Reservation not found.');
            if (!in_array($reservation['status'], ['pending', 'confirmed'], true)) {
                throw new InvalidArgumentException('Drivers can only be assigned to pending or confirmed reservations.');
            }
            $current = Database::fetch('SELECT * FROM driver_assignments WHERE reservation_id=? FOR UPDATE', [$reservationId]);
            $oldDriver = $current
                ? Database::fetch('SELECT user_id FROM drivers WHERE driver_id=? FOR UPDATE', [(int)$current['driver_id']])
                : null;
            $oldUserId = !empty($oldDriver['user_id']) ? (int)$oldDriver['user_id'] : null;
            $messages = [];
            if (!$driverId) {
                if ($current) {
                    Database::execute('DELETE FROM driver_assignments WHERE assignment_id=?', [(int)$current['assignment_id']]);
                    Audit::log('driver.unassigned', 'reservation', (string)$reservationId, $reservation['reservation_code']);
                    if ($oldUserId) {
                        $messages[] = [
                            'user_id' => $oldUserId,
                            'type' => 'driver_assignment_removed',
                            'subject' => 'Chauffeur trip unassigned',
                            'message' => 'Reservation ' . $reservation['reservation_code'] . ' is no longer assigned to you.',
                        ];
                    }
                }
                return $messages;
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
            $cleanNotes = trim((string)$notes) ?: null;
            if ($current) {
                if ((int)$current['driver_id'] !== $driverId) {
                    Database::execute(
                        "UPDATE driver_assignments SET driver_id=?, assigned_by=?, notes=?, trip_status='assigned', incident_report=NULL, status_updated_at=NULL WHERE assignment_id=?",
                        [$driverId, Auth::id(), $cleanNotes, $current['assignment_id']]
                    );
                } else {
                    Database::execute('UPDATE driver_assignments SET assigned_by=?, notes=? WHERE assignment_id=?', [Auth::id(), $cleanNotes, $current['assignment_id']]);
                }
            } else {
                Database::execute('INSERT INTO driver_assignments (driver_id,reservation_id,assigned_by,notes) VALUES (?,?,?,?)', [$driverId, $reservationId, Auth::id(), $cleanNotes]);
            }
            Audit::log('driver.assigned', 'reservation', (string)$reservationId, $reservation['reservation_code'] . ' assigned to ' . $driver['first_name'] . ' ' . $driver['last_name']);
            $sameDriver = $current && (int)$current['driver_id'] === $driverId;
            if ($sameDriver) {
                if ($oldUserId && ($current['notes'] ?? null) !== $cleanNotes) {
                    $message = $cleanNotes
                        ? 'New instructions: ' . $cleanNotes
                        : 'The previous trip instructions have been cleared.';
                    $messages[] = [
                        'user_id' => $oldUserId,
                        'type' => 'driver_assignment_updated',
                        'subject' => 'Trip instructions updated',
                        'message' => 'Instructions for reservation ' . $reservation['reservation_code'] . ' changed. ' . $message,
                    ];
                }
            } else {
                if ($oldUserId) {
                    $messages[] = [
                        'user_id' => $oldUserId,
                        'type' => 'driver_assignment_removed',
                        'subject' => 'Chauffeur trip reassigned',
                        'message' => 'Reservation ' . $reservation['reservation_code'] . ' is no longer assigned to you.',
                    ];
                }
                if (!empty($driver['user_id'])) {
                    $instructionText = $cleanNotes ? ' Instructions: ' . $cleanNotes : '';
                    $messages[] = [
                        'user_id' => (int)$driver['user_id'],
                        'type' => 'driver_assignment',
                        'subject' => 'Chauffeur trip assigned',
                        'message' => 'You have been assigned to reservation ' . $reservation['reservation_code'] . '.' . $instructionText,
                    ];
                }
            }
            return $messages;
        });
        foreach ($notifications as $notification) {
            (new NotificationService())->notifyUser(
                $notification['user_id'],
                null,
                $notification['type'],
                $notification['subject'],
                $notification['message'],
                'reservation',
                $reservationId
            );
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
        $incident = trim($incident);
        $incidentLength = function_exists('mb_strlen') ? mb_strlen($incident, 'UTF-8') : strlen($incident);
        if ($incidentLength > 500) {
            throw new InvalidArgumentException('Trip reports must be 500 characters or fewer.');
        }

        $updated = Database::transaction(function (PDO $pdo) use ($assignmentId, $driver, $status, $incident): array {
            $stmt = $pdo->prepare(
                'SELECT da.*, r.reservation_code, r.reservation_id, r.status AS reservation_status
                 FROM driver_assignments da JOIN reservations r ON r.reservation_id = da.reservation_id
                 WHERE da.assignment_id = ? AND da.driver_id = ? FOR UPDATE'
            );
            $stmt->execute([$assignmentId, $driver['driver_id']]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new InvalidArgumentException('That trip is not assigned to you.');
            }
            if (!in_array($row['reservation_status'], ['pending', 'confirmed'], true)) {
                throw new InvalidArgumentException('Trip updates are available only for current assigned reservations.');
            }
            $transitions = [
                'assigned' => ['assigned', 'en_route', 'on_site', 'delayed', 'completed'],
                'en_route' => ['en_route', 'on_site', 'delayed', 'completed'],
                'on_site' => ['on_site', 'en_route', 'delayed', 'completed'],
                'delayed' => ['delayed', 'en_route', 'on_site', 'completed'],
                'completed' => ['completed'],
            ];
            $currentStatus = (string)($row['trip_status'] ?? 'assigned');
            if (!in_array($status, $transitions[$currentStatus] ?? [], true)) {
                throw new InvalidArgumentException('Trip progress cannot move backwards after the current status.');
            }

            $incidentReport = trim((string)($row['incident_report'] ?? ''));
            if ($incident !== '') {
                $entry = '[' . date('Y-m-d H:i:s') . '] ' . $incident;
                $incidentReport = trim($incidentReport . ($incidentReport === '' ? '' : "\n") . $entry);
                if (strlen($incidentReport) > 10000) {
                    $incidentReport = substr($incidentReport, -10000);
                }
            }
            Database::execute(
                'UPDATE driver_assignments SET trip_status = ?, incident_report = ?, status_updated_at = NOW() WHERE assignment_id = ?',
                [$status, $incidentReport !== '' ? $incidentReport : null, $assignmentId]
            );
            return ['row' => $row, 'incident_report' => $incidentReport];
        });

        $row = $updated['row'];
        Audit::log('driver.trip_status', 'driver_assignment', (string)$assignmentId, $row['reservation_code'] . ' -> ' . $status);
        if ($incident !== '') {
            Audit::log('driver.trip_report', 'driver_assignment', (string)$assignmentId, $row['reservation_code'] . ': ' . $incident);
            $message = 'Driver report for reservation ' . $row['reservation_code'] . ': ' . $incident;
            $notifications = new NotificationService();
            if (!empty($row['assigned_by'])) {
                $notifications->notifyUser((int)$row['assigned_by'], null, 'driver_incident', 'Driver trip report', $message, 'reservation', (int)$row['reservation_id']);
            } else {
                $notifications->notifyRentalOfficers('driver_incident', 'Driver trip report', $message, 'reservation', (int)$row['reservation_id']);
            }
        }
    }
}
