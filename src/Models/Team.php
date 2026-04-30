<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;
use GamesPool\Core\Slug;

class Team
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM teams WHERE id = ?', [$id]);
    }

    public static function findByCode(string $code): ?array
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) return null;
        return Database::fetch('SELECT * FROM teams WHERE join_code = ?', [$code]);
    }

    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT t.*, tm.role
               FROM teams t
               JOIN team_members tm ON tm.team_id = t.id
              WHERE tm.user_id = ?
              ORDER BY t.name ASC',
            [$userId]
        );
    }

    public static function memberCount(int $teamId): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS c FROM team_members WHERE team_id = ?', [$teamId]);
        return (int) ($row['c'] ?? 0);
    }

    public static function isMember(int $teamId, int $userId): bool
    {
        return (bool) Database::fetch(
            'SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1',
            [$teamId, $userId]
        );
    }

    public static function create(string $name, int $captainId): int
    {
        $slug = Slug::unique($name, fn($s) => (bool) Database::fetch('SELECT id FROM teams WHERE slug = ?', [$s]));
        $code = self::generateJoinCode();

        $teamId = Database::insert(
            'INSERT INTO teams (name, slug, join_code, created_by) VALUES (?, ?, ?, ?)',
            [$name, $slug, $code, $captainId]
        );
        Database::query(
            'INSERT INTO team_members (team_id, user_id, role) VALUES (?, ?, "captain")',
            [$teamId, $captainId]
        );
        return $teamId;
    }

    public static function addMember(int $teamId, int $userId, string $role = 'member'): void
    {
        Database::query(
            'INSERT IGNORE INTO team_members (team_id, user_id, role) VALUES (?, ?, ?)',
            [$teamId, $userId, $role]
        );
    }

    public static function removeMember(int $teamId, int $userId): void
    {
        Database::query(
            'DELETE FROM team_members WHERE team_id = ? AND user_id = ?',
            [$teamId, $userId]
        );
    }

    private static function generateJoinCode(): string
    {
        // 6-digit numeric, leading zeros allowed; collision-retry
        for ($i = 0; $i < 50; $i++) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $taken = Database::fetch('SELECT id FROM teams WHERE join_code = ?', [$code]);
            if (!$taken) return $code;
        }
        // Astronomically unlikely fallback
        throw new \RuntimeException('Kon geen unieke join-code genereren.');
    }
}
