<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use Crms\Controllers\AdminController;
use Crms\Controllers\AuthController;
use Crms\Controllers\CustomerController;
use Crms\Controllers\FinanceController;
use Crms\Controllers\HomeController;
use Crms\Controllers\InvoiceController;
use Crms\Controllers\MaintenanceController;
use Crms\Controllers\PaymentController;
use Crms\Controllers\RentalController;
use Crms\Controllers\ReservationController;
use Crms\Controllers\VehicleController;
use Crms\View;

$route = $_GET['r'] ?? 'home';
$route = is_string($route) ? $route : 'home';

$routes = [
    'home' => [HomeController::class, 'index'],
    'search' => [HomeController::class, 'search'],
    'dashboard' => [HomeController::class, 'dashboard'],
    'login' => [AuthController::class, 'login'],
    'register' => [AuthController::class, 'register'],
    'logout' => [AuthController::class, 'logout'],
    'vehicles' => [VehicleController::class, 'index'],
    'vehicles/form' => [VehicleController::class, 'form'],
    'vehicles/show' => [VehicleController::class, 'show'],
    'customers' => [CustomerController::class, 'index'],
    'customers/form' => [CustomerController::class, 'form'],
    'reservations' => [ReservationController::class, 'index'],
    'reservations/form' => [ReservationController::class, 'form'],
    'reservations/show' => [ReservationController::class, 'show'],
    'reservations/transition' => [ReservationController::class, 'transition'],
    'rentals' => [RentalController::class, 'index'],
    'rentals/show' => [RentalController::class, 'show'],
    'rentals/create' => [RentalController::class, 'create'],
    'rentals/pickup' => [RentalController::class, 'pickup'],
    'rentals/return' => [RentalController::class, 'returnVehicle'],
    'rentals/inspect' => [RentalController::class, 'inspect'],
    'rentals/close' => [RentalController::class, 'close'],
    'invoices' => [InvoiceController::class, 'index'],
    'invoices/show' => [InvoiceController::class, 'show'],
    'invoices/issue' => [InvoiceController::class, 'issue'],
    'finance' => [FinanceController::class, 'index'],
    'finance/form' => [FinanceController::class, 'form'],
    'payments/momo' => [PaymentController::class, 'momo'],
    'payments/sync' => [PaymentController::class, 'sync'],
    'maintenance' => [MaintenanceController::class, 'index'],
    'admin/users' => [AdminController::class, 'users'],
    'admin/settings' => [AdminController::class, 'settings'],
    'admin/audit' => [AdminController::class, 'audit'],
    'admin/backup' => [AdminController::class, 'backup'],
    'reports' => [AdminController::class, 'reports'],
];

if (!isset($routes[$route])) {
    http_response_code(404);
    View::render('errors/404', ['title' => 'Not found']);
    exit;
}

[$class, $method] = $routes[$route];
(new $class())->$method();
