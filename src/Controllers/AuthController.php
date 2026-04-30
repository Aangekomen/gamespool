<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;

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
            'display_name'          => trim((string) ($_POST['display_name'] ?? '')),
            'email'                 => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'password'              => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
        ];

        $v = (new Validator($data))
            ->required('display_name')->min('display_name', 2)->max('display_name', 80)
            ->required('email')->email('email')->max('email', 190)
            ->required('password')->min('password', 8)
            ->matches('password', 'password_confirmation', 'Wachtwoorden komen niet overeen');

        $errors = $v->errors();
        $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$data['email']]);
        if ($existing) {
            $errors['email'][] = 'Dit e-mailadres is al in gebruik';
        }

        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            Session::flash('_old', $data);
            redirect('/register');
        }

        // First registered user becomes admin automatically
        $userCount = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
        $isAdmin   = $userCount === 0 ? 1 : 0;

        $id = Database::insert(
            'INSERT INTO users (email, display_name, password_hash, is_admin) VALUES (?, ?, ?, ?)',
            [$data['email'], $data['display_name'], password_hash($data['password'], PASSWORD_DEFAULT), $isAdmin]
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
