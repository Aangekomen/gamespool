<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;

/**
 * Berekent streaks en badges direct uit match_participants — geen aparte
 * tabellen. Cheap genoeg voor profielpagina's; cache op user-niveau als het
 * later traag wordt.
 */
class Achievements
{
    /**
     * Huidige reeks gewonnen / verloren wedstrijden voor een speler.
     *
     * Levert ['type' => 'win'|'loss'|'none', 'count' => N, 'best_win_streak' => N]
     */
    public static function streak(int $userId): array
    {
        $rows = Database::fetchAll(
            "SELECT p.result
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
              WHERE p.user_id = ? AND m.state = 'completed'
                AND p.result IN ('win','loss','draw')
              ORDER BY m.ended_at DESC, m.id DESC
              LIMIT 200",
            [$userId]
        );
        if (!$rows) {
            return ['type' => 'none', 'count' => 0, 'best_win_streak' => 0];
        }

        // Huidige streak: alleen win-streaks of loss-streaks tellen, draws breken.
        $current = ['type' => 'none', 'count' => 0];
        foreach ($rows as $r) {
            $res = $r['result'];
            if ($res === 'draw') break;
            if ($current['type'] === 'none') {
                $current = ['type' => $res, 'count' => 1];
            } elseif ($current['type'] === $res) {
                $current['count']++;
            } else {
                break;
            }
        }

        // Best-ever win streak (over de laatste 200 matches — meestal genoeg).
        $best = 0;
        $run  = 0;
        // We willen historisch chronologisch lopen; rows zijn DESC dus omdraaien
        $chrono = array_reverse($rows);
        foreach ($chrono as $r) {
            if ($r['result'] === 'win') {
                $run++;
                if ($run > $best) $best = $run;
            } else {
                $run = 0;
            }
        }

        return [
            'type'            => $current['type'],
            'count'           => (int) $current['count'],
            'best_win_streak' => $best,
        ];
    }

    /**
     * Verdiende badges — uit aggregaties.
     * Elke badge: ['key', 'label', 'emoji', 'description', 'earned' => bool, 'progress'?]
     */
    public static function badges(int $userId): array
    {
        $stat = Database::fetch(
            "SELECT COUNT(p.id) AS matches,
                    COALESCE(SUM(p.result = 'win'), 0)  AS wins,
                    COALESCE(SUM(p.points_awarded), 0) AS points
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
              WHERE p.user_id = ? AND m.state = 'completed'",
            [$userId]
        ) ?? ['matches' => 0, 'wins' => 0, 'points' => 0];

        $matches = (int) $stat['matches'];
        $wins    = (int) $stat['wins'];
        $points  = (int) $stat['points'];

        $streak = self::streak($userId);
        $bestWin = (int) $streak['best_win_streak'];
        $curWin  = $streak['type'] === 'win' ? (int) $streak['count'] : 0;

        // Distinct spellen waar speler resultaat haalde (variatie-badge)
        $games = (int) (Database::fetch(
            "SELECT COUNT(DISTINCT m.game_id) AS c
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
              WHERE p.user_id = ? AND m.state = 'completed'",
            [$userId]
        )['c'] ?? 0);

        $defs = [
            ['key' => 'first_match',    'label' => 'Eerste match',     'emoji' => '🎯', 'description' => 'Speel je eerste match',
             'earned' => $matches >= 1, 'progress' => min(1, $matches), 'goal' => 1],
            ['key' => 'first_win',      'label' => 'Eerste winst',     'emoji' => '🏆', 'description' => 'Win een match',
             'earned' => $wins >= 1, 'progress' => min(1, $wins), 'goal' => 1],
            ['key' => 'streak_3',       'label' => 'Hot streak',       'emoji' => '🔥', 'description' => 'Win 3 op rij',
             'earned' => $bestWin >= 3, 'progress' => min(3, max($bestWin, $curWin)), 'goal' => 3],
            ['key' => 'streak_5',       'label' => 'On fire',          'emoji' => '🚀', 'description' => 'Win 5 op rij',
             'earned' => $bestWin >= 5, 'progress' => min(5, max($bestWin, $curWin)), 'goal' => 5],
            ['key' => 'streak_10',      'label' => 'Onverslaanbaar',   'emoji' => '👑', 'description' => 'Win 10 op rij',
             'earned' => $bestWin >= 10, 'progress' => min(10, max($bestWin, $curWin)), 'goal' => 10],
            ['key' => 'matches_25',     'label' => 'Stamgast',         'emoji' => '🍻', 'description' => 'Speel 25 matches',
             'earned' => $matches >= 25, 'progress' => min(25, $matches), 'goal' => 25],
            ['key' => 'matches_100',    'label' => 'Veteraan',         'emoji' => '🎖️', 'description' => 'Speel 100 matches',
             'earned' => $matches >= 100, 'progress' => min(100, $matches), 'goal' => 100],
            ['key' => 'multitalent',    'label' => 'Multitalent',      'emoji' => '🎲', 'description' => 'Speel 3 verschillende spellen',
             'earned' => $games >= 3, 'progress' => min(3, $games), 'goal' => 3],
            ['key' => 'centurion',      'label' => 'Centurion',        'emoji' => '💯', 'description' => 'Verdien 100 punten lifetime',
             'earned' => $points >= 100, 'progress' => min(100, $points), 'goal' => 100],
        ];

        return $defs;
    }
}
