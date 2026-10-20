<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Services\DriverAccess;
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
        $preCustomerId = $reservation
            ? (int) $reservation['customer_id']
            : (Auth::isCustomer() ? (int) (Auth::customerId() ?? 0) : Http::int('customer_id'));
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
                    'customer_signature' => Http::str('customer_signature'),
                    'reservation_reviewed' => Http::str('reservation_reviewed'),
                ];
                if ($reservation) {
                    $svc->update($reservationId, $input, !Auth::isCustomer());
                    Http::flash('success', 'Reservation updated. Availability and pricing were rechecked.');
                    Http::redirect('reservations/show', ['id' => $reservationId]);
                }
                $created = $svc->create($input, !Auth::isCustomer());
                Http::flash('success', 'Reservation saved. Review its booking deposit and payment status to continue.');
                Http::redirect('reservations/show', ['id' => $created['reservation_id']]);
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            } catch (\Throwable) {
                Http::flash('danger', 'Could not create the reservation.');
            }
        }
        View::render('reservations/form', [
            'title' => $reservation ? 'Edit reservation' : 'New reservation',
            'customers' => $customers,
            'preCustomerId' => $preCustomerId,
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
            'SELECT r.*, c.first_name, c.last_name, c.phone, c.email,
                    v.registration_no, v.make, v.model,
                    decider.first_name AS decision_first_name, decider.last_name AS decision_last_name,
                    captor.first_name AS signature_captor_first_name, captor.last_name AS signature_captor_last_name
             FROM reservations r
             JOIN customers c ON c.customer_id = r.customer_id
             JOIN vehicles v ON v.vehicle_id = r.vehicle_id
             LEFT JOIN users decider ON decider.user_id = r.decision_by
             LEFT JOIN users captor ON captor.user_id = r.customer_signature_captured_by
             WHERE r.reservation_id = ?',
            [$id]
        );
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $depositSummary = (new \Crms\Services\SecurityDepositService())->reservationSummary($id);
        $depositTransactions = Database::fetchAll(
            "SELECT transaction_id, transaction_number, amount, status, external_reference, transaction_date
             FROM financial_transactions
             WHERE (reservation_id = ? OR rental_id = (SELECT rental_id FROM rentals WHERE reservation_id = ? LIMIT 1))
               AND transaction_type = 'deposit_received'
             ORDER BY transaction_id DESC LIMIT 10",
            [$id, $id]
        );
        $bookingInvoice = Database::fetch(
            "SELECT i.invoice_id, i.invoice_number, i.status, i.total_amount, b.outstanding_balance, b.net_paid,
                    EXISTS(SELECT 1 FROM financial_transactions ft WHERE ft.invoice_id = i.invoice_id AND ft.status IN ('pending','completed','awaiting_approval')) AS payment_activity
             FROM invoices i LEFT JOIN vw_invoice_balances b ON b.invoice_id = i.invoice_id
             WHERE i.reservation_id = ? AND i.notes LIKE 'Booking prepayment:%'
             ORDER BY i.invoice_id DESC LIMIT 1",
            [$id]
        );
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
        $protectedDriverAssignment = false;
        if (Rbac::can('drivers.manage') && in_array($row['status'], ['pending', 'confirmed'], true)) {
            $drivers = Database::fetchAll(
                "SELECT d.* FROM drivers d WHERE d.status='active' AND " . DriverAccess::scopeSql('d') . ' ORDER BY d.last_name,d.first_name'
            );
            $assignment = Database::fetch('SELECT * FROM driver_assignments WHERE reservation_id=?', [$id]);
            if ($assignment && !DriverAccess::canAccess((int) $assignment['driver_id'])) {
                $assignment = null;
                $protectedDriverAssignment = true;
            }
        }
        View::render('reservations/show', ['title' => $row['reservation_code'], 'row' => $row, 'rental' => $rental, 'depositSummary' => $depositSummary, 'depositTransactions' => $depositTransactions, 'bookingInvoice' => $bookingInvoice, 'canEditConfirmedStaffBooking' => $canEditConfirmedStaffBooking, 'drivers' => $drivers, 'assignment' => $assignment, 'protectedDriverAssignment' => $protectedDriverAssignment]);
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

    public function decision(): void
    {
        $id = Http::int('id');
        Rbac::require('reservations.manage');
        if (!Http::isPost()) {
            Http::flash('danger', 'Submit the reservation decision form to continue.');
            Http::redirect('reservations/show', ['id' => $id]);
        }
        try {
            (new ReservationService())->decide(
                $id,
                Http::str('decision'),
                Http::str('note'),
                Http::str('approver_signature')
            );
            Http::flash('success', 'Reservation decision recorded and the customer has been notified.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        } catch (\Throwable) {
            Http::flash('danger', 'Could not record the reservation decision.');
        }
        Http::redirect('reservations/show', ['id' => $id]);
    }
}
