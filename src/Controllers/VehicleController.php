<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Money;
use Crms\Rbac;
use Crms\Upload;
use Crms\Validator;
use Crms\View;
use Crms\Services\MaintenanceService;

final class VehicleController
{
    public function index(): void
    {
        Rbac::require('vehicles.view');
        $q = Http::str('q');
        $customer = Auth::isCustomer();
        $inspectorMode = (Auth::user()['role_name'] ?? '') === 'Vehicle Inspector/Maintenance Staff';
        $sql = $customer
            ? "SELECT v.vehicle_id, v.category_id, v.make, v.model, v.manufacture_year, v.transmission, v.fuel_type,
                      v.daily_rate, v.image_path, 'available' AS status, NULL AS registration_no, c.category_name
               FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE v.status = 'available'"
            : 'SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE 1=1';
        $params = [];
        if (!$customer && $inspectorMode) {
            $sql .= " AND v.status = 'maintenance'";
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $sql .= $customer
                ? ' AND (v.make LIKE ? OR v.model LIKE ?)'
                : ' AND (v.registration_no LIKE ? OR v.make LIKE ? OR v.model LIKE ?)';
            $params = $customer ? [$like, $like] : [$like, $like, $like];
        }
        $sql .= ' ORDER BY v.updated_at DESC';
        View::render('vehicles/index', [
            'title' => $customer ? 'Available vehicles' : ($inspectorMode ? 'Vehicles in maintenance' : 'Vehicles'),
            'vehicles' => Database::fetchAll($sql, $params),
            'q' => $q,
            'customerMode' => $customer,
        ]);
    }

