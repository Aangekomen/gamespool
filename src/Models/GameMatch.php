<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;
use GamesPool\Core\ScoreEngine;

/**
 * "Match" is a reserved word in PHP 8, so we use GameMatch.
 */
class GameMatch
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM matches WHERE id = ?', [$id]);
    }

    public static function findByToken(string $token): ?array
    {
        return Database::fetch('SELECT * FROM matches WHERE join_token = ?', [$token]);
    }

    /**
     * List recent matches with game name. Optional filter by user (matches the user participated in).
     */
    public static function recent(int $limit = 25, ?int $userId = null): array
    {
        if ($userId !== null) {
            return Database::fetchAll(
                'SELECT m.*, g.name AS game_name, g.slug AS game_slug
                   FROM matches m
                   JOIN games g ON g.id = m.game_id
                  WHERE EXISTS (SELECT 1 FROM match_participants p WHERE p.match_id = m.id AND p.user_id = ?)
                  ORDER BY m.started_at DESC
                  LIMIT ' . (int) $limit,
                [$userId]
            );
        }
        return Database::fetchAll(
            'SELECT m.*, g.name AS game_name, g.slug AS game_slug
               FROM matches m
               JOIN games g ON g.id = m.game_id
              ORDER BY m.started_at DESC
              LIMIT ' . (int) $limit
        );
    }

    public static function participants(int $matchId): array
    {
        return Database::fetchAll(
            'SELECT p.*, u.display_name, u.avatar_path
               FROM match_participants p
               JOIN users u ON u.id = p.user_id
              WHERE p.match_id = ?
              ORDER BY p.points_awarded DESC, p.id ASC',
            [$matchId]
        );
    }

    /**
     * Create a new match record (in_progress) with participants but no scores yet.
     * Every participant must have a user_id — guest play is not supported.
     */
    public static function create(int $gameId, ?int $createdBy, array $participants, ?string $label = null, ?int $deviceId = null): int
    {
        $token = bin2hex(random_bytes(8));
        $matchId = Database::insert(
            'INSERT INTO matches (game_id, device_id, label, state, join_token, created_by) VALUES (?, ?, ?, "in_progress", ?, ?)',
            [$gameId, $deviceId, $label, $token, $createdBy]
        );

        foreach ($participants as $p) {
            if (empty($p['user_id'])) continue;
            Database::query(
                'INSERT INTO match_participants (match_id, user_id, team_id) VALUES (?, ?, ?)',
                [
                    $matchId,
                    (int) $p['user_id'],
                    !empty($p['team_id']) ? (int) $p['team_id'] : null,
                ]
            );
        }
        return $matchId;
    }

    /**
     * Record results: takes participant rows with raw_score and/or result,
     * runs the score engine, and writes back points/ratings + completes the match.
     */
    public static function recordResults(int $matchId, array $participantInputs): void
    {
        $match = self::find($matchId);
        if (!$match) return;
        $game = Game::find((int) $match['game_id']);
        if (!$game) return;

        $existing = self::participants($matchId);
        $byId = [];
        foreach ($existing as $row) $byId[(int) $row['id']] = $row;

        // Merge inputs onto existing rows
        $merged = [];
        foreach ($participantInputs as $row) {
            $id = (int) ($row['id'] ?? 0);
            $base = $byId[$id] ?? [];
            $merged[] = array_merge($base, [
                'id'         => $id,
                'user_id'    => $base['user_id'] ?? null,
                'team_id'    => $base['team_id'] ?? null,
                'raw_score'  => isset($row['raw_score']) ? (int) $row['raw_score'] : null,
                'result'     => $row['result'] ?? null,
            ]);
        }

        $computed = ScoreEngine::compute($game, $merged);

        Database::pdo()->beginTransaction();
        try {
            foreach ($computed as $row) {
                Database::query(
                    'UPDATE match_participants
                        SET raw_score = ?, result = ?, points_awarded = ?, rating_before = ?, rating_after = ?
                      WHERE id = ?',
                    [
                        $row['raw_score']     ?? null,
                        $row['result']        ?? null,
                        (int) ($row['points_awarded'] ?? 0),
                        $row['rating_before'] ?? null,
                        $row['rating_after']  ?? null,
                        (int) $row['id'],
                    ]
                );
            }
            Database::query(
                'UPDATE matches SET state = "completed", ended_at = NOW() WHERE id = ?',
                [$matchId]
            );
            ScoreEngine::persistRatings($game, $computed);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Currently-active matches (waiting + in_progress) with participant names joined.
     */
    public static function active(int $limit = 10): array
    {
        $rows = Database::fetchAll(
            "SELECT m.*, g.name AS game_name, g.slug AS game_slug
               FROM matches m
               JOIN games g ON g.id = m.game_id
              WHERE m.state IN ('waiting','in_progress')
              ORDER BY m.started_at DESC
              LIMIT " . (int) $limit
        );
        if (!$rows) return [];

        $ids = array_column($rows, 'id');
        $place = implode(',', array_fill(0, count($ids), '?'));
        $parts = Database::fetchAll(
            "SELECT mp.match_id, u.display_name
               FROM match_participants mp
               JOIN users u ON u.id = mp.user_id
              WHERE mp.match_id IN ($place)
              ORDER BY mp.id ASC",
            $ids
        );
        $byMatch = [];
        foreach ($parts as $p) {
            $byMatch[(int) $p['match_id']][] = $p['display_name'];
        }
        foreach ($rows as &$r) {
            $r['participant_names'] = $byMatch[(int) $r['id']] ?? [];
        }
        return $rows;
    }

    public static function allRecent(int $limit = 100): array
    {
        return Database::fetchAll(
            'SELECT m.*, g.name AS game_name, g.slug AS game_slug,
                    (SELECT COUNT(*) FROM match_participants p WHERE p.match_id = m.id) AS participant_count
               FROM matches m
               JOIN games g ON g.id = m.game_id
              ORDER BY m.started_at DESC
              LIMIT ' . (int) $limit
        );
    }

    public static function updateLabel(int $matchId, ?string $label): void
    {
        Database::query('UPDATE matches SET label = ? WHERE id = ?', [$label, $matchId]);
    }

    public static function delete(int $matchId): void
    {
        Database::query('DELETE FROM matches WHERE id = ?', [$matchId]);
    }

    public static function cancel(int $matchId): void
    {
        Database::query(
            'UPDATE matches SET state = "cancelled", ended_at = NOW() WHERE id = ? AND state IN ("in_progress","waiting")',
            [$matchId]
        );
    }

    /**
     * Create a "waiting" match started from a device QR scan, with the
     * scanning user as sole confirmed participant.
     */
    public static function createWaiting(array $device, int $userId): int
    {
        $token = bin2hex(random_bytes(8));
        $matchId = Database::insert(
            'INSERT INTO matches (game_id, device_id, label, state, join_token, created_by)
             VALUES (?, ?, ?, "waiting", ?, ?)',
            [$device['game_id'], $device['id'], $device['name'], $token, $userId]
        );
        Database::query(
            'INSERT INTO match_participants (match_id, user_id) VALUES (?, ?)',
            [$matchId, $userId]
        );
        return $matchId;
    }

    /**
     * Find the most-recent waiting match for a given device, if any.
     */
    public static function waitingForDevice(int $deviceId): ?array
    {
        return Database::fetch(
            'SELECT * FROM matches
              WHERE device_id = ? AND state = "waiting"
              ORDER BY id DESC
              LIMIT 1',
            [$deviceId]
        );
    }

    /**
     * Any non-finished match (waiting or in_progress) currently bound to a device.
     */
    public static function activeForDevice(int $deviceId): ?array
    {
        return Database::fetch(
            'SELECT * FROM matches
              WHERE device_id = ? AND state IN ("waiting","in_progress")
              ORDER BY id DESC
              LIMIT 1',
            [$deviceId]
        );
    }

    /**
     * Add a 2nd participant and lock the match (waiting → in_progress).
     * Returns true if accepted, false if user was already in the match.
     */
    public static function acceptInvite(int $matchId, int $userId): bool
    {
        $existing = Database::fetch(
            'SELECT id FROM match_participants WHERE match_id = ? AND user_id = ? LIMIT 1',
            [$matchId, $userId]
        );
        if ($existing) return false;

        Database::pdo()->beginTransaction();
        try {
            Database::query(
                'INSERT INTO match_participants (match_id, user_id) VALUES (?, ?)',
                [$matchId, $userId]
            );
            Database::query(
                'UPDATE matches SET state = "in_progress" WHERE id = ? AND state = "waiting"',
                [$matchId]
            );
            Database::pdo()->commit();
            return true;
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
            throw $e;
        }
    }
}
