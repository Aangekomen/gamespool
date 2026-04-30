<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;

class Leaderboard
{
    public const PERIODS = ['live', 'day', 'week', 'season', 'month', 'lifetime'];

    public static function periodLabel(string $p): string
    {
        return match ($p) {
            'live'     => 'Live (laatste 24u)',
            'day'      => 'Vandaag',
            'week'     => 'Deze week',
            'season'   => 'Seizoen ' . self::currentSeasonLabel(),
            'month'    => 'Deze maand',
            'lifetime' => 'Lifetime',
            default    => $p,
        };
    }

    /**
     * Returns the lower bound timestamp for a period, or null for lifetime.
     * Seizoen = kalenderkwartaal (Q1 jan-mrt, Q2 apr-jun, ...).
     */
    public static function since(string $period): ?string
    {
        return match ($period) {
            'live'   => date('Y-m-d H:i:s', strtotime('-24 hours')),
            'day'    => date('Y-m-d 00:00:00'),
            'week'   => date('Y-m-d 00:00:00', strtotime('monday this week')),
            'month'  => date('Y-m-01 00:00:00'),
            'season' => self::seasonStart(),
            default  => null,
        };
    }

    /**
     * Begin van het huidige seizoen (1e dag van het kwartaal).
     */
    public static function seasonStart(): string
    {
        $month = (int) date('n');
        $startMonth = (int) (floor(($month - 1) / 3) * 3) + 1;
        return date('Y-' . str_pad((string) $startMonth, 2, '0', STR_PAD_LEFT) . '-01 00:00:00');
    }

    /**
     * Korte label, bv. "Q2 2026".
     */
    public static function currentSeasonLabel(): string
    {
        $q = (int) ceil(((int) date('n')) / 3);
        return 'Q' . $q . ' ' . date('Y');
    }

    /**
     * Aantal dagen tot eind van het huidige seizoen.
     */
    public static function seasonDaysLeft(): int
    {
        $month = (int) date('n');
        $endMonth = (int) (floor(($month - 1) / 3) * 3) + 3;
        $endDate = date('Y-' . str_pad((string) $endMonth, 2, '0', STR_PAD_LEFT) . '-01 00:00:00');
        $endTs = strtotime('+1 month', strtotime($endDate)) - 1;
        return max(0, (int) floor(($endTs - time()) / 86400));
    }

    /**
     * Begin/eind van een specifiek seizoen (kalenderkwartaal).
     * Quarter = 1..4. Levert ['since' => 'Y-m-d H:i:s', 'until' => '...'].
     */
    public static function seasonRange(int $year, int $quarter): array
    {
        $quarter = max(1, min(4, $quarter));
        $startMonth = ($quarter - 1) * 3 + 1;
        $sinceTs = strtotime(sprintf('%04d-%02d-01 00:00:00', $year, $startMonth));
        $untilTs = strtotime('+3 months', $sinceTs) - 1;
        return [
            'since' => date('Y-m-d H:i:s', $sinceTs),
            'until' => date('Y-m-d H:i:s', $untilTs),
            'label' => 'Q' . $quarter . ' ' . $year,
        ];
    }

    /**
     * Geeft alle seizoenen waarin matches zijn afgerond, plus het huidige
     * (ook als nog leeg). Recentste eerst. Levert array van
     * ['key' => '2026Q2', 'year', 'quarter', 'label', 'is_current'].
     */
    public static function seasonsAvailable(): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT YEAR(ended_at) AS y, QUARTER(ended_at) AS q
               FROM matches
              WHERE state = 'completed' AND ended_at IS NOT NULL
              ORDER BY y DESC, q DESC"
        );
        $curY = (int) date('Y');
        $curQ = (int) ceil(((int) date('n')) / 3);
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            $y = (int) $r['y']; $q = (int) $r['q'];
            $key = $y . 'Q' . $q;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = [
                'key' => $key, 'year' => $y, 'quarter' => $q,
                'label' => 'Q' . $q . ' ' . $y,
                'is_current' => ($y === $curY && $q === $curQ),
            ];
        }
        $curKey = $curY . 'Q' . $curQ;
        if (!isset($seen[$curKey])) {
            array_unshift($out, [
                'key' => $curKey, 'year' => $curY, 'quarter' => $curQ,
                'label' => 'Q' . $curQ . ' ' . $curY, 'is_current' => true,
            ]);
        }
        return $out;
    }

    /** Parse 'YYYYQN' (bv. '2025Q3'). Levert null bij ongeldige input. */
    public static function parseSeasonKey(string $key): ?array
    {
        if (!preg_match('/^(\d{4})Q([1-4])$/', $key, $m)) return null;
        return ['year' => (int) $m[1], 'quarter' => (int) $m[2]];
    }

    /**
     * Player standings: sum of points_awarded over completed matches in window.
     * If $gameId is given and that game uses Elo, returns current ratings.
     */
    public static function players(string $period, ?int $gameId = null, int $limit = 100, string $scoring = 'auto', ?string $since = null, ?string $until = null): array
    {
        if ($since === null && $until === null) {
            $since = self::since($period);
        }

        if ($gameId !== null && $scoring !== 'wins') {
            $game = Game::find($gameId);
            if ($game && $game['score_type'] === 'elo' && $period === 'lifetime' && $since === null && $until === null) {
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
        if ($until !== null) {
            $where .= ' AND m.ended_at <= ?';
            $params[] = $until;
        }
        if ($gameId !== null) {
            $where .= ' AND m.game_id = ?';
            $params[] = $gameId;
        }

        // Globale view of expliciet 'wins' modus: ranking op aantal winsten,
        // 1 punt per winst — gelijk speelveld over alle scoring-systemen.
        $useWins = ($scoring === 'wins') || ($gameId === null && $scoring === 'auto');
        $pointsExpr = $useWins
            ? "SUM(p.result = 'win')"
            : "COALESCE(SUM(p.points_awarded), 0)";

        return Database::fetchAll(
            "SELECT u.id, u.display_name, u.avatar_path,
                    {$pointsExpr} AS total_points,
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
    public static function teams(string $period, ?int $gameId = null, int $limit = 100, ?string $since = null, ?string $until = null): array
    {
        if ($since === null && $until === null) {
            $since = self::since($period);
        }

        $where = 'm.state = "completed" AND p.team_id IS NOT NULL';
        $params = [];
        if ($since !== null) {
            $where .= ' AND m.ended_at >= ?';
            $params[] = $since;
        }
        if ($until !== null) {
            $where .= ' AND m.ended_at <= ?';
            $params[] = $until;
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
