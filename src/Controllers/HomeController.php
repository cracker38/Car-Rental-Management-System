<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\View;
use Crms\Services\AvailabilityService;
use Crms\Services\PricingService;

final class HomeController
{
    public function index(): void
    {
        $categories = [];
        $vehicles = [];
        try {
            $categories = Database::fetchAll('SELECT * FROM vehicle_categories WHERE is_active = 1 ORDER BY category_name');
            $vehicles = Database::fetchAll(
                "SELECT v.*, c.category_name FROM vehicles v
                 JOIN vehicle_categories c ON c.category_id = v.category_id
                 WHERE v.status <> 'inactive' ORDER BY v.created_at DESC LIMIT 8"
            );
        } catch (\Throwable) {
            Http::flash('warning', 'Database is not ready. Run install.php first.');
        }
        View::render('home/index', [
            'title' => 'Car Rental Management System',
            'categories' => $categories,
            'vehicles' => $vehicles,
        ], 'public');
    }

    public function search(): void
    {
        $pickup = Http::datetime('pickup_datetime');
        $returnAt = Http::datetime('return_datetime');
        $categoryId = Http::int('category_id', 0) ?: null;
        $results = [];
        $quotes = [];
        $error = null;
        if ($pickup && $returnAt) {
            try {
                $results = (new AvailabilityService())->search($pickup, $returnAt, $categoryId);
                $pricing = new PricingService();
                foreach ($results as $v) {
                    $quotes[(int) $v['vehicle_id']] = $pricing->quoteRates($v, $pickup, $returnAt);
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        $categories = Database::fetchAll('SELECT * FROM vehicle_categories WHERE is_active = 1 ORDER BY category_name');
        View::render('home/search', [
            'title' => 'Search vehicles',
            'categories' => $categories,
            'results' => $results,
            'quotes' => $quotes,
            'pickup' => $pickup,
            'returnAt' => $returnAt,
            'categoryId' => $categoryId,
            'error' => $error,
        ], Auth::check() ? 'main' : 'public');
    }

    public function dashboard(): void
    {
        Auth::requireLogin();
        $stats = [
            'vehicles' => Database::fetch("SELECT COUNT(*) c FROM vehicles WHERE status <> 'inactive'")['c'] ?? 0,
            'reservations' => Database::fetch("SELECT COUNT(*) c FROM reservations WHERE status IN ('pending','confirmed')")['c'] ?? 0,
            'active_rentals' => Database::fetch("SELECT COUNT(*) c FROM rentals WHERE status IN ('active','overdue')")['c'] ?? 0,
            'outstanding' => Database::fetch("SELECT COALESCE(SUM(outstanding_balance),0) s FROM vw_invoice_balances WHERE outstanding_balance > 0")['s'] ?? 0,
        ];
        $mine = [];
        if (Auth::isCustomer() && Auth::customerId()) {
            $mine = Database::fetchAll(
                'SELECT * FROM reservations WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10',
                [Auth::customerId()]
            );
        }
        $notes = Database::fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? OR customer_id = ? ORDER BY created_at DESC LIMIT 8',
            [Auth::id(), Auth::customerId()]
        );
        View::render('home/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'mine' => $mine,
            'notes' => $notes,
        ]);
    }
}
