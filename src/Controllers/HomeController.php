<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\View;
use Crms\Services\AvailabilityService;
use Crms\Services\PricingService;
use Crms\Services\DashboardService;
use Crms\Services\EmailService;

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
                 WHERE v.status <> 'inactive' ORDER BY v.daily_rate ASC, v.created_at DESC LIMIT 12"
            );
        } catch (\Throwable) {
            Http::flash('warning', 'Database is not ready. Run install.php first.');
        }
        View::render('home/index', [
            'title' => 'Home',
            'categories' => $categories,
            'vehicles' => $vehicles,
            'fullBleed' => true,
        ], 'site');
    }

    public function search(): void
    {
        $pickup = Http::datetime('pickup_datetime');
        $returnAt = Http::datetime('return_datetime');
        $categoryId = Http::int('category_id', 0) ?: null;
        $sort = Http::str('sort', 'recommended');
        if (!in_array($sort, ['recommended', 'price_low', 'price_high'], true)) {
            $sort = 'recommended';
        }
        $results = [];
        $quotes = [];
        $error = null;
        $filters = [
            'model' => substr(trim(Http::str('model')), 0, 80),
            'min_rate' => trim(Http::str('min_rate')),
            'max_rate' => trim(Http::str('max_rate')),
            'fuel_type' => Http::str('fuel_type'),
            'transmission' => Http::str('transmission'),
        ];
        if (($pickup === '') !== ($returnAt === '')) {
            $error = 'Choose both a pick-up time and a return time.';
        }
        foreach (['min_rate', 'max_rate'] as $rateKey) {
            $rate = $filters[$rateKey];
            if ($rate !== '' && (!is_numeric($rate) || (float) $rate < 0 || (float) $rate > 999999999999)) {
                $error = 'Enter a valid non-negative daily price.';
                break;
            }
            if ($rate !== '') {
                $filters[$rateKey] = number_format((float) $rate, 2, '.', '');
            }
        }
        if ($error === null && $filters['min_rate'] !== '' && $filters['max_rate'] !== '' && (float) $filters['min_rate'] > (float) $filters['max_rate']) {
            $error = 'The minimum daily price cannot exceed the maximum.';
        }
        if (!in_array($filters['fuel_type'], ['', 'petrol', 'diesel', 'hybrid', 'electric', 'other'], true)) {
            $filters['fuel_type'] = '';
        }
        if (!in_array($filters['transmission'], ['', 'manual', 'automatic', 'other'], true)) {
            $filters['transmission'] = '';
        }
        if ($pickup && $returnAt && $error === null) {
            try {
                $results = (new AvailabilityService())->search($pickup, $returnAt, $categoryId, $filters, $sort);
                $pricing = new PricingService();
                foreach ($results as $v) {
                    $quotes[(int) $v['vehicle_id']] = $pricing->quoteRates($v, $pickup, $returnAt);
                }
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (\Throwable) {
                $error = 'We could not check availability right now. Please try again or contact our Kigali team.';
            }
        } elseif ($pickup === '' && $returnAt === '' && $error === null) {
            try {
                $results = (new AvailabilityService())->browse($categoryId, $filters, $sort);
            } catch (\Throwable) {
                $error = 'The available fleet could not be loaded right now. Please try again shortly.';
            }
        }
        $categories = [];
        $maxDailyRate = 200000;
        try {
            $categories = Database::fetchAll('SELECT * FROM vehicle_categories WHERE is_active = 1 ORDER BY category_name');
            $rateRow = Database::fetch("SELECT COALESCE(MAX(daily_rate), 0) AS max_rate FROM vehicles WHERE status NOT IN ('inactive','maintenance')");
            $maxDailyRate = max(200000, (int) (ceil(((float) ($rateRow['max_rate'] ?? 0)) / 10000) * 10000));
        } catch (\Throwable) {
            $error ??= 'Car search is temporarily unavailable. Please contact our Kigali team for help.';
        }
        View::render('home/search', [
            'title' => 'Browse cars',
            'categories' => $categories,
            'results' => $results,
            'quotes' => $quotes,
            'pickup' => $pickup,
            'returnAt' => $returnAt,
            'categoryId' => $categoryId,
            'filters' => $filters,
            'sort' => $sort,
            'maxDailyRate' => $maxDailyRate,
            'error' => $error,
            'fullBleed' => true,
        ], 'site');
    }

    public function dashboard(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        $role = (string) ($user['role_name'] ?? '');
        $dashboardService = new DashboardService();
        try {
            $data = $dashboardService->data($user ?? []);
        } catch (\Throwable $error) {
            error_log('Dashboard data loading failed: ' . $error->getMessage());
            $data = $dashboardService->fallbackData($user ?? []);
            Http::flash('warning', 'Some dashboard figures could not be loaded. Check the database connection.');
        }
        View::render(DashboardService::templateFor($role), $data + ['title' => DashboardService::pageTitle($role)]);
    }

    public function about(): void
    {
        View::render('home/about', ['title' => 'About', 'fullBleed' => true], 'site');
    }

    public function services(): void
    {
        View::render('home/services', ['title' => 'Services', 'fullBleed' => true], 'site');
    }

    public function blog(): void
    {
        View::render('home/blog', [
            'title' => 'Rwanda travel journal',
            'metaDescription' => 'Practical travel inspiration, local car hire guidance, and trip-planning tips for exploring Rwanda from Kigali.',
            'fullBleed' => true,
        ], 'site');
    }

    public function contact(): void
    {
        if (Http::isPost()) {
            $name = trim(substr(Http::str('name'), 0, 120));
            $contact = trim(substr(Http::str('contact'), 0, 190));
            $message = trim(substr(Http::str('message'), 0, 5000));
            if ($name === '' || $contact === '' || $message === '') {
                Http::flash('danger', 'Please complete the contact form with your name, email or phone, and message.');
            } else {
                try {
                    (new EmailService())->sendContactEnquiry($name, $contact, $message);
                    Http::flash('success', 'Thank you. We have received your message.');
                    Http::redirect('contact');
                } catch (\Throwable $error) {
                    error_log('Contact form email failed: ' . $error->getMessage());
                    Http::flash('danger', 'Could not send the message. Try again later.');
                }
            }
        }
        View::render('home/contact', ['title' => 'Contact', 'fullBleed' => true], 'site');
    }

    public function fleet(): void
    {
        $vehicles = [];
        $categories = [];
        try {
            $categories = Database::fetchAll('SELECT * FROM vehicle_categories WHERE is_active = 1 ORDER BY category_name');
            $vehicles = Database::fetchAll(
                "SELECT v.*, c.category_name FROM vehicles v
                 JOIN vehicle_categories c ON c.category_id = v.category_id
                 WHERE v.status <> 'inactive' ORDER BY c.category_name, v.daily_rate"
            );
        } catch (\Throwable) {
            Http::flash('warning', 'Database is not ready. Run install.php first.');
        }
        View::render('home/fleet', [
            'title' => 'Our fleet',
            'vehicles' => $vehicles,
            'categories' => $categories,
            'fullBleed' => true,
        ], 'site');
    }
}
