<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Auth
{
    private const REMEMBER_COOKIE = 'gamespool_remember';
    private const REMEMBER_TTL    = 7 * 24 * 60 * 60; // 7 dagen

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = Database::fetch(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [strtolower(trim($email))]
        );
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::query(
                'UPDATE users SET password_hash = ? WHERE id = ?',
                [password_hash($password, PASSWORD_DEFAULT), $user['id']]
            );
        }
        self::login((int) $user['id']);
        if ($remember) self::issueRememberCookie((int) $user['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        Session::set('user_id', $userId);
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id !== null) {
            Database::query(
                'UPDATE users SET remember_token = NULL, remember_expires_at = NULL WHERE id = ?',
                [$id]
            );
        }
        self::clearRememberCookie();
        Session::destroy();
    }

    public static function check(): bool
    {
        if (Session::get('user_id') !== null) return true;
        return self::tryRememberCookie();
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        if ($id === null) {
            self::tryRememberCookie();
            $id = Session::get('user_id');
        }
        return $id !== null ? (int) $id : null;
    }

    public static function user(): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $id = self::id();
        if ($id === null) {
            return null;
        }
        $cache = Database::fetch(
            'SELECT id, email, first_name, last_name, display_name, avatar_path, is_admin,
                    email_verified_at, created_at
               FROM users WHERE id = ?',
            [$id]
        );
        return $cache;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('_flash.intended', $_SERVER['REQUEST_URI'] ?? '/');
            redirect('/login');
        }
    }

    public static function issueRememberCookie(int $userId): void
    {
        $token   = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_TTL;
        Database::query(
            'UPDATE users SET remember_token = ?, remember_expires_at = ? WHERE id = ?',
            [hash('sha256', $token), date('Y-m-d H:i:s', $expires), $userId]
        );
        setcookie(self::REMEMBER_COOKIE, $userId . ':' . $token, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function tryRememberCookie(): bool
    {
        $raw = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if (!$raw || !str_contains($raw, ':')) return false;
        [$idPart, $token] = explode(':', $raw, 2);
        $userId = (int) $idPart;
        if ($userId <= 0 || strlen($token) < 32) {
            self::clearRememberCookie();
            return false;
        }
        $row = Database::fetch(
            'SELECT id, remember_token, remember_expires_at FROM users WHERE id = ?',
            [$userId]
        );
        if (!$row || empty($row['remember_token']) || empty($row['remember_expires_at'])) {
            self::clearRememberCookie();
            return false;
        }
        if (strtotime((string) $row['remember_expires_at']) < time()) {
            Database::query(
                'UPDATE users SET remember_token = NULL, remember_expires_at = NULL WHERE id = ?',
                [$userId]
            );
            self::clearRememberCookie();
            return false;
        }
        if (!hash_equals((string) $row['remember_token'], hash('sha256', $token))) {
            self::clearRememberCookie();
            return false;
        }
        self::login($userId);
        // Roll the token to prevent replay
        self::issueRememberCookie($userId);
        return true;
    }

    private static function clearRememberCookie(): void
    {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) return;
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }
}
