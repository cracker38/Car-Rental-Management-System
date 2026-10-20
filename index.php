<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use Crms\Controllers\AdminController;
use Crms\Controllers\AuthController;
use Crms\Controllers\CustomerController;
use Crms\Controllers\DriverController;
use Crms\Controllers\FinanceController;
use Crms\Controllers\HomeController;
use Crms\Controllers\InvoiceController;
use Crms\Controllers\MaintenanceController;
use Crms\Controllers\NotificationController;
use Crms\Controllers\PaymentController;
use Crms\Controllers\RentalController;
use Crms\Controllers\ReservationController;
use Crms\Controllers\VehicleController;
use Crms\Auth;
use Crms\View;

$route = $_GET['r'] ?? 'home';
$route = is_string($route) ? $route : 'home';

$routes = [
    'home' => [HomeController::class, 'index'],
    'search' => [HomeController::class, 'search'],
    'fleet' => [HomeController::class, 'fleet'],
    'about' => [HomeController::class, 'about'],
    'services' => [HomeController::class, 'services'],
    'blog' => [HomeController::class, 'blog'],
    'contact' => [HomeController::class, 'contact'],
    'dashboard' => [HomeController::class, 'dashboard'],
    'login' => [AuthController::class, 'login'],
    'password/change' => [AuthController::class, 'changePassword'],
    'register' => [AuthController::class, 'register'],
    'logout' => [AuthController::class, 'logout'],
    'vehicles' => [VehicleController::class, 'index'],
    'vehicles/form' => [VehicleController::class, 'form'],
    'vehicles/show' => [VehicleController::class, 'show'],
    'vehicles/flag' => [VehicleController::class, 'flag'],
    'customers' => [CustomerController::class, 'index'],
    'customers/form' => [CustomerController::class, 'form'],
    'customers/profile' => [CustomerController::class, 'profile'],
    'drivers' => [DriverController::class, 'index'],
    'drivers/form' => [DriverController::class, 'form'],
    'drivers/assign' => [DriverController::class, 'assign'],
    'drivers/schedule' => [DriverController::class, 'schedule'],
    'drivers/trip' => [DriverController::class, 'trip'],
    'reservations' => [ReservationController::class, 'index'],
    'reservations/form' => [ReservationController::class, 'form'],
    'reservations/show' => [ReservationController::class, 'show'],
    'reservations/transition' => [ReservationController::class, 'transition'],
    'reservations/decision' => [ReservationController::class, 'decision'],
    'rentals' => [RentalController::class, 'index'],
    'rentals/show' => [RentalController::class, 'show'],
    'rentals/create' => [RentalController::class, 'create'],
    'rentals/pickup' => [RentalController::class, 'pickup'],
    'rentals/return' => [RentalController::class, 'returnVehicle'],
    'rentals/inspect' => [RentalController::class, 'inspect'],
    'rentals/inspect-pickup' => [RentalController::class, 'inspectPickup'],
    'rentals/close' => [RentalController::class, 'close'],
    'rentals/cancel' => [RentalController::class, 'cancel'],
    'invoices' => [InvoiceController::class, 'index'],
    'invoices/show' => [InvoiceController::class, 'show'],
    'invoices/create' => [InvoiceController::class, 'create'],
    'invoices/issue' => [InvoiceController::class, 'issue'],
    'finance' => [FinanceController::class, 'index'],
    'finance/form' => [FinanceController::class, 'form'],
    'finance/approvals' => [FinanceController::class, 'approvals'],
    'finance/review' => [FinanceController::class, 'review'],
    'finance/reconcile' => [FinanceController::class, 'reconcile'],
    'payments/momo' => [PaymentController::class, 'momo'],
    'payments/sync' => [PaymentController::class, 'sync'],
    'payments/status' => [PaymentController::class, 'status'],
    'payments/receipt' => [PaymentController::class, 'receipt'],
    'notifications/open' => [NotificationController::class, 'open'],
    'notifications/poll' => [NotificationController::class, 'poll'],
    'maintenance' => [MaintenanceController::class, 'index'],
    'admin/users' => [AdminController::class, 'users'],
    'admin/roles' => [AdminController::class, 'roles'],
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

if (Auth::check() && Auth::mustChangePassword() && !in_array($route, ['password/change', 'logout'], true)) {
    \Crms\Http::redirect('password/change');
}

[$class, $method] = $routes[$route];
(new $class())->$method();
