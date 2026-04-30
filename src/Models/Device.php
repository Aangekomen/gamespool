<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;

class Device
{
    /** Alphabet without ambiguous chars (no 0/O, 1/I, l) */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM devices WHERE id = ?', [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        return Database::fetch('SELECT * FROM devices WHERE code = ?', [strtoupper(trim($code))]);
    }

    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT d.*, g.name AS game_name, g.slug AS game_slug
               FROM devices d
               LEFT JOIN games g ON g.id = d.game_id
              ORDER BY d.name ASC'
        );
    }

    public static function create(string $name, ?int $gameId, ?string $location = null): int
    {
        return Database::insert(
            'INSERT INTO devices (name, code, game_id, location) VALUES (?, ?, ?, ?)',
            [$name, self::generateCode(), $gameId, $location ?: null]
        );
    }

    public static function update(int $id, string $name, ?int $gameId, ?string $location): void
    {
        Database::query(
            'UPDATE devices SET name = ?, game_id = ?, location = ? WHERE id = ?',
            [$name, $gameId, $location ?: null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM devices WHERE id = ?', [$id]);
    }

    private static function generateCode(int $length = 6): string
    {
        for ($try = 0; $try < 50; $try++) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            if (!Database::fetch('SELECT id FROM devices WHERE code = ?', [$code])) {
                return $code;
            }
        }
        throw new \RuntimeException('Kon geen unieke device-code genereren.');
    }
}
