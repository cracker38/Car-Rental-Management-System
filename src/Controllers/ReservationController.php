<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\ReservationService;
use InvalidArgumentException;

final class ReservationController
{
    public function index(): void
    {
        Rbac::require('reservations.view');
        $sql = 'SELECT r.*, EXISTS(SELECT 1 FROM rentals rt WHERE rt.reservation_id = r.reservation_id) AS has_rental,
                       c.first_name, c.last_name, v.registration_no, v.make, v.model
                FROM reservations r
                JOIN customers c ON c.customer_id = r.customer_id
                JOIN vehicles v ON v.vehicle_id = r.vehicle_id';
        $params = [];
        if (Auth::isCustomer()) {
            $cid = Auth::customerId();
            $sql .= ' WHERE r.customer_id = ?';
            $params[] = $cid ?: 0;
        }
        $sql .= ' ORDER BY r.created_at DESC LIMIT 200';
        View::render('reservations/index', ['title' => 'Reservations', 'rows' => Database::fetchAll($sql, $params)]);
    }

    public function form(): void
    {
        if (Auth::isCustomer()) {
            Auth::requireLogin();
        } else {
            Rbac::require('reservations.manage');
        }
        $reservationId = Http::int('id');
        $reservation = null;
        if ($reservationId > 0) {
            Rbac::assertCustomerOwns('reservation', $reservationId);
            $reservation = Database::fetch('SELECT * FROM reservations WHERE reservation_id = ?', [$reservationId]);
            if (!$reservation) {
                http_response_code(404);
                View::render('errors/404', ['title' => 'Not found']);
                return;
            }
            $canEditConfirmedStaffBooking = !Auth::isCustomer()
                && $reservation['status'] === 'confirmed'
                && $reservation['pickup_datetime'] > date('Y-m-d H:i:s')
                && !Database::fetch('SELECT rental_id FROM rentals WHERE reservation_id = ?', [$reservationId]);
            if ($reservation['status'] !== 'pending' && !$canEditConfirmedStaffBooking) {
                Http::flash('warning', 'Only pending reservations or eligible future confirmed staff bookings can be edited.');
                Http::redirect('reservations/show', ['id' => $reservationId]);
            }
        }
        $customers = Database::fetchAll("SELECT * FROM customers WHERE status = 'active' ORDER BY last_name");
        $vehicles = Database::fetchAll("SELECT * FROM vehicles WHERE status NOT IN ('inactive') ORDER BY registration_no");
        $preVehicle = Http::int('vehicle_id') ?: ($reservation ? (int) $reservation['vehicle_id'] : null);
        $pickup = Http::str('pickup_datetime', $reservation['pickup_datetime'] ?? '');
        $returnAt = Http::str('return_datetime', $reservation['return_datetime'] ?? '');
        if ($reservation && !in_array((int) $reservation['vehicle_id'], array_map(static fn(array $v): int => (int) $v['vehicle_id'], $vehicles), true)) {
            $oldVehicle = Database::fetch('SELECT * FROM vehicles WHERE vehicle_id = ?', [(int) $reservation['vehicle_id']]);
            if ($oldVehicle) $vehicles[] = $oldVehicle;
        }
        if ($reservation && Http::isPost()) {
            $pickup = Http::str('pickup_datetime');
            $returnAt = Http::str('return_datetime');
        }
        if (Auth::isCustomer()) {
            $customers = Database::fetchAll('SELECT * FROM customers WHERE customer_id = ?', [Auth::customerId()]);
        }
        if (Http::isPost()) {
            $customerId = $reservation ? (int) $reservation['customer_id'] : (Auth::isCustomer() ? (int) Auth::customerId() : Http::int('customer_id'));
            try {
                $svc = new ReservationService();
                $input = [
                    'customer_id' => $customerId,
                    'vehicle_id' => Http::int('vehicle_id'),
                    'pickup_datetime' => Http::datetime('pickup_datetime'),
                    'return_datetime' => Http::datetime('return_datetime'),
                    'pickup_location' => Http::str('pickup_location'),
                    'return_location' => Http::str('return_location'),
                    'discount_amount' => Auth::isCustomer() ? '0' : (Http::str('discount_amount') ?: '0'),
                    'customer_notes' => Http::str('customer_notes'),
                    'internal_notes' => Auth::isCustomer() ? null : Http::str('internal_notes'),
                ];
                if ($reservation) {
                    $svc->update($reservationId, $input, !Auth::isCustomer());
                    Http::flash('success', 'Reservation updated. Availability and pricing were rechecked.');
                    Http::redirect('reservations/show', ['id' => $reservationId]);
                }
                $svc->create($input, !Auth::isCustomer());
                Http::flash('success', 'Reservation saved. Availability was locked and rechecked in a database transaction.');
                Http::redirect('reservations');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            } catch (\Throwable) {
                Http::flash('danger', 'Could not create the reservation.');
            }
        }
        View::render('reservations/form', [
            'title' => $reservation ? 'Edit reservation' : 'New reservation',
            'customers' => $customers,
            'vehicles' => $vehicles,
            'preVehicle' => $preVehicle,
            'pickup' => $pickup,
            'returnAt' => $returnAt,
            'reservation' => $reservation,
        ]);
    }

