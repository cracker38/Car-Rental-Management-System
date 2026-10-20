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
            Http::redirect(Auth::mustChangePassword() ? 'password/change' : 'dashboard');
        }
        if (Http::isPost()) {
            $email = strtolower(Http::str('email'));
            $password = (string) ($_POST['password'] ?? '');
            if (Auth::login($email, $password)) {
                Http::clearOld();
                Http::redirect(Auth::mustChangePassword() ? 'password/change' : 'dashboard');
            }
        }
        View::render('auth/login', ['title' => 'Sign in', 'fullBleed' => true], 'site');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $mustChangePassword = Auth::mustChangePassword();

        if (Http::isPost()) {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($currentPassword === '') {
                Http::flash('danger', 'Enter your current password.');
            } elseif (strlen($newPassword) < 8) {
                Http::flash('danger', 'Use at least 8 characters for your new password.');
            } elseif ($newPassword !== $confirmPassword) {
                Http::flash('danger', 'The new passwords do not match.');
            } else {
                try {
                    Database::transaction(function () use ($userId, $currentPassword, $newPassword): void {
                        $account = Database::fetch('SELECT password_hash FROM users WHERE user_id = ? FOR UPDATE', [$userId]);
                        if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
                            throw new \InvalidArgumentException('Your current password is incorrect.');
                        }
                        if (password_verify($newPassword, $account['password_hash'])) {
                            throw new \InvalidArgumentException('Choose a new password different from your current password.');
                        }

                        Database::execute(
                            'UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?',
                            [password_hash($newPassword, PASSWORD_DEFAULT), $userId]
                        );
                        Audit::log('user.password_changed', 'user', (string) $userId, 'Account password changed');
                    });
                    Http::flash('success', 'Your password has been changed.');
                    Http::redirect('dashboard');
                } catch (\InvalidArgumentException $e) {
                    Http::flash('danger', $e->getMessage());
                } catch (\Throwable) {
                    Http::flash('danger', 'Could not change your password. Please try again.');
                }
            }
        }

        View::render('auth/change-password', [
            'title' => 'Change password',
            'mustChangePassword' => $mustChangePassword,
        ]);
    }

    public function register(): void
    {
        if (Auth::check()) {
            Http::redirect('home');
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
        View::render('auth/register', ['title' => 'Create account', 'fullBleed' => true], 'site');
    }

    public function logout(): void
    {
        if (Http::isPost()) {
            Auth::logout();
            session_start();
            Csrf::token();
            Http::flash('success', 'You have signed out.');
        }
        Http::redirect('home');
    }
}
