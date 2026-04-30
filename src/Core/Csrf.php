<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf_token', $token);
        }
        return $token;
    }

    public static function check(?string $token): bool
    {
        $expected = Session::get('_csrf_token');
        return is_string($token) && is_string($expected) && hash_equals($expected, $token);
    }

    public static function verifyOrAbort(): void
    {
        if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!self::check($token)) {
                http_response_code(419);
                echo 'CSRF token mismatch.';
                exit;
            }
        }
    }
}
