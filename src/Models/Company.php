<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;

class Company
{
    /**
     * Normalize for dedupe: trim, collapse whitespace, lowercase.
     */
    public static function normalize(string $name): string
    {
        $s = trim($name);
        $s = (string) preg_replace('/\s+/u', ' ', $s);
        return mb_strtolower($s);
    }

    /**
     * Get-or-create by name. Idempotent for case/whitespace variants
     * ("ACME B.V.", " acme  b.v. " → same row).
     */
    public static function findOrCreate(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') return null;
        $normalized = self::normalize($name);

        $row = Database::fetch(
            'SELECT * FROM companies WHERE normalized_name = ? LIMIT 1',
            [$normalized]
        );
        if ($row) return $row;

        $id = Database::insert(
            'INSERT INTO companies (name, normalized_name) VALUES (?, ?)',
            [$name, $normalized]
        );
        return Database::fetch('SELECT * FROM companies WHERE id = ?', [$id]);
    }
}
