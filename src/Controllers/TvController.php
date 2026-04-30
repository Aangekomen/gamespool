<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Database;
use GamesPool\Models\GameMatch;
use GamesPool\Models\Leaderboard;

class TvController
{
    public function index(): string
    {
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
          LEFT JOIN games g ON g.id = d.game_id
              ORDER BY d.name ASC"
        );

        return view('tv/index', [
            'active'    => GameMatch::active(8),
            'top'       => array_slice(Leaderboard::players('lifetime'), 0, 10),
            'devices'   => $devices,
        ]);
    }
}
