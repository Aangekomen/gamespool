<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Admin
{
    public static function require(): void
    {
        Auth::requireLogin();
        $u = Auth::user();
        if (!$u || empty($u['is_admin'])) {
            http_response_code(403);
            echo 'Alleen voor admins.';
            exit;
        }
    }

    public static function is(): bool
    {
        $u = Auth::user();
        return (bool) ($u['is_admin'] ?? false);
    }
}
