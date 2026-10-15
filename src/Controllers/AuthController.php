<?php
declare(strict_types=1);

namespace Crms\Controllers;

use Crms\Audit;
use Crms\Auth;
use Crms\Csrf;
use Crms\Database;
use Crms\Http;
use Crms\Validator;
use Crms\View;

final class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            Http::redirect('dashboard');
        }
        if (Http::isPost()) {
            $email = strtolower(Http::str('email'));
            $password = (string) ($_POST['password'] ?? '');
            if (Auth::login($email, $password)) {
                Http::clearOld();
                Http::redirect('dashboard');
            }
        }
        View::render('auth/login', ['title' => 'Sign in'], 'public');
    }

    public function register(): void
    {
        if (Auth::check()) {
            Http::redirect('dashboard');
        }
        if (Http::isPost()) {
            $v = new Validator();
            $v->require($_POST, ['first_name', 'last_name', 'email', 'phone', 'password', 'password_confirm']);
            $v->email('email', Http::str('email'));
            if (Http::str('password') !== Http::str('password_confirm')) {
                $v->errors['password_confirm'] = 'Passwords do not match.';
            }
            if (strlen(Http::str('password')) < 8) {
                $v->errors['password'] = 'Use at least 8 characters.';
            }
            if ($v->ok()) {
                $email = strtolower(Http::str('email'));
                $exists = Database::fetch('SELECT user_id FROM users WHERE email = ?', [$email]);
                if ($exists) {
                    Http::flash('danger', 'That email is already registered.');
                } else {
                    try {
                        Database::transaction(function () use ($email) {
                            $role = Database::fetch("SELECT role_id FROM roles WHERE role_name = 'Customer'");
                            Database::execute(
                                'INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) VALUES (?,?,?,?,?,?,\'active\')',
                                [
                                    $role['role_id'], Http::str('first_name'), Http::str('last_name'),
                                    $email, Http::str('phone'), password_hash(Http::str('password'), PASSWORD_DEFAULT),
                                ]
                            );
                            $userId = (int) Database::lastId();
                            $code = 'CUS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                            Database::execute(
                                'INSERT INTO customers (linked_user_id, customer_code, first_name, last_name, phone, email, address, identification_type, identification_number, driving_license_number, created_by)
                                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                                [
                                    $userId, $code, Http::str('first_name'), Http::str('last_name'),
                                    Http::str('phone'), $email, Http::str('address') ?: null,
                                    Http::str('identification_type') ?: null, Http::str('identification_number') ?: null,
                                    Http::str('driving_license_number') ?: null, $userId,
                                ]
                            );
                            Audit::log('user.registered', 'user', (string) $userId, $email);
                        });
                        Http::flash('success', 'Account created. You can sign in.');
                        Http::redirect('login');
                    } catch (\Throwable $e) {
                        Http::flash('danger', 'Could not create the account. Check unique identity fields.');
                    }
                }
            } else {
                Http::rememberOld($_POST);
                foreach ($v->errors as $err) {
                    Http::flash('danger', $err);
                    break;
                }
            }
        }
        View::render('auth/register', ['title' => 'Create account'], 'public');
    }

    public function logout(): void
    {
        if (Http::isPost()) {
            Auth::logout();
            session_start();
            Csrf::token();
            Http::flash('success', 'You have signed out.');
        }
        Http::redirect('login');
    }
}
