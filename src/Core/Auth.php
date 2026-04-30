<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Auth
{
    public static function attempt(string $email, string $password): bool
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
        return true;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        Session::set('user_id', $userId);
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
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
            'SELECT id, email, display_name, avatar_path, is_admin, created_at FROM users WHERE id = ?',
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
}
