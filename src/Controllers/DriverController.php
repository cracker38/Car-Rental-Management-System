<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Services\DriverService;
use Crms\View;
use InvalidArgumentException;

final class DriverController
{
    public function index(): void
    {
        Rbac::require('drivers.manage');
        $rows = Database::fetchAll(
            'SELECT d.*, u.email AS account_email FROM drivers d LEFT JOIN users u ON u.user_id=d.user_id ORDER BY d.status,d.last_name,d.first_name'
        );
        View::render('drivers/index', ['title' => 'Drivers', 'rows' => $rows]);
    }

    public function form(): void
    {
        Rbac::require('drivers.manage');
        $id = Http::int('id');
        $driver = $id ? Database::fetch('SELECT * FROM drivers WHERE driver_id=?', [$id]) : null;
        if ($id && !$driver) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $users = Database::fetchAll(
            "SELECT u.user_id,u.first_name,u.last_name,u.email FROM users u JOIN roles r ON r.role_id=u.role_id
             LEFT JOIN drivers d ON d.user_id=u.user_id WHERE r.role_name='Driver' AND u.status='active'
             AND (d.driver_id IS NULL OR d.driver_id=?) ORDER BY u.last_name,u.first_name",
            [$id ?: 0]
        );
        if (Http::isPost()) {
            try {
                (new DriverService())->save($_POST, $id ?: null);
                Http::flash('success', $id ? 'Driver record updated.' : 'Driver record created.');
                Http::redirect('drivers');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            } catch (\Throwable) {
                Http::flash('danger', 'Could not save the driver. Check that the licence number and linked account are unique.');
            }
        }
        View::render('drivers/form', ['title' => $id ? 'Edit driver' : 'Add driver', 'driver' => $driver, 'users' => $users]);
    }

    public function assign(): void
    {
        Rbac::require('drivers.manage');
        $reservationId = Http::int('reservation_id');
        try {
            (new DriverService())->assign($reservationId, Http::int('driver_id') ?: null, Http::str('notes'));
            Http::flash('success', Http::int('driver_id') ? 'Driver assignment saved.' : 'Driver assignment removed.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        } catch (\Throwable) {
            Http::flash('danger', 'Could not update this driver assignment.');
        }
        Http::redirect('reservations/show', ['id' => $reservationId]);
    }

    public function schedule(): void
    {
        Rbac::require('drivers.schedule');
        $canManage = Rbac::can('drivers.manage');
        $driverId = Http::int('driver_id');
        if (!$canManage) {
            $driver = Database::fetch('SELECT driver_id FROM drivers WHERE user_id=?', [Auth::id()]);
            $driverId = (int)($driver['driver_id'] ?? 0);
        }
        $rows = $driverId ? Database::fetchAll(
            "SELECT da.assignment_id, da.notes AS assignment_notes, da.trip_status, da.incident_report, da.status_updated_at,
                    r.reservation_code, r.pickup_datetime, r.return_datetime,
                    r.pickup_location, r.return_location, r.status AS reservation_status,
                    c.first_name,c.last_name,c.phone,v.registration_no,v.make,v.model
             FROM driver_assignments da JOIN reservations r ON r.reservation_id=da.reservation_id
             JOIN customers c ON c.customer_id=r.customer_id JOIN vehicles v ON v.vehicle_id=r.vehicle_id
             WHERE da.driver_id=? AND r.status IN ('pending','confirmed') AND da.trip_status <> 'completed'
             ORDER BY CASE WHEN r.pickup_datetime >= NOW() THEN 0 WHEN r.return_datetime >= NOW() THEN 1 ELSE 2 END,
                      CASE WHEN r.pickup_datetime >= NOW() THEN r.pickup_datetime ELSE r.return_datetime END ASC LIMIT 200",
            [$driverId]
        ) : [];
        $drivers = $canManage ? Database::fetchAll("SELECT driver_id,first_name,last_name FROM drivers ORDER BY last_name,first_name") : [];
        View::render('drivers/schedule', ['title' => $canManage ? 'Driver schedule' : 'My schedule', 'rows' => $rows, 'drivers' => $drivers, 'driverId' => $driverId, 'canManage' => $canManage]);
    }

    public function trip(): void
    {
        Rbac::require('drivers.schedule');
        if (!Http::isPost()) {
            Http::redirect('dashboard');
        }
        try {
            (new DriverService())->updateTrip(Http::int('assignment_id'), Http::str('trip_status'), Http::str('incident_report'));
            Http::flash('success', 'Trip progress saved.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect(Http::str('back') === 'schedule' ? 'drivers/schedule' : 'dashboard');
    }
}
