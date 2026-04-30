<?php
declare(strict_types=1);

namespace GamesPool\Core;

use GamesPool\Models\Game;

/**
 * "Actief spel" voor de huidige gebruiker. Gewoon een filter — speler-stats
 * en matches blijven globaal opgeslagen, maar lijsten/leaderboards tonen
 * alleen het gekozen spel zodat je niet bedolven wordt onder álles.
 *
 * Persisted in cookie zodat het ook werkt voor uitgelogde gasten en
 * stabiel blijft over sessies.
 */
class ActiveGame
{
    private const COOKIE = 'fc_active_game';

    /** Game-slug of null (= alle spellen). */
    public static function slug(): ?string
    {
        $raw = $_COOKIE[self::COOKIE] ?? null;
        if (!$raw) return null;
        $clean = preg_replace('/[^a-z0-9\-]/i', '', (string) $raw) ?: null;
        return $clean ?: null;
    }

    /** Hele game-row of null. */
    public static function game(): ?array
    {
        $slug = self::slug();
        if (!$slug) return null;
        return Game::findBySlug($slug);
    }

    public static function id(): ?int
    {
        $g = self::game();
        return $g ? (int) $g['id'] : null;
    }

    /** Zet (of unset met '') het actieve spel via cookie. */
    public static function set(?string $slug): void
    {
        $params = [
            'expires'  => $slug ? time() + 31536000 : time() - 3600,
            'path'     => '/',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
            'httponly' => false, // mag voor reactie in JS later
            'samesite' => 'Lax',
        ];
        if ($slug === null || $slug === '') {
            setcookie(self::COOKIE, '', $params);
            unset($_COOKIE[self::COOKIE]);
            return;
        }
        $clean = preg_replace('/[^a-z0-9\-]/i', '', $slug);
        setcookie(self::COOKIE, (string) $clean, $params);
        $_COOKIE[self::COOKIE] = (string) $clean;
    }
}
