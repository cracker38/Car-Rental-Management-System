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
        $sql = 'SELECT r.*, c.first_name, c.last_name, v.registration_no, v.make, v.model
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
        $customers = Database::fetchAll("SELECT * FROM customers WHERE status = 'active' ORDER BY last_name");
        $vehicles = Database::fetchAll("SELECT * FROM vehicles WHERE status NOT IN ('inactive') ORDER BY registration_no");
        $preVehicle = Http::int('vehicle_id') ?: null;
        $pickup = Http::str('pickup_datetime');
        $returnAt = Http::str('return_datetime');
        if (Auth::isCustomer()) {
            $customers = Database::fetchAll('SELECT * FROM customers WHERE customer_id = ?', [Auth::customerId()]);
        }
        if (Http::isPost()) {
            $customerId = Auth::isCustomer() ? (int) Auth::customerId() : Http::int('customer_id');
            try {
                $svc = new ReservationService();
                $svc->create([
                    'customer_id' => $customerId,
                    'vehicle_id' => Http::int('vehicle_id'),
                    'pickup_datetime' => Http::datetime('pickup_datetime'),
                    'return_datetime' => Http::datetime('return_datetime'),
                    'pickup_location' => Http::str('pickup_location'),
                    'return_location' => Http::str('return_location'),
                    'discount_amount' => Auth::isCustomer() ? '0' : (Http::str('discount_amount') ?: '0'),
                    'customer_notes' => Http::str('customer_notes'),
                    'internal_notes' => Auth::isCustomer() ? null : Http::str('internal_notes'),
                ], !Auth::isCustomer());
                Http::flash('success', 'Reservation saved. Availability was locked and rechecked in a database transaction.');
                Http::redirect('reservations');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            } catch (\Throwable) {
                Http::flash('danger', 'Could not create the reservation.');
            }
        }
        View::render('reservations/form', [
            'title' => 'New reservation',
            'customers' => $customers,
            'vehicles' => $vehicles,
            'preVehicle' => $preVehicle,
            'pickup' => $pickup,
            'returnAt' => $returnAt,
        ]);
    }

    public function show(): void
    {
        Rbac::require('reservations.view');
        $id = Http::int('id');
        Rbac::assertCustomerOwns('reservation', $id);
        $row = Database::fetch(
            'SELECT r.*, c.first_name, c.last_name, c.phone, v.registration_no, v.make, v.model
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
        $rental = Database::fetch('SELECT * FROM rentals WHERE reservation_id = ?', [$id]);
        View::render('reservations/show', ['title' => $row['reservation_code'], 'row' => $row, 'rental' => $rental]);
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
