<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Audit;
use Crms\Auth;
use Crms\Database;
use Crms\Http;
use Crms\Phone;
use Crms\Rbac;
use Crms\Validator;
use Crms\View;

final class CustomerController
{
    public function index(): void
    {
        Rbac::require('customers.view');
        $q = Http::str('q');
        $sql = 'SELECT * FROM customers WHERE 1=1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (customer_code LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like, $like];
        }
        $sql .= ' ORDER BY created_at DESC';
        View::render('customers/index', ['title' => 'Customers', 'customers' => Database::fetchAll($sql, $params), 'q' => $q]);
    }

    public function form(): void
    {
        Rbac::require('customers.manage');
        $id = Http::int('id');
        $customer = $id ? Database::fetch('SELECT * FROM customers WHERE customer_id = ?', [$id]) : null;
        if (Http::isPost()) {
            $v = new Validator();
            $v->require($_POST, ['first_name', 'last_name', 'phone']);
            $v->in('status', Http::str('status'), ['active', 'inactive', 'blacklisted']);
            if ($v->ok()) {
                $fields = [
                    Http::str('first_name'), Http::str('last_name'), Http::str('phone'), Http::str('email') ?: null,
                    Http::str('address') ?: null, Http::str('identification_type') ?: null,
                    Http::str('identification_number') ?: null, Http::str('driving_license_number') ?: null,
                    Http::str('license_expiry_date') ?: null, Http::str('status') ?: 'active',
                ];
                try {
                    if ($customer) {
                        Database::execute(
                            'UPDATE customers SET first_name=?, last_name=?, phone=?, email=?, address=?, identification_type=?, identification_number=?, driving_license_number=?, license_expiry_date=?, status=? WHERE customer_id=?',
                            [...$fields, $id]
                        );
                        Audit::log('customer.updated', 'customer', (string) $id);
                        Http::flash('success', 'Customer updated.');
                    } else {
                        $code = 'CUS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                        Database::execute(
                            'INSERT INTO customers (customer_code, first_name, last_name, phone, email, address, identification_type, identification_number, driving_license_number, license_expiry_date, status, created_by)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                            [$code, ...$fields, Auth::id()]
                        );
                        Audit::log('customer.created', 'customer', Database::lastId(), $code);
                        Http::flash('success', 'Customer created.');
                    }
                    Http::redirect('customers');
                } catch (\Throwable) {
                    Http::flash('danger', 'Could not save the customer. Check unique identity details.');
                }
            }
        }
        View::render('customers/form', ['title' => $customer ? 'Edit customer' : 'Add customer', 'customer' => $customer]);
    }

    public function profile(): void
    {
        Auth::requireLogin();
        if (!Auth::isCustomer()) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Forbidden']);
            return;
        }
        $cid = Auth::customerId();
        if (!$cid) {
            Http::flash('danger', 'Your login is not linked to a customer record.');
            Http::redirect('dashboard');
        }
        $customer = Database::fetch('SELECT * FROM customers WHERE customer_id = ?', [$cid]);
        if (!$customer) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Not found']);
            return;
        }
        if (Http::isPost()) {
            $v = new Validator();
            $v->require($_POST, ['first_name', 'last_name', 'phone']);
            $phone = Phone::rwandaMobile(Http::str('phone'));
            if ($phone === null) {
                Http::flash('danger', 'Enter a valid Rwanda mobile number.');
            } elseif ($v->ok()) {
                try {
                    Database::execute(
                        'UPDATE customers SET first_name=?, last_name=?, phone=?, email=?, address=?, identification_type=?, identification_number=?, driving_license_number=?, license_expiry_date=? WHERE customer_id=?',
                        [
                            Http::str('first_name'), Http::str('last_name'), $phone,
                            Http::str('email') ?: null, Http::str('address') ?: null,
                            Http::str('identification_type') ?: null, Http::str('identification_number') ?: null,
                            Http::str('driving_license_number') ?: null, Http::str('license_expiry_date') ?: null,
                            $cid,
                        ]
                    );
                    Database::execute(
                        'UPDATE users SET first_name=?, last_name=?, email=COALESCE(NULLIF(?, \'\'), email) WHERE user_id=?',
                        [Http::str('first_name'), Http::str('last_name'), Http::str('email'), Auth::id()]
                    );
                    Audit::log('customer.self_updated', 'customer', (string) $cid);
                    Http::flash('success', 'Your profile was updated.');
                    Http::redirect('customers/profile');
                } catch (\Throwable) {
                    Http::flash('danger', 'Could not save your profile. Check that the email is unique.');
                }
            }
        }
        View::render('customers/profile', ['title' => 'My profile', 'customer' => $customer]);
    }
}
