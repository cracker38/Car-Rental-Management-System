<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Database;
use Crms\Http;
use Crms\Rbac;
use Crms\View;
use Crms\Services\MaintenanceService;
use InvalidArgumentException;

final class MaintenanceController
{
    public function index(): void
    {
        $canManage = Rbac::can('maintenance.manage');
        if (!$canManage) {
            Rbac::require('maintenance.view');
        }
        if (Http::isPost()) {
            Rbac::require('maintenance.manage');
        }
        if (Http::isPost() && Http::str('action') === 'create') {
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
                    Http::str('vehicle_safe') === 'yes'
                );
                Http::flash('success', 'Maintenance status updated.');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
            Http::redirect('maintenance');
        }
        $rows = Database::fetchAll(
            'SELECT m.*, v.registration_no FROM maintenance_records m
             JOIN vehicles v ON v.vehicle_id = m.vehicle_id ORDER BY
               CASE WHEN m.status IN (\'scheduled\',\'in_progress\') THEN 0 ELSE 1 END,
               COALESCE(m.expected_completion_date, m.start_date) ASC, m.created_at DESC'
        );
        $vehicles = $canManage ? Database::fetchAll('SELECT vehicle_id, registration_no, status FROM vehicles WHERE status <> \'inactive\' ORDER BY registration_no') : [];
        View::render('maintenance/index', ['title' => 'Maintenance', 'rows' => $rows, 'vehicles' => $vehicles, 'canManage' => $canManage]);
    }
}
