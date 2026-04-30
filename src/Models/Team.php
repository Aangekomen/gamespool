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
            "SELECT t.*, tm.role, tm.status
               FROM teams t
               JOIN team_members tm ON tm.team_id = t.id
              WHERE tm.user_id = ? AND tm.status = 'approved'
              ORDER BY t.name ASC",
            [$userId]
        );
    }

    public static function pendingForUser(int $userId): array
    {
        return Database::fetchAll(
            "SELECT t.*, tm.role, tm.status, tm.requested_at
               FROM teams t
               JOIN team_members tm ON tm.team_id = t.id
              WHERE tm.user_id = ? AND tm.status = 'pending'
              ORDER BY tm.requested_at DESC",
            [$userId]
        );
    }

    /** Pending join requests for a team that the captain must approve. */
    public static function pendingRequests(int $teamId): array
    {
        return Database::fetchAll(
            "SELECT u.id AS user_id, u.display_name, u.avatar_path, tm.requested_at
               FROM team_members tm
               JOIN users u ON u.id = tm.user_id
              WHERE tm.team_id = ? AND tm.status = 'pending'
              ORDER BY tm.requested_at ASC",
            [$teamId]
        );
    }

    public static function memberCount(int $teamId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM team_members WHERE team_id = ? AND status = 'approved'",
            [$teamId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public static function isMember(int $teamId, int $userId): bool
    {
        return (bool) Database::fetch(
            "SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? AND status = 'approved' LIMIT 1",
            [$teamId, $userId]
        );
    }

    public static function membership(int $teamId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1',
            [$teamId, $userId]
        );
    }

    public static function membersWithRoles(int $teamId): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.display_name, u.first_name, u.last_name, u.email, u.avatar_path,
                    tm.role, tm.tag, tm.status, tm.joined_at
               FROM team_members tm
               JOIN users u ON u.id = tm.user_id
              WHERE tm.team_id = ? AND tm.status = 'approved'
              ORDER BY (tm.role = 'captain') DESC, u.display_name ASC",
            [$teamId]
        );
    }

    public static function setMemberTag(int $teamId, int $userId, ?string $tag): void
    {
        if ($tag !== null) {
            $tag = mb_substr(trim($tag), 0, 20);
            if ($tag === '') $tag = null;
        }
        Database::query(
            'UPDATE team_members SET tag = ? WHERE team_id = ? AND user_id = ?',
            [$tag, $teamId, $userId]
        );
    }

    public static function transferCaptaincy(int $teamId, int $newCaptainUserId): void
    {
        Database::pdo()->beginTransaction();
        try {
            Database::query(
                "UPDATE team_members SET role = 'member' WHERE team_id = ? AND role = 'captain' AND status = 'approved'",
                [$teamId]
            );
            Database::query(
                "UPDATE team_members SET role = 'captain' WHERE team_id = ? AND user_id = ? AND status = 'approved'",
                [$teamId, $newCaptainUserId]
            );
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
            throw $e;
        }
    }

    public static function isCaptain(int $teamId, int $userId): bool
    {
        return (bool) Database::fetch(
            "SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? AND role = 'captain' AND status = 'approved' LIMIT 1",
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
        // Captain is automatically approved
        Database::query(
            'INSERT INTO team_members (team_id, user_id, role, status) VALUES (?, ?, "captain", "approved")',
            [$teamId, $captainId]
        );
        return $teamId;
    }

    /**
     * Request to join. Returns the row's status ('pending' or 'approved').
     * Captain auto-approves; everyone else lands in pending.
     */
    public static function requestJoin(int $teamId, int $userId): string
    {
        $existing = self::membership($teamId, $userId);
        if ($existing) {
            return (string) $existing['status'];
        }
        Database::query(
            'INSERT INTO team_members (team_id, user_id, role, status, requested_at)
             VALUES (?, ?, "member", "pending", NOW())',
            [$teamId, $userId]
        );
        return 'pending';
    }

    public static function approveMember(int $teamId, int $userId): void
    {
        Database::query(
            "UPDATE team_members SET status = 'approved' WHERE team_id = ? AND user_id = ?",
            [$teamId, $userId]
        );
    }

    public static function rejectMember(int $teamId, int $userId): void
    {
        Database::query(
            "DELETE FROM team_members WHERE team_id = ? AND user_id = ? AND status = 'pending'",
            [$teamId, $userId]
        );
    }

    public static function removeMember(int $teamId, int $userId): void
    {
        Database::query(
            'DELETE FROM team_members WHERE team_id = ? AND user_id = ?',
            [$teamId, $userId]
        );
    }

    /**
     * Recent matches that involved this team (any participant tagged with team_id).
     */
    public static function matchesPlayed(int $teamId, int $limit = 25): array
    {
        return Database::fetchAll(
            "SELECT DISTINCT m.id, m.label, m.state, m.started_at, m.ended_at,
                    g.name AS game_name, g.slug AS game_slug
               FROM matches m
               JOIN games g ON g.id = m.game_id
              WHERE EXISTS (SELECT 1 FROM match_participants p WHERE p.match_id = m.id AND p.team_id = ?)
              ORDER BY m.started_at DESC
              LIMIT " . (int) $limit,
            [$teamId]
        );
    }

    public static function allWithStats(): array
    {
        return Database::fetchAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id AND tm.status = 'approved') AS member_count,
                    (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id AND tm.status = 'pending')  AS pending_count,
                    (SELECT u.display_name FROM team_members tm JOIN users u ON u.id = tm.user_id
                       WHERE tm.team_id = t.id AND tm.role = 'captain' AND tm.status = 'approved' LIMIT 1) AS captain_name
               FROM teams t
              ORDER BY t.name ASC"
        );
    }

    public static function rename(int $teamId, string $name): void
    {
        $slug = Slug::unique($name, fn($s) => (bool) Database::fetch('SELECT id FROM teams WHERE slug = ? AND id <> ?', [$s, $teamId]));
        Database::query('UPDATE teams SET name = ?, slug = ? WHERE id = ?', [$name, $slug, $teamId]);
    }

    public static function regenerateCode(int $teamId): string
    {
        $code = self::generateJoinCode();
        Database::query('UPDATE teams SET join_code = ? WHERE id = ?', [$code, $teamId]);
        return $code;
    }

    public static function delete(int $teamId): void
    {
        Database::query('DELETE FROM teams WHERE id = ?', [$teamId]);
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
