<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Database;
use GamesPool\Models\Game;
use GamesPool\Models\GameMatch;
use GamesPool\Models\Leaderboard;

class TvController
{
    /** Globaal TV-dashboard. 1 punt per winst voor alle sporten samen. */
    public function index(): string
    {
        return $this->render(null);
    }

    /** Per-sport TV-dashboard: /tv/{slug}. Gebruikt eigen scoring van het spel. */
    public function gameTv(string $slug): string
    {
        $game = Game::findBySlug($slug);
        if (!$game) { http_response_code(404); echo view('errors/404'); exit; }
        return $this->render($game);
    }

    private function render(?array $game): string
    {
        $gameId = $game ? (int) $game['id'] : null;
        $args   = [];
        $where  = '';
        if ($gameId !== null) {
            $where  = ' WHERE d.game_id = ?';
            $args[] = $gameId;
        }
        $devices = Database::fetchAll(
            "SELECT d.*, g.name AS game_name,
                    (SELECT COUNT(*) FROM matches m
                       WHERE m.device_id = d.id AND m.state IN ('waiting','in_progress')) AS active_count,
                    (SELECT m2.started_at FROM matches m2
                       WHERE m2.device_id = d.id AND m2.state IN ('waiting','in_progress')
                       ORDER BY m2.id DESC LIMIT 1) AS active_started_at,
                    (SELECT m3.state FROM matches m3
                       WHERE m3.device_id = d.id AND m3.state IN ('waiting','in_progress')
                       ORDER BY m3.id DESC LIMIT 1) AS active_state
               FROM devices d
          LEFT JOIN games g ON g.id = d.game_id"
            . $where . "
              ORDER BY d.name ASC",
            $args
        );

        // Globaal: 1 punt per winst. Per-spel: gewoon score-engine punten.
        $scoring = $gameId === null ? 'wins' : 'auto';

        return view('tv/index', [
            'active'      => GameMatch::active(8, $gameId),
            'top'         => array_slice(Leaderboard::players('lifetime', $gameId, 100, $scoring), 0, 10),
            'topSeason'   => array_slice(Leaderboard::players('season',   $gameId, 100, $scoring), 0, 10),
            'topWeek'     => array_slice(Leaderboard::players('week',     $gameId, 100, $scoring), 0, 5),
            'devices'     => $devices,
            'seasonLabel' => Leaderboard::currentSeasonLabel(),
            'seasonDays'  => Leaderboard::seasonDaysLeft(),
            'currentGame' => $game,
            'allGames'    => Game::all(),
            'scoringMode' => $scoring,
        ]);
    }
}
