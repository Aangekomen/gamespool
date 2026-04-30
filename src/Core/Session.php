<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_name('gamespool_sess');
            session_start();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::ensureWritable();
        $_SESSION[$key] = $value;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        if (isset($_SESSION[$key])) {
            self::ensureWritable();
            unset($_SESSION[$key]);
        }
        return $value;
    }

    public static function flash(string $key, mixed $value): void
    {
        self::ensureWritable();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        if (isset($_SESSION[$key])) {
            self::ensureWritable();
            unset($_SESSION[$key]);
        }
    }

    /**
     * Re-open de sessie als die door App::run gesloten is voor performance.
     * PHP file-sessions locken; we sluiten ze meteen na auth bij GET-requests
     * en re-locken pas op het moment dat we daadwerkelijk willen schrijven.
     */
    private static function ensureWritable(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
