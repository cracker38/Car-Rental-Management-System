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
            'title' => 'Book a car',
            'categories' => $categories,
            'results' => $results,
            'quotes' => $quotes,
            'pickup' => $pickup,
            'returnAt' => $returnAt,
            'categoryId' => $categoryId,
            'error' => $error,
            'fullBleed' => true,
        ], 'site');
    }

    public function dashboard(): void
    {
        Auth::requireLogin();
        Http::redirect('home');
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
        View::render('home/blog', ['title' => 'Rwanda journal', 'fullBleed' => true], 'site');
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
