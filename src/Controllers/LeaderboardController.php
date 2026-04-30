<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Models\Game;
use GamesPool\Models\Leaderboard;

class LeaderboardController
{
    public function index(): string
    {
        Auth::requireLogin();

        $period = (string) ($_GET['period'] ?? 'lifetime');
        if (!in_array($period, Leaderboard::PERIODS, true)) {
            $period = 'lifetime';
        }
        $gameSlug = (string) ($_GET['game'] ?? '');
        $game = $gameSlug !== '' ? Game::findBySlug($gameSlug) : null;
        $gameId = $game ? (int) $game['id'] : null;

        return view('leaderboard/index', [
            'period'  => $period,
            'gameId'  => $gameId,
            'game'    => $game,
            'games'   => Game::all(),
            'players' => Leaderboard::players($period, $gameId),
            'teams'   => Leaderboard::teams($period, $gameId),
        ]);
    }
}
