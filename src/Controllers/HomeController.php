<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Models\Game;
use GamesPool\Models\GameMatch;
use GamesPool\Models\Leaderboard;
use GamesPool\Models\Team;
use GamesPool\Models\Tournament;

class HomeController
{
    public function index(): string
    {
        if (!Auth::check()) {
            return view('home', ['guest' => true]);
        }

        $userId = (int) Auth::id();
        $myTeams = Team::forUser($userId);
        return view('home', [
            'guest'         => false,
            'stats'         => $this->personalStats($userId),
            'games'         => Game::all(),
            'activeMatches' => GameMatch::active(8),
            'recentMatches' => GameMatch::recent(5, $userId),
            'hasTeam'       => count($myTeams) > 0,
            'tournaments'   => Tournament::upcoming(3),
        ]);
    }

    private function personalStats(int $userId): array
    {
        $weekStart  = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $todayStart = date('Y-m-d 00:00:00');

        $row = Database::fetch(
            "SELECT
                COUNT(p.id)                          AS matches_played,
                COALESCE(SUM(p.result = 'win'),0)    AS wins,
                COALESCE(SUM(p.points_awarded),0)    AS total_points,
                COALESCE(SUM(CASE WHEN m.ended_at >= ? THEN 1 ELSE 0 END),0) AS week_matches,
                COALESCE(SUM(CASE WHEN m.ended_at >= ? THEN 1 ELSE 0 END),0) AS today_matches
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
              WHERE p.user_id = ? AND m.state = 'completed'",
            [$weekStart, $todayStart, $userId]
        ) ?? [];

        $matches  = (int) ($row['matches_played'] ?? 0);
        $wins     = (int) ($row['wins'] ?? 0);
        $winRate  = $matches > 0 ? (int) round(($wins / $matches) * 100) : 0;

        // Rank on lifetime leaderboard (top 100 query)
        $rank = null;
        $totalRanked = 0;
        foreach (Leaderboard::players('lifetime') as $i => $p) {
            $totalRanked++;
            if ($rank === null && (int) $p['id'] === $userId) {
                $rank = $i + 1;
            }
        }

        return [
            'matches_played' => $matches,
            'wins'           => $wins,
            'losses'         => max(0, $matches - $wins),
            'win_rate'       => $winRate,
            'total_points'   => (int) ($row['total_points'] ?? 0),
            'today_matches'  => (int) ($row['today_matches'] ?? 0),
            'week_matches'   => (int) ($row['week_matches'] ?? 0),
            'rank'           => $rank,
            'total_ranked'   => $totalRanked,
        ];
    }
}