    public function form(): void
    {
        Rbac::require('vehicles.manage');
        $rentalOfficer = (Auth::user()['role_name'] ?? '') === 'Rental Officer';
        $id = Http::int('id');
        $vehicle = $id ? Database::fetch('SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE v.vehicle_id = ?', [$id]) : null;
        $categories = Database::fetchAll('SELECT * FROM vehicle_categories ORDER BY category_name');
        if (Http::isPost()) {
            $v = new Validator();
            $categoryId = $rentalOfficer && $vehicle ? (int) $vehicle['category_id'] : Http::int('category_id');
            $required = ['registration_no', 'make', 'model', 'daily_rate'];
            if (!$rentalOfficer || !$vehicle) {
                $required[] = 'category_id';
            }
            $v->require($_POST, $required);
            $categoryIds = array_map(static fn(array $category): string => (string) $category['category_id'], $categories);
            $v->in('category_id', (string) $categoryId, $categoryIds);
            $v->decimal('daily_rate', Http::str('daily_rate'));
            if (!$rentalOfficer) {
                $v->decimal('weekly_rate', Http::str('weekly_rate'), true);
                $v->decimal('monthly_rate', Http::str('monthly_rate'), true);
            }
            $rateFields = $rentalOfficer ? ['daily_rate'] : ['daily_rate', 'weekly_rate', 'monthly_rate'];
            foreach ($rateFields as $rateField) {
                $rate = trim(Http::str($rateField));
                if ($rate !== '' && is_numeric($rate) && (float) $rate > 9999999999.99) {
                    $v->errors[$rateField] = 'Rates must be no greater than 9,999,999,999.99.';
                }
            }
            if (!$rentalOfficer) {
                $v->in('status', Http::str('status'), ['available', 'reserved', 'rented', 'maintenance', 'inactive']);
            }
            $fuelLevel = Http::str('fuel_level_percent');
            if ($fuelLevel !== '' && (!is_numeric($fuelLevel) || (float) $fuelLevel < 0 || (float) $fuelLevel > 100)) {
                $v->errors['fuel_level_percent'] = 'Fuel level must be between 0 and 100 percent.';
            }
            if (Http::int('current_mileage', 0) < 0) {
                $v->errors['current_mileage'] = 'Mileage cannot be negative.';
            }
            if ($v->ok()) {
                $selectedCategory = null;
                foreach ($categories as $category) {
                    if ((int) $category['category_id'] === $categoryId) {
                        $selectedCategory = $category;
                        break;
                    }
                }
                if (!$selectedCategory) {
                    Http::flash('danger', 'Choose a valid vehicle category.');
                    View::render('vehicles/form', ['title' => $vehicle ? 'Edit vehicle' : 'Add vehicle', 'vehicle' => $vehicle, 'categories' => $categories, 'rentalOfficer' => $rentalOfficer]);
                    return;
                }
                $image = $vehicle['image_path'] ?? null;
                try {
                    $uploaded = Upload::image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'vehicles');
                    if ($uploaded) {
                        $image = $uploaded;
                    }
                } catch (\Throwable $e) {
                    Http::flash('danger', $e->getMessage());
                    View::render('vehicles/form', ['title' => 'Vehicle', 'vehicle' => $vehicle, 'categories' => $categories, 'rentalOfficer' => $rentalOfficer]);
                    return;
                }
                if ($rentalOfficer) {
                    $dailyRate = Http::str('daily_rate');
                    $categoryDailyRate = (float) $selectedCategory['default_daily_rate'];
                    $categoryWeeklyRate = $selectedCategory['default_weekly_rate'];
                    $categoryMonthlyRate = $selectedCategory['default_monthly_rate'];
                    $weeklyRate = $categoryDailyRate > 0 && (float) $categoryWeeklyRate > 0
                        ? Money::mul($dailyRate, (float) $categoryWeeklyRate / $categoryDailyRate)
                        : Money::mul($dailyRate, 7);
                    $monthlyRate = $categoryDailyRate > 0 && (float) $categoryMonthlyRate > 0
                        ? Money::mul($dailyRate, (float) $categoryMonthlyRate / $categoryDailyRate)
                        : Money::mul($dailyRate, 30);
                    if ($vehicle) {
                        $status = $vehicle['status'];
                        $currentMileage = (int) $vehicle['current_mileage'];
                        $vehicleFuelLevel = $vehicle['fuel_level_percent'];
                    } else {
                        $status = 'available';
                        $currentMileage = Http::int('current_mileage');
                        $vehicleFuelLevel = Http::str('fuel_level_percent') !== '' ? Http::str('fuel_level_percent') : null;
                    }
                } else {
                    $dailyRate = Http::str('daily_rate');
                    $weeklyRate = Http::str('weekly_rate') !== '' ? Http::str('weekly_rate') : null;
                    $monthlyRate = Http::str('monthly_rate') !== '' ? Http::str('monthly_rate') : null;
                    $status = Http::str('status') ?: 'available';
                    $currentMileage = Http::int('current_mileage');
                    $vehicleFuelLevel = Http::str('fuel_level_percent') !== '' ? Http::str('fuel_level_percent') : null;
                }
                $fields = [
                    $categoryId, Http::str('registration_no'), Http::str('vin') ?: null,
                    Http::str('make'), Http::str('model'), Http::str('manufacture_year') !== '' ? Http::int('manufacture_year') : null,
                    Http::str('colour') ?: null, Http::str('transmission') ?: 'manual', Http::str('fuel_type') ?: 'petrol',
                    $dailyRate, $weeklyRate, $monthlyRate,
                    $currentMileage, $vehicleFuelLevel,
                    $image, $status,
                    Http::str('insurance_expiry_date') ?: null, Http::str('inspection_expiry_date') ?: null,
                    Http::str('description') ?: null,
                ];
                if ($vehicle) {
                    Database::execute(
                        'UPDATE vehicles SET category_id=?, registration_no=?, vin=?, make=?, model=?, manufacture_year=?, colour=?, transmission=?, fuel_type=?, daily_rate=?, weekly_rate=?, monthly_rate=?, current_mileage=?, fuel_level_percent=?, image_path=?, status=?, insurance_expiry_date=?, inspection_expiry_date=?, description=? WHERE vehicle_id=?',
                        [...$fields, $id]
                    );
                    Audit::log('vehicle.updated', 'vehicle', (string) $id, Http::str('registration_no'));
                    Http::flash('success', 'Vehicle updated.');
                } else {
                    Database::execute(
                        'INSERT INTO vehicles (category_id, registration_no, vin, make, model, manufacture_year, colour, transmission, fuel_type, daily_rate, weekly_rate, monthly_rate, current_mileage, fuel_level_percent, image_path, status, insurance_expiry_date, inspection_expiry_date, description, created_by)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                        [...$fields, Auth::id()]
                    );
                    Audit::log('vehicle.created', 'vehicle', Database::lastId(), Http::str('registration_no'));
                    Http::flash('success', 'Vehicle created.');
                }
                Http::redirect('vehicles');
            } else {
                Http::flash('danger', 'Please correct the highlighted fields.');
            }
        }
        View::render('vehicles/form', ['title' => $vehicle ? 'Edit vehicle' : 'Add vehicle', 'vehicle' => $vehicle, 'categories' => $categories, 'rentalOfficer' => $rentalOfficer]);
    }

    public function show(): void
    {
        Rbac::require('vehicles.view');
        $id = Http::int('id');
        $isCustomer = Auth::isCustomer();
        $inspectorMode = (Auth::user()['role_name'] ?? '') === 'Vehicle Inspector/Maintenance Staff';
        $vehicleSql = $isCustomer
            ? "SELECT v.vehicle_id, v.category_id, v.make, v.model, v.manufacture_year, v.transmission, v.fuel_type,
                      v.daily_rate, v.weekly_rate, v.monthly_rate, v.image_path, v.description, v.status, c.category_name
               FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id
               WHERE v.vehicle_id = ? AND v.status NOT IN ('inactive','maintenance')"
            : ($inspectorMode
                ? "SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE v.vehicle_id = ? AND v.status = 'maintenance'"
                : 'SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE v.vehicle_id = ?');
        $vehicle = Database::fetch($vehicleSql, [$id]);
        if (!$vehicle) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        // Reservation history contains other customers' booking data. Never expose it to customers.
        $history = $isCustomer || $inspectorMode ? [] : Database::fetchAll(
            'SELECT reservation_code, pickup_datetime, return_datetime, status FROM reservations WHERE vehicle_id = ? ORDER BY pickup_datetime DESC LIMIT 20',
            [$id]
        );
        View::render('vehicles/show', ['title' => $vehicle['registration_no'], 'vehicle' => $vehicle, 'history' => $history]);
    }

    public function flag(): void
    {
        Auth::requireLogin();
        if (!Rbac::can('inspections.manage') && !Rbac::can('maintenance.manage')) {
            Rbac::require('inspections.manage');
        }
        if (!Http::isPost()) {
            Http::redirect('vehicles');
        }
        $id = Http::int('id');
        $vehicle = Database::fetch('SELECT vehicle_id, registration_no, status FROM vehicles WHERE vehicle_id = ?', [$id]);
        if (!$vehicle) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        if ($vehicle['status'] === 'inactive') {
            Http::flash('danger', 'Inactive vehicles cannot be flagged.');
            Http::redirect('vehicles/show', ['id' => $id]);
        }
        $note = trim(Http::str('flag_note'));
        if ($note === '' || strlen($note) > 250) {
            Http::flash('danger', 'Enter a safety reason of 250 characters or fewer.');
            Http::redirect('vehicles/show', ['id' => $id]);
        }
        try {
            (new MaintenanceService())->updateVehicleReadiness($id, 'maintenance', $note);
        } catch (\InvalidArgumentException $e) {
            Http::flash('danger', $e->getMessage());
            Http::redirect('vehicles/show', ['id' => $id]);
        }
        Http::flash('success', $vehicle['registration_no'] . ' is flagged unavailable until maintenance is cleared.');
        Http::redirect('vehicles/show', ['id' => $id]);
    }
}
