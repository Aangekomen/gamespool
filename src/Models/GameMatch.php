<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;
use GamesPool\Core\ScoreEngine;
use GamesPool\Models\Tournament;

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
                'SELECT m.*, g.name AS game_name, g.slug AS game_slug,
                        (SELECT mp.result         FROM match_participants mp WHERE mp.match_id = m.id AND mp.user_id = ? LIMIT 1) AS my_result,
                        (SELECT mp.points_awarded FROM match_participants mp WHERE mp.match_id = m.id AND mp.user_id = ? LIMIT 1) AS my_points
                   FROM matches m
                   JOIN games g ON g.id = m.game_id
                  WHERE EXISTS (SELECT 1 FROM match_participants p WHERE p.match_id = m.id AND p.user_id = ?)
                  ORDER BY m.started_at DESC
                  LIMIT ' . (int) $limit,
                [$userId, $userId, $userId]
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

        $merged = self::mergeInputs($matchId, $participantInputs);
        $computed = ScoreEngine::compute($game, $merged);

        Database::pdo()->beginTransaction();
        try {
            foreach ($computed as $row) {
                Database::query(
                    'UPDATE match_participants
                        SET raw_score = ?, result = ?, points_awarded = ?, rating_before = ?, rating_after = ?, match_side = ?
                      WHERE id = ?',
                    [
                        $row['raw_score']     ?? null,
                        $row['result']        ?? null,
                        (int) ($row['points_awarded'] ?? 0),
                        $row['rating_before'] ?? null,
                        $row['rating_after']  ?? null,
                        $row['match_side']    ?? null,
                        (int) $row['id'],
                    ]
                );
            }
            Database::query(
                'UPDATE matches
                    SET state = "completed", ended_at = NOW(),
                        pending_recorded_by = NULL, pending_recorded_at = NULL, pending_payload = NULL
                  WHERE id = ?',
                [$matchId]
            );
            ScoreEngine::persistRatings($game, $computed);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
            throw $e;
        }

        // Hook: toernooimatch? Advance winner naar volgende ronde.
        if (!empty($match['tournament_id'])) {
            $winner = null;
            foreach ($computed as $row) {
                if (($row['result'] ?? null) === 'win' && !empty($row['user_id'])) {
                    $winner = (int) $row['user_id']; break;
                }
            }
            if ($winner !== null) {
                Tournament::advanceWinner(
                    (int) $match['tournament_id'],
                    (int) $match['bracket_round'],
                    (int) $match['bracket_slot'],
                    $winner
                );
            }
        }
    }

    private static function mergeInputs(int $matchId, array $participantInputs): array
    {
        $existing = self::participants($matchId);
        $byId = [];
        foreach ($existing as $row) $byId[(int) $row['id']] = $row;
        $merged = [];
        foreach ($participantInputs as $row) {
            $id = (int) ($row['id'] ?? 0);
            $base = $byId[$id] ?? [];
            $merged[] = array_merge($base, [
                'id'         => $id,
                'user_id'    => $base['user_id'] ?? null,
                'team_id'    => $base['team_id'] ?? null,
                'match_side' => $row['match_side'] ?? ($base['match_side'] ?? null),
                'raw_score'  => isset($row['raw_score']) ? (int) $row['raw_score'] : null,
                'result'     => $row['result'] ?? null,
            ]);
        }
        return $merged;
    }

    /**
     * Voorlopige uitslag opslaan: andere deelnemers moeten nog bevestigen.
     * Bij solo-match (1 deelnemer) wordt het meteen completed.
     */
    public static function recordPending(int $matchId, int $byUserId, array $participantInputs): void
    {
        $merged = self::mergeInputs($matchId, $participantInputs);
        $userIds = array_unique(array_filter(array_map(fn ($r) => (int) ($r['user_id'] ?? 0), $merged)));
        // Single-player edge case: niets om te bevestigen, gewoon afronden
        if (count($userIds) <= 1) {
            self::recordResults($matchId, $participantInputs);
            return;
        }
        Database::query(
            'UPDATE matches
                SET state = "pending_confirmation",
                    pending_recorded_by = ?, pending_recorded_at = NOW(), pending_payload = ?
              WHERE id = ?',
            [$byUserId, json_encode($participantInputs, JSON_THROW_ON_ERROR), $matchId]
        );

        // Push: vraag de tegenstanders om te bevestigen
        $by = Database::fetch('SELECT display_name FROM users WHERE id = ?', [$byUserId]);
        $byName = (string) ($by['display_name'] ?? 'Iemand');
        foreach ($userIds as $uid) {
            if ((int) $uid === $byUserId) continue;
            \GamesPool\Core\Push::sendToUser(
                (int) $uid,
                'Bevestig de uitslag',
                $byName . ' heeft de uitslag ingevoerd — klopt het?',
                '/matches/' . $matchId
            );
        }
    }

    /**
     * Bevestiger neemt pending_payload over en start volledige score-flow.
     */
    public static function confirmPending(int $matchId): void
    {
        $row = Database::fetch('SELECT pending_payload FROM matches WHERE id = ?', [$matchId]);
        if (!$row || empty($row['pending_payload'])) return;
        try {
            $inputs = json_decode((string) $row['pending_payload'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) { return; }
        if (!is_array($inputs)) return;
        self::recordResults($matchId, $inputs);
    }

    /**
     * Andere deelnemer betwist de uitslag — terug naar in_progress.
     */
    public static function disputePending(int $matchId): void
    {
        Database::query(
            'UPDATE matches
                SET state = "in_progress",
                    pending_recorded_by = NULL, pending_recorded_at = NULL, pending_payload = NULL
              WHERE id = ? AND state = "pending_confirmation"',
            [$matchId]
        );
    }

    /**
     * Best-of-N serie: een groep matches die dezelfde series_id delen.
     * Returns ['matches' => [...], 'tally' => [user_id => wins], 'target' => N,
     *          'leader' => user_id|null, 'finished' => bool]
     */
    public static function seriesSummary(string $seriesId): array
    {
        $matches = Database::fetchAll(
            "SELECT m.id, m.state, m.started_at, m.ended_at, m.series_target
               FROM matches m
              WHERE m.series_id = ?
              ORDER BY m.id ASC",
            [$seriesId]
        );
        $target = 0;
        foreach ($matches as $m) {
            if (!empty($m['series_target'])) { $target = (int) $m['series_target']; break; }
        }
        if (!$matches) {
            return ['matches' => [], 'tally' => [], 'target' => $target, 'leader' => null, 'finished' => false, 'majority' => 0];
        }
        $ids = array_column($matches, 'id');
        $place = implode(',', array_fill(0, count($ids), '?'));
        $rows = Database::fetchAll(
            "SELECT mp.user_id, mp.result, u.display_name
               FROM match_participants mp
               JOIN users u ON u.id = mp.user_id
              WHERE mp.match_id IN ($place)
                AND mp.result = 'win'",
            $ids
        );
        $tally = []; $names = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            $tally[$uid] = ($tally[$uid] ?? 0) + 1;
            $names[$uid] = (string) $r['display_name'];
        }
        $leader = null; $top = 0;
        foreach ($tally as $uid => $w) {
            if ($w > $top) { $top = $w; $leader = $uid; }
        }
        $majority = $target > 0 ? (int) floor($target / 2) + 1 : 0;
        $finished = $majority > 0 && $top >= $majority;
        return [
            'matches'  => $matches,
            'tally'    => $tally,
            'names'    => $names,
            'target'   => $target,
            'leader'   => $leader,
            'finished' => $finished,
            'majority' => $majority,
        ];
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

    /**
     * Onderlinge balans tussen exact twee spelers voor een spel:
     * geeft per speler aantal winsten + totaal punten over alle afgeronde
     * matches waarbij precies dit duo speelde. Gebruikt voor head-to-head
     * weergave bij Rematch.
     *
     * Levert op: ['a' => ['wins' => N, 'points' => N], 'b' => [...], 'matches' => N]
     */
    public static function headToHead(int $userA, int $userB, int $gameId): array
    {
        $rows = Database::fetchAll(
            "SELECT m.id, p.user_id, p.result, p.points_awarded
               FROM matches m
               JOIN match_participants p ON p.match_id = m.id
              WHERE m.game_id = ?
                AND m.state = 'completed'
                AND m.id IN (
                    SELECT m2.id FROM matches m2
                      JOIN match_participants pa ON pa.match_id = m2.id AND pa.user_id = ?
                      JOIN match_participants pb ON pb.match_id = m2.id AND pb.user_id = ?
                     WHERE m2.game_id = ?
                       AND m2.state = 'completed'
                       AND (SELECT COUNT(*) FROM match_participants pc WHERE pc.match_id = m2.id) = 2
                )",
            [$gameId, $userA, $userB, $gameId]
        );

        $tally = [
            'a' => ['wins' => 0, 'points' => 0],
            'b' => ['wins' => 0, 'points' => 0],
            'matches' => 0,
        ];
        $matchIds = [];
        foreach ($rows as $r) {
            $matchIds[(int) $r['id']] = true;
            $key = ((int) $r['user_id'] === $userA) ? 'a' : 'b';
            if ($r['result'] === 'win') $tally[$key]['wins']++;
            $tally[$key]['points'] += (int) $r['points_awarded'];
        }
        $tally['matches'] = count($matchIds);
        return $tally;
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
     * Sluit matches af die te lang in 'waiting' of 'in_progress' staan en
     * verwijder oude cancelled matches die alleen nog ruis zijn in de lijst.
     *
     *   waiting          → 30 minuten → cancelled
     *   in_progress      →  6 uur     → cancelled
     *   cancelled        →  2 uur na  ended_at → DELETE (uit historie)
     */
    public static function cancelStale(): int
    {
        $pdo = Database::pdo();
        $cancelled = $pdo->exec(
            "UPDATE matches
                SET state = 'cancelled', ended_at = NOW()
              WHERE (state = 'waiting'     AND started_at < (NOW() - INTERVAL 30 MINUTE))
                 OR (state = 'in_progress' AND started_at < (NOW() - INTERVAL 6 HOUR))"
        );
        // Geannuleerde matches > 2 uur oud opruimen — geen waarde voor history.
        $pdo->exec(
            "DELETE FROM matches
              WHERE state = 'cancelled'
                AND COALESCE(ended_at, started_at) < (NOW() - INTERVAL 2 HOUR)"
        );
        return (int) $cancelled;
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
