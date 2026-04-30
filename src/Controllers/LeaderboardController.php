<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\ActiveGame;
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
        // Query string > active-game cookie. 'game=all' override = expliciet ALLES.
        $gameSlug = $_GET['game'] ?? null;
        if ($gameSlug === null) $gameSlug = ActiveGame::slug();
        if ($gameSlug === 'all') $gameSlug = '';
        $game = $gameSlug ? Game::findBySlug((string) $gameSlug) : null;
        $gameId = $game ? (int) $game['id'] : null;

        // Optionele history-view: ?season=2025Q4 → expliciete range, override period
        $seasonKey   = trim((string) ($_GET['season'] ?? ''));
        $seasonRange = null;
        $seasonLabel = null;
        if ($seasonKey !== '') {
            $parsed = Leaderboard::parseSeasonKey($seasonKey);
            if ($parsed) {
                $seasonRange = Leaderboard::seasonRange($parsed['year'], $parsed['quarter']);
                $seasonLabel = $seasonRange['label'];
                $period = 'season';
            }
        }

        $players = $seasonRange
            ? Leaderboard::players($period, $gameId, 100, 'auto', $seasonRange['since'], $seasonRange['until'])
            : Leaderboard::players($period, $gameId);
        $teams = $seasonRange
            ? Leaderboard::teams($period, $gameId, 100, $seasonRange['since'], $seasonRange['until'])
            : Leaderboard::teams($period, $gameId);

        return view('leaderboard/index', [
            'period'      => $period,
            'gameId'      => $gameId,
            'game'        => $game,
            'games'       => Game::all(),
            'players'     => $players,
            'teams'       => $teams,
            'seasons'     => Leaderboard::seasonsAvailable(),
            'seasonKey'   => $seasonKey ?: null,
            'seasonLabel' => $seasonLabel,
        ]);
    }
}
