<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Mailer;
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
        $token = bin2hex(random_bytes(32));

        $id = Database::insert(
            'INSERT INTO users (email, first_name, last_name, company_id, display_name, password_hash, is_admin, verification_token)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $companyId,
                $displayName,
                password_hash($data['password'], PASSWORD_DEFAULT),
                $isAdmin,
                $token,
            ]
        );

        $this->sendVerificationMail((int) $id, $data['email'], $data['first_name'], $token);

        Auth::login($id);
        Session::flash('_flash.success', 'Welkom! We hebben een bevestigingsmail naar ' . $data['email'] . ' gestuurd.');
        redirect('/');
    }

    public function verify(string $token): void
    {
        $token = preg_replace('/[^a-f0-9]/', '', $token) ?? '';
        if (strlen($token) < 32) {
            Session::flash('_flash.error', 'Ongeldige verificatielink.');
            redirect('/');
        }
        $user = Database::fetch('SELECT id, email_verified_at FROM users WHERE verification_token = ?', [$token]);
        if (!$user) {
            Session::flash('_flash.error', 'Verificatielink is ongeldig of al gebruikt.');
            redirect('/');
        }
        if ($user['email_verified_at'] !== null) {
            Session::flash('_flash.success', 'E-mailadres was al bevestigd.');
            redirect('/');
        }
        Database::query(
            'UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?',
            [$user['id']]
        );
        Session::flash('_flash.success', 'E-mailadres bevestigd. Bedankt!');
        redirect('/');
    }

    public function resendVerification(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        if (!$u) redirect('/');
        if (!empty($u['email_verified_at'])) {
            Session::flash('_flash.success', 'Je e-mailadres is al bevestigd.');
            redirect('/');
        }
        $token = bin2hex(random_bytes(32));
        Database::query(
            'UPDATE users SET verification_token = ? WHERE id = ?',
            [$token, $u['id']]
        );
        $this->sendVerificationMail((int) $u['id'], (string) $u['email'], (string) ($u['first_name'] ?? ''), $token);
        Session::flash('_flash.success', 'Bevestigingsmail opnieuw verzonden.');
        redirect('/');
    }

    private function sendVerificationMail(int $userId, string $email, string $firstName, string $token): void
    {
        $appName = (string) (function_exists('config') ? config('app.name') : 'FlexiComp');
        $verifyUrl = url('/verify/' . $token);
        $body = "Hallo " . trim($firstName) . ",\n\n"
              . "Bevestig je e-mailadres voor {$appName} via deze link:\n"
              . $verifyUrl . "\n\n"
              . "Heb je geen account aangemaakt? Negeer dan deze mail.\n\n"
              . "— {$appName}";
        Mailer::send($email, "Bevestig je e-mail voor {$appName}", $body);
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

    public function showPasswordReset(string $token): string
    {
        $token = preg_replace('/[^a-f0-9]/', '', $token) ?? '';
        $user  = strlen($token) >= 32
            ? Database::fetch('SELECT id, password_reset_expires_at FROM users WHERE password_reset_token = ?', [$token])
            : null;
        $valid = $user
              && $user['password_reset_expires_at']
              && strtotime((string) $user['password_reset_expires_at']) >= time();
        return view('auth/password_reset', [
            'token'  => $token,
            'valid'  => (bool) $valid,
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function resetPassword(string $token): void
    {
        $token = preg_replace('/[^a-f0-9]/', '', $token) ?? '';
        $user  = strlen($token) >= 32
            ? Database::fetch('SELECT id, password_reset_expires_at FROM users WHERE password_reset_token = ?', [$token])
            : null;
        if (!$user || !$user['password_reset_expires_at'] || strtotime((string) $user['password_reset_expires_at']) < time()) {
            Session::flash('_flash.error', 'De resetlink is ongeldig of verlopen.');
            redirect('/login');
        }

        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');
        $errors = [];
        if (mb_strlen($new) < 8) $errors['new_password'][] = 'Minimaal 8 tekens.';
        if ($new !== $confirm)   $errors['new_password'][] = 'Bevestiging komt niet overeen.';
        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            redirect('/password/reset/' . $token);
        }

        Database::query(
            'UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?',
            [password_hash($new, PASSWORD_DEFAULT), $user['id']]
        );
        Session::flash('_flash.success', 'Wachtwoord ingesteld. Log in met je nieuwe wachtwoord.');
        redirect('/login');
    }
