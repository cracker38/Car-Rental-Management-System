<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\Upload;
use Crms\Validator;
use Crms\View;

final class VehicleController
{
    public function index(): void
    {
        Rbac::require('vehicles.view');
        $q = Http::str('q');
        $sql = 'SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE 1=1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (v.registration_no LIKE ? OR v.make LIKE ? OR v.model LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY v.updated_at DESC';
        View::render('vehicles/index', [
            'title' => 'Vehicles',
            'vehicles' => Database::fetchAll($sql, $params),
            'q' => $q,
        ]);
    }

    public function form(): void
    {
        Rbac::require('vehicles.manage');
        $id = Http::int('id');
        $vehicle = $id ? Database::fetch('SELECT * FROM vehicles WHERE vehicle_id = ?', [$id]) : null;
        $categories = Database::fetchAll('SELECT * FROM vehicle_categories ORDER BY category_name');
        if (Http::isPost()) {
            $v = new Validator();
            $v->require($_POST, ['category_id', 'registration_no', 'make', 'model', 'daily_rate']);
            $v->in('status', Http::str('status'), ['available', 'reserved', 'rented', 'maintenance', 'inactive']);
            $v->decimal('daily_rate', Http::str('daily_rate'));
            if ($v->ok()) {
                $image = $vehicle['image_path'] ?? null;
                try {
                    $uploaded = Upload::image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], 'vehicles');
                    if ($uploaded) {
                        $image = $uploaded;
                    }
                } catch (\Throwable $e) {
                    Http::flash('danger', $e->getMessage());
                    View::render('vehicles/form', ['title' => 'Vehicle', 'vehicle' => $vehicle, 'categories' => $categories]);
                    return;
                }
                $fields = [
                    Http::int('category_id'), Http::str('registration_no'), Http::str('vin') ?: null,
                    Http::str('make'), Http::str('model'), Http::str('manufacture_year') !== '' ? Http::int('manufacture_year') : null,
                    Http::str('colour') ?: null, Http::str('transmission') ?: 'manual', Http::str('fuel_type') ?: 'petrol',
                    Http::str('daily_rate'), Http::str('weekly_rate') !== '' ? Http::str('weekly_rate') : null,
                    Http::str('monthly_rate') !== '' ? Http::str('monthly_rate') : null,
                    Http::int('current_mileage'), Http::str('fuel_level_percent') !== '' ? Http::str('fuel_level_percent') : null,
                    $image, Http::str('status') ?: 'available',
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
        View::render('vehicles/form', ['title' => $vehicle ? 'Edit vehicle' : 'Add vehicle', 'vehicle' => $vehicle, 'categories' => $categories]);
    }

    public function show(): void
    {
        Rbac::require('vehicles.view');
        $id = Http::int('id');
        $vehicle = Database::fetch(
            'SELECT v.*, c.category_name FROM vehicles v JOIN vehicle_categories c ON c.category_id = v.category_id WHERE v.vehicle_id = ?',
            [$id]
        );
        if (!$vehicle) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        $history = Database::fetchAll(
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
        $note = Http::str('flag_note');
        Database::execute("UPDATE vehicles SET status = 'maintenance' WHERE vehicle_id = ?", [$id]);
        Audit::log('vehicle.flagged_unsafe', 'vehicle', (string) $id, trim($vehicle['registration_no'] . ' ' . $note));
        Http::flash('success', $vehicle['registration_no'] . ' is flagged unavailable until maintenance is cleared.');
        Http::redirect('vehicles/show', ['id' => $id]);
    }
}
