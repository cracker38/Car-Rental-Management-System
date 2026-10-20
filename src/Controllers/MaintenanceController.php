<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Database;
use Crms\Auth;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\MaintenanceService;
use InvalidArgumentException;

final class MaintenanceController
{
    public function index(): void
    {
        $inspectorMode = (Auth::user()['role_name'] ?? '') === 'Vehicle Inspector/Maintenance Staff';
        $canManage = Rbac::can('maintenance.manage');
        $canSchedule = $canManage && !$inspectorMode;
        if (!$canManage) {
            Rbac::require('maintenance.view');
        }
        if (Http::isPost()) {
            Rbac::require('maintenance.manage');
        }
        if (Http::isPost() && Http::str('action') === 'create') {
            if (!$canSchedule) {
                http_response_code(403);
                View::render('errors/403', ['title' => 'Forbidden']);
                return;
            }
            try {
                (new MaintenanceService())->create($_POST);
                Http::flash('success', 'Maintenance job scheduled. The vehicle is unavailable while the job is open.');
                Http::redirect('maintenance');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
        }
        if (Http::isPost() && Http::str('action') === 'details') {
            try {
                (new MaintenanceService())->updateDetails(Http::int('id'), $_POST);
                Http::flash('success', 'Service details saved.');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
            Http::redirect('maintenance');
        }
        if (Http::isPost() && Http::str('action') === 'status') {
            try {
                (new MaintenanceService())->updateStatus(
                    Http::int('id'),
                    Http::str('new_status'),
                    Http::str('reason'),
                    Http::str('vehicle_safe') === 'yes',
                    Http::str('vehicle_status')
                );
                Http::flash('success', 'Maintenance status updated.');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
            Http::redirect('maintenance');
        }
        if (Http::isPost() && Http::str('action') === 'vehicle_readiness') {
            try {
                (new MaintenanceService())->updateVehicleReadiness(
                    Http::int('vehicle_id'),
                    Http::str('vehicle_status'),
                    Http::str('reason')
                );
                Http::flash('success', 'Vehicle readiness was updated. Active rentals and reservations retain their correct operational status.');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
            Http::redirect('maintenance');
        }
        $vehicleScope = $inspectorMode ? " WHERE v.status = 'maintenance'" : '';
        $rows = Database::fetchAll(
            'SELECT m.*, v.registration_no FROM maintenance_records m
             JOIN vehicles v ON v.vehicle_id = m.vehicle_id' . $vehicleScope . ' ORDER BY
               CASE WHEN m.status IN (\'scheduled\',\'in_progress\') THEN 0 ELSE 1 END,
               COALESCE(m.expected_completion_date, m.start_date) ASC, m.created_at DESC'
        );
        $maintenanceVehicles = Database::fetchAll(
            "SELECT v.vehicle_id, v.registration_no, v.make, v.model, v.status,
                    (SELECT COUNT(*) FROM maintenance_records m WHERE m.vehicle_id=v.vehicle_id AND m.status IN ('scheduled','in_progress')) AS open_jobs
             FROM vehicles v WHERE v.status='maintenance' ORDER BY v.registration_no"
        );
        $vehicles = $canSchedule ? Database::fetchAll("SELECT vehicle_id, registration_no, status FROM vehicles WHERE status <> 'inactive' ORDER BY registration_no") : [];
        View::render('maintenance/index', [
            'title' => 'Maintenance',
            'rows' => $rows,
            'vehicles' => $vehicles,
            'maintenanceVehicles' => $maintenanceVehicles,
            'canManage' => $canManage,
            'canSchedule' => $canSchedule,
            'inspectorMode' => $inspectorMode,
        ]);
    }
}
