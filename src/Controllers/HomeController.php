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
                $results = (new AvailabilityService())->search($pickup, $returnAt, $categoryId, $filters);
                $pricing = new PricingService();
                foreach ($results as $v) {
                    $quotes[(int) $v['vehicle_id']] = $pricing->quoteRates($v, $pickup, $returnAt);
                }
            } catch (\Throwable) {
                $error = 'We could not check availability right now. Please try again or contact our Kigali team.';
            }
        }
        $categories = [];
        try {
            $categories = Database::fetchAll('SELECT * FROM vehicle_categories WHERE is_active = 1 ORDER BY category_name');
        } catch (\Throwable) {
            $error ??= 'Car search is temporarily unavailable. Please contact our Kigali team for help.';
        }
        View::render('home/search', [
            'title' => 'Book a car',
            'categories' => $categories,
            'results' => $results,
            'quotes' => $quotes,
            'pickup' => $pickup,
            'returnAt' => $returnAt,
            'categoryId' => $categoryId,
            'filters' => $filters,
            'error' => $error,
            'fullBleed' => true,
        ], 'site');
    }

    public function dashboard(): void
    {
        Auth::requireLogin();
        $user = Auth::user();
        $role = (string) ($user['role_name'] ?? '');
        try {
            $data = (new DashboardService())->data($user ?? []);
        } catch (\Throwable) {
            $data = ['user' => $user, 'notes' => [], 'role' => $role, 'shortcuts' => [], 'kpis' => []];
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
            $name = Http::str('name');
            $contact = Http::str('contact');
            $message = Http::str('message');
            if ($name === '' || $contact === '' || $message === '') {
                Http::flash('danger', 'Please complete the contact form.');
            } else {
                try {
                    Database::execute(
                        'INSERT INTO notifications (channel, notification_type, subject, message, status)
                         VALUES (\'in_app\',\'contact_form\',?,?,\'queued\')',
                        ['Website enquiry from ' . $name, $contact . "\n" . $message]
                    );
                    Http::flash('success', 'Thank you. We have received your message.');
                    Http::redirect('contact');
                } catch (\Throwable) {
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