    public function show(): void
    {
        Rbac::require('reservations.view');
        $id = Http::int('id');
        Rbac::assertCustomerOwns('reservation', $id);
        $row = Database::fetch(
            'SELECT r.reservation_id, r.customer_id, r.reservation_code, r.status, r.pickup_datetime, r.return_datetime,
                    r.estimated_amount, r.security_deposit, c.first_name, c.last_name, c.phone,
                    v.registration_no, v.make, v.model
             FROM reservations r
             JOIN customers c ON c.customer_id = r.customer_id
             JOIN vehicles v ON v.vehicle_id = r.vehicle_id
             WHERE r.reservation_id = ?',
            [$id]
        );
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $rentalSql = 'SELECT * FROM rentals WHERE reservation_id = ?';
        $rentalParams = [$id];
        if (Auth::isCustomer()) {
            $rentalSql .= ' AND customer_id = ?';
            $rentalParams[] = Auth::customerId() ?: 0;
        }
        $rental = Database::fetch($rentalSql, $rentalParams);
        $canEditConfirmedStaffBooking = !Auth::isCustomer()
            && $row['status'] === 'confirmed'
            && $row['pickup_datetime'] > date('Y-m-d H:i:s')
            && !$rental;
        $drivers = [];
        $assignment = null;
        if (Rbac::can('drivers.manage') && in_array($row['status'], ['pending', 'confirmed'], true)) {
            $drivers = Database::fetchAll("SELECT * FROM drivers WHERE status='active' ORDER BY last_name,first_name");
            $assignment = Database::fetch('SELECT * FROM driver_assignments WHERE reservation_id=?', [$id]);
        }
        View::render('reservations/show', ['title' => $row['reservation_code'], 'row' => $row, 'rental' => $rental, 'canEditConfirmedStaffBooking' => $canEditConfirmedStaffBooking, 'drivers' => $drivers, 'assignment' => $assignment]);
    }

    public function transition(): void
    {
        $id = Http::int('id');
        if (Auth::isCustomer()) {
            Auth::requireLogin();
            Rbac::assertCustomerOwns('reservation', $id);
            $to = Http::str('status');
            if ($to !== 'cancelled') {
                Http::flash('danger', 'Customers may only cancel their own pending or confirmed bookings.');
                Http::redirect('reservations/show', ['id' => $id]);
            }
        } else {
            Rbac::require('reservations.manage');
        }
        try {
            (new ReservationService())->transition($id, Http::str('status'), Http::str('reason') ?: null);
            Http::flash('success', 'Reservation status updated.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('reservations/show', ['id' => $id]);
    }
}
