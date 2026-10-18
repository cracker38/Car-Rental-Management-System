<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Settings;
use Crms\Upload;
use Crms\View;
use Crms\Services\RentalService;
use InvalidArgumentException;

final class RentalController
{
    public function index(): void
    {
        $canManage = Rbac::can('rentals.manage');
        $canView = Rbac::can('rentals.view');
        $canInspect = Rbac::can('inspections.manage');
        if (!$canManage && !$canView && !$canInspect) {
            Rbac::require('rentals.view');
        }
        $sql = 'SELECT rt.*, c.first_name, c.last_name, v.registration_no, r.pickup_datetime
                FROM rentals rt
                JOIN customers c ON c.customer_id = rt.customer_id
                JOIN reservations r ON r.reservation_id = rt.reservation_id AND r.customer_id = rt.customer_id
                JOIN vehicles v ON v.vehicle_id = rt.vehicle_id';
        $params = [];
        if (!$canManage && !$canView && $canInspect) {
            $sql .= " WHERE rt.status IN ('scheduled','active','overdue','returned')";
        } elseif (Auth::isCustomer()) {
            $sql .= ' WHERE rt.customer_id = ?';
            $params[] = Auth::customerId() ?: 0;
        }
        $sql .= ' ORDER BY rt.created_at DESC LIMIT 200';
        View::render('rentals/index', [
            'title' => ($canManage || $canView) ? 'Rentals' : 'Pickup and return inspections',
            'rows' => Database::fetchAll($sql, $params),
            'inspectOnly' => !$canManage && !$canView && $canInspect,
        ]);
    }

    public function show(): void
    {
        $canManage = Rbac::can('rentals.manage');
        $canView = Rbac::can('rentals.view');
        $canInspect = Rbac::can('inspections.manage');
        if (!$canManage && !$canView && !$canInspect) {
            Rbac::require('rentals.view');
        }
        $id = Http::int('id');
        Rbac::assertCustomerOwns('rental', $id);
        $row = Database::fetch(
            "SELECT rt.*, CASE WHEN rt.status = 'active' AND rt.expected_return_datetime < NOW() THEN 'overdue' ELSE rt.status END AS display_status,
                    c.first_name, c.last_name, c.phone, c.email, c.address, c.driving_license_number,
                    v.registration_no, v.vin, v.make, v.model, v.manufacture_year, v.colour, v.transmission, v.fuel_type,
                    r.reservation_code, r.pickup_datetime AS booking_pickup_datetime,
                    r.return_datetime AS booking_return_datetime, r.pickup_location, r.return_location,
                    r.agreed_daily_rate, r.estimated_days, r.estimated_amount, r.security_deposit
             FROM rentals rt
             JOIN customers c ON c.customer_id = rt.customer_id
             JOIN vehicles v ON v.vehicle_id = rt.vehicle_id
             JOIN reservations r ON r.reservation_id = rt.reservation_id AND r.customer_id = rt.customer_id
             WHERE rt.rental_id = ?",
            [$id]
        );
        if (!$row) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        if (!$canManage && !$canView && !in_array($row['status'], ['scheduled', 'active', 'overdue', 'returned', 'inspected'], true)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            return;
        }
        $inspections = Database::fetchAll('SELECT * FROM inspections WHERE rental_id = ? ORDER BY inspected_at', [$id]);
        $invoice = null;
        if (Rbac::can('invoices.view')) {
            $invoiceStatusFilter = Auth::isCustomer() ? "status NOT IN ('draft','void')" : "status <> 'void'";
            $invoice = Database::fetch(
                'SELECT * FROM invoices WHERE rental_id = ? AND customer_id = ? AND ' . $invoiceStatusFilter,
                [$id, Auth::isCustomer() ? (Auth::customerId() ?: 0) : (int) $row['customer_id']]
            );
        }
        View::render('rentals/show', [
            'title' => $row['rental_number'],
            'row' => $row,
            'inspections' => $inspections,
            'invoice' => $invoice,
            'businessName' => Settings::get('business_name', 'Rent Car Rwanda'),
            'printAgreement' => !empty($row['agreement_terms']) && ($canManage || $canView),
            'inspectionOnly' => !$canManage && !$canView && $canInspect,
        ]);
    }

    public function create(): void
    {
        Rbac::require('rentals.manage');
        if (Auth::isCustomer()) {
            Http::flash('danger', 'Only staff can generate rental agreements.');
            Http::redirect('reservations');
        }
        try {
            $terms = Http::str('agreement_terms');
            if ($terms === '' || $terms === 'Leave blank to use the standard Rwanda hire terms.') {
                $terms = $this->defaultTerms();
            }
            if (strlen($terms) > 10000) {
                throw new InvalidArgumentException('Agreement terms must be 10,000 characters or fewer.');
            }
            $rental = (new RentalService())->createFromReservation(Http::int('reservation_id'), $terms);
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
            Http::flash('success', 'Return recorded. Mileage and late fees were calculated; damage and additional charges are flagged for accountant review.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function inspect(): void
    {
        Rbac::require('inspections.manage');
        try {
            $image = Upload::image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'inspections');
            (new RentalService())->recordInspection(Http::int('id'), [
                'odometer_reading' => Http::str('odometer_reading'),
                'fuel_level_percent' => Http::str('fuel_level_percent'),
                'exterior_condition' => Http::str('exterior_condition') ?: null,
                'interior_condition' => Http::str('interior_condition') ?: null,
                'cleanliness' => Http::str('cleanliness') ?: null,
                'damage_notes' => Http::str('damage_notes'),
                'notes' => Http::str('notes'),
                'image_path' => $image,
            ]);
            Http::flash('success', 'Return inspection saved.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        } catch (\RuntimeException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function inspectPickup(): void
    {
        Rbac::require('inspections.manage');
        try {
            $image = Upload::image($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'inspections');
            (new RentalService())->recordPickupInspection(Http::int('id'), [
                'odometer_reading' => Http::str('odometer_reading'),
                'fuel_level_percent' => Http::str('fuel_level_percent'),
                'exterior_condition' => Http::str('exterior_condition') ?: null,
                'interior_condition' => Http::str('interior_condition') ?: null,
                'cleanliness' => Http::str('cleanliness') ?: null,
                'damage_notes' => Http::str('damage_notes'),
                'notes' => Http::str('notes'),
                'image_path' => $image,
            ]);
            Http::flash('success', 'Pickup condition inspection saved. Rental status was not changed.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        } catch (\RuntimeException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function close(): void
    {
        Rbac::require('rentals.manage');
        try {
            (new RentalService())->close(Http::int('id'));
            Http::flash('success', 'Rental closed. Recorded charges are ready for the accountant invoice workflow.');
        } catch (InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
        }
        Http::redirect('rentals/show', ['id' => Http::int('id')]);
    }

    public function cancel(): void
    {
        Rbac::require('rentals.manage');
        try {
            (new RentalService())->cancelScheduled(Http::int('id'), Http::str('reason'));
            Http::flash('success', 'Scheduled rental and its reservation were cancelled. The reason was recorded in the audit log.');
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
