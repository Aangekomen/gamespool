<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;
use GamesPool\Models\Company;

class AuthController
{
    public function showRegister(): string
    {
        if (Auth::check()) redirect('/');
        return view('auth/register', [
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function register(): void
    {
        $data = [
            'first_name'            => trim((string) ($_POST['first_name'] ?? '')),
            'last_name'             => trim((string) ($_POST['last_name'] ?? '')),
            'email'                 => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'company'               => trim((string) ($_POST['company'] ?? '')),
            'password'              => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];

        $v = (new Validator($data))
            ->required('first_name')->min('first_name', 2)->max('first_name', 80)
            ->required('last_name')->min('last_name', 2)->max('last_name', 80)
            ->required('email')->email('email')->max('email', 190)
            ->max('company', 150)
            ->required('password')->min('password', 8)
            ->matches('password', 'password_confirmation', 'Wachtwoorden komen niet overeen');

        $errors = $v->errors();
        $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$data['email']]);
        if ($existing) {
            $errors['email'][] = 'Dit e-mailadres is al in gebruik';
        }

        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            unset($data['password'], $data['password_confirmation']);
            Session::flash('_old', $data);
            redirect('/register');
        }

        $companyId = null;
        if ($data['company'] !== '') {
            $company = Company::findOrCreate($data['company']);
            $companyId = $company ? (int) $company['id'] : null;
        }

        // First registered user becomes admin automatically
        $userCount = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
        $isAdmin   = $userCount === 0 ? 1 : 0;

        $displayName = trim($data['first_name'] . ' ' . $data['last_name']);

        $id = Database::insert(
            'INSERT INTO users (email, first_name, last_name, company_id, display_name, password_hash, is_admin)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $companyId,
                $displayName,
                password_hash($data['password'], PASSWORD_DEFAULT),
                $isAdmin,
            ]
        );
        Auth::login($id);
        redirect('/');
    }

    public function showLogin(): string
    {
        if (Auth::check()) redirect('/');
        return view('auth/login', [
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function login(): void
    {
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::attempt($email, $password)) {
            Session::flash('_errors', ['email' => ['E-mailadres of wachtwoord onjuist']]);
            Session::flash('_old', ['email' => $email]);
            redirect('/login');
        }
        $intended = Session::pull('_flash.intended', '/');
        redirect(is_string($intended) ? $intended : '/');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
