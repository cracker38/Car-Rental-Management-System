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
        $rows = Database::fetchAll(
            'SELECT m.*, v.registration_no FROM maintenance_records m
             JOIN vehicles v ON v.vehicle_id = m.vehicle_id ORDER BY m.created_at DESC'
        );
        $vehicles = $canManage ? Database::fetchAll('SELECT vehicle_id, registration_no FROM vehicles ORDER BY registration_no') : [];
        if (Http::isPost() && Http::str('action') === 'create') {
            try {
                (new MaintenanceService())->create($_POST);
                Http::flash('success', 'Maintenance recorded. The vehicle is blocked while work is open.');
                Http::redirect('maintenance');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
        }
        if (Http::isPost() && Http::str('action') === 'complete') {
            try {
                (new MaintenanceService())->complete(Http::int('id'));
                Http::flash('success', 'Maintenance completed.');
            } catch (InvalidArgumentException $e) {
                Http::flash('danger', $e->getMessage());
            }
            Http::redirect('maintenance');
        }
        View::render('maintenance/index', ['title' => 'Maintenance', 'rows' => $rows, 'vehicles' => $vehicles, 'canManage' => $canManage]);
    }
}
