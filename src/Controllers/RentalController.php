<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\InvoiceService;
use Crms\Services\RentalService;
use InvalidArgumentException;

final class RentalController
{
    public function index(): void
    {
        Rbac::require('rentals.manage');
        $sql = 'SELECT rt.*, c.first_name, c.last_name, v.registration_no
                FROM rentals rt
                JOIN customers c ON c.customer_id = rt.customer_id
                JOIN vehicles v ON v.vehicle_id = rt.vehicle_id';
        $params = [];
        if (Auth::isCustomer()) {
            $sql .= ' WHERE rt.customer_id = ?';
            $params[] = Auth::customerId() ?: 0;
        }
        $sql .= ' ORDER BY rt.created_at DESC LIMIT 200';
        View::render('rentals/index', ['title' => 'Rentals', 'rows' => Database::fetchAll($sql, $params)]);
    }

    public function show(): void
    {
        Rbac::require('rentals.manage');
        $id = Http::int('id');
        Rbac::assertCustomerOwns('rental', $id);
        $row = Database::fetch(
            'SELECT rt.*, c.first_name, c.last_name, v.registration_no, v.make, v.model, r.reservation_code
             FROM rentals rt
             JOIN customers c ON c.customer_id = rt.customer_id
             JOIN vehicles v ON v.vehicle_id = rt.vehicle_id
             JOIN reservations r ON r.reservation_id = rt.reservation_id
             WHERE rt.rental_id = ?',
            [$id]
        );
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $inspections = Database::fetchAll('SELECT * FROM inspections WHERE rental_id = ? ORDER BY inspected_at', [$id]);
        $invoice = Database::fetch("SELECT * FROM invoices WHERE rental_id = ? AND status <> 'void'", [$id]);
        View::render('rentals/show', ['title' => $row['rental_number'], 'row' => $row, 'inspections' => $inspections, 'invoice' => $invoice]);
    }

    public function create(): void
    {
        Rbac::require('rentals.manage');
        if (Auth::isCustomer()) {
            Http::flash('danger', 'Only staff can generate rental agreements.');
            Http::redirect('reservations');
        }
        try {
            $rental = (new RentalService())->createFromReservation(Http::int('reservation_id'), Http::str('agreement_terms') ?: $this->defaultTerms());
            Http::flash('success', 'Rental agreement created.');
            Http::redirect('rentals/show', ['id' => $rental['rental_id']]);
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
            Http::redirect('reservations/show', ['id' => Http::int('reservation_id')]);
        }
    }

    public function pickup(): void
    {
        Rbac::require('rentals.manage');
        try {
            (new RentalService())->pickup(Http::int('id'), [
                'actual_pickup_datetime' => Http::datetime('actual_pickup_datetime'),
                'pickup_mileage' => Http::str('pickup_mileage'),
                'pickup_fuel_percent' => Http::str('pickup_fuel_percent'),
                'pickup_notes' => Http::str('pickup_notes'),
                'exterior_condition' => Http::str('exterior_condition') ?: null,
                'interior_condition' => Http::str('interior_condition') ?: null,
                'cleanliness' => Http::str('cleanliness') ?: null,
            ]);
            Http::flash('success', 'Pickup recorded.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function returnVehicle(): void
    {
        Rbac::require('rentals.manage');
        try {
            (new RentalService())->returnVehicle(Http::int('id'), [
                'actual_return_datetime' => Http::datetime('actual_return_datetime'),
                'return_mileage' => Http::str('return_mileage'),
                'return_fuel_percent' => Http::str('return_fuel_percent'),
                'return_notes' => Http::str('return_notes'),
                'damage_fee' => Http::str('damage_fee') ?: '0',
                'cleaning_fee' => Http::str('cleaning_fee') ?: '0',
                'other_charges' => Http::str('other_charges') ?: '0',
            ]);
            Http::flash('success', 'Return recorded. Fees were calculated on the server.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function inspect(): void
    {
        Rbac::require('inspections.manage');
        try {
            (new RentalService())->recordInspection(Http::int('id'), [
                'odometer_reading' => Http::str('odometer_reading'),
                'fuel_level_percent' => Http::str('fuel_level_percent'),
                'exterior_condition' => Http::str('exterior_condition') ?: null,
                'interior_condition' => Http::str('interior_condition') ?: null,
                'cleanliness' => Http::str('cleanliness') ?: null,
                'damage_notes' => Http::str('damage_notes'),
                'notes' => Http::str('notes'),
            ]);
            Http::flash('success', 'Return inspection saved.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function close(): void
    {
        Rbac::require('rentals.manage');
        try {
            (new RentalService())->close(Http::int('id'));
            $rentalId = Http::int('id');
            $existing = Database::fetch("SELECT invoice_id FROM invoices WHERE rental_id = ? AND status <> 'void'", [$rentalId]);
            if (!$existing) {
                (new InvoiceService())->createFromRental($rentalId);
            }
            Http::flash('success', 'Rental closed and invoice generated from server-side totals.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    private function defaultTerms(): string
    {
        return "Rental agreement (Rwanda / CRMS)\n"
            . "1. The hirer must hold a valid driving licence for the full hire period.\n"
            . "2. The vehicle must be returned with fuel and condition as recorded at pickup, unless charges apply.\n"
            . "3. Late return, extra mileage, cleaning and damage fees are calculated by the system using configured rates.\n"
            . "4. The security deposit is held separately from rental charges and is refunded after lawful deductions.\n"
            . "5. Currency is RWF. Times are Africa/Kigali.";
    }
}
