<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;

class Leaderboard
{
    public const PERIODS = ['live', 'day', 'week', 'month', 'lifetime'];

    public static function periodLabel(string $p): string
    {
        return match ($p) {
            'live'     => 'Live (laatste 24u)',
            'day'      => 'Vandaag',
            'week'     => 'Deze week',
            'month'    => 'Deze maand',
            'lifetime' => 'Lifetime',
            default    => $p,
        };
    }

    /**
     * Returns the lower bound timestamp for a period, or null for lifetime.
     */
    public static function since(string $period): ?string
    {
        return match ($period) {
            'live'  => date('Y-m-d H:i:s', strtotime('-24 hours')),
            'day'   => date('Y-m-d 00:00:00'),
            'week'  => date('Y-m-d 00:00:00', strtotime('monday this week')),
            'month' => date('Y-m-01 00:00:00'),
            default => null,
        };
    }

    /**
     * Player standings: sum of points_awarded over completed matches in window.
     * If $gameId is given and that game uses Elo, returns current ratings.
     */
    public static function players(string $period, ?int $gameId = null, int $limit = 100): array
    {
        $since = self::since($period);

        if ($gameId !== null) {
            $game = Game::find($gameId);
            if ($game && $game['score_type'] === 'elo' && $period === 'lifetime') {
                return Database::fetchAll(
                    'SELECT u.id, u.display_name, u.avatar_path,
                            r.rating  AS total_points,
                            r.matches_played AS matches_played,
                            0 AS wins
                       FROM user_ratings r
                       JOIN users u ON u.id = r.user_id
                      WHERE r.game_id = ?
                      ORDER BY r.rating DESC
                      LIMIT ' . (int) $limit,
                    [$gameId]
                );
            }
        }

        $where = 'm.state = "completed" AND p.user_id IS NOT NULL';
        $params = [];
        if ($since !== null) {
            $where .= ' AND m.ended_at >= ?';
            $params[] = $since;
        }
        if ($gameId !== null) {
            $where .= ' AND m.game_id = ?';
            $params[] = $gameId;
        }

        return Database::fetchAll(
            "SELECT u.id, u.display_name, u.avatar_path,
                    COALESCE(SUM(p.points_awarded), 0) AS total_points,
                    COUNT(p.id) AS matches_played,
                    SUM(p.result = 'win') AS wins
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
               JOIN users   u ON u.id = p.user_id
              WHERE {$where}
              GROUP BY u.id, u.display_name, u.avatar_path
              ORDER BY total_points DESC, wins DESC, matches_played ASC
              LIMIT " . (int) $limit,
            $params
        );
    }

    /**
     * Team standings (analogous, for matches with team_id set).
     */
    public static function teams(string $period, ?int $gameId = null, int $limit = 100): array
    {
        $since = self::since($period);

        $where = 'm.state = "completed" AND p.team_id IS NOT NULL';
        $params = [];
        if ($since !== null) {
            $where .= ' AND m.ended_at >= ?';
            $params[] = $since;
        }
        if ($gameId !== null) {
            $where .= ' AND m.game_id = ?';
            $params[] = $gameId;
        }

        return Database::fetchAll(
            "SELECT t.id, t.name, t.slug, t.logo_path,
                    COALESCE(SUM(p.points_awarded), 0) AS total_points,
                    COUNT(p.id) AS matches_played,
                    SUM(p.result = 'win') AS wins
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
               JOIN teams   t ON t.id = p.team_id
              WHERE {$where}
              GROUP BY t.id, t.name, t.slug, t.logo_path
              ORDER BY total_points DESC, wins DESC, matches_played ASC
              LIMIT " . (int) $limit,
            $params
        );
    }
}
