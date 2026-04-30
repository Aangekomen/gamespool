<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Session;
use GamesPool\Models\Game;
use GamesPool\Models\GameMatch;

class MatchController
{
    public function index(): string
    {
        Auth::requireLogin();
        return view('matches/index', [
            'matches' => GameMatch::recent(50),
        ]);
    }

    public function create(): string
    {
        Auth::requireLogin();
        $games = Game::all();
        if (empty($games)) {
            Session::flash('_flash.error', 'Voeg eerst een spel toe.');
            redirect('/games/new');
        }
        return view('matches/new', [
            'games' => $games,
            'users' => Database::fetchAll('SELECT id, display_name FROM users ORDER BY display_name'),
            'errors'=> Session::pull('_errors', []),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $gameId = (int) ($_POST['game_id'] ?? 0);
        $label  = trim((string) ($_POST['label'] ?? '')) ?: null;
        $game = Game::find($gameId);
        if (!$game) {
            Session::flash('_errors', ['game_id' => ['Onbekend spel']]);
            redirect('/matches/new');
        }

        // Participants come as parallel arrays:
        // participants[user_id][], participants[guest_name][]
        $userIds    = (array) ($_POST['participants']['user_id']    ?? []);
        $guestNames = (array) ($_POST['participants']['guest_name'] ?? []);

        $participants = [];
        $rows = max(count($userIds), count($guestNames));
        for ($i = 0; $i < $rows; $i++) {
            $u = $userIds[$i]    ?? '';
            $g = trim((string) ($guestNames[$i] ?? ''));
            if ($u === '' && $g === '') continue;
            $participants[] = [
                'user_id'    => $u !== '' ? (int) $u : null,
                'guest_name' => $g !== '' ? $g : null,
            ];
        }

        if (count($participants) < 2) {
            Session::flash('_errors', ['participants' => ['Minimaal 2 deelnemers']]);
            Session::flash('_old', ['game_id' => $gameId, 'label' => $label]);
            redirect('/matches/new');
        }

        $matchId = GameMatch::create($gameId, (int) Auth::id(), $participants, $label);
        redirect('/matches/' . $matchId . '/record');
    }

    public function show(string $id): string
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        $game = Game::find((int) $match['game_id']);
        return view('matches/show', [
            'match'        => $match,
            'game'         => $game,
            'participants' => GameMatch::participants((int) $match['id']),
        ]);
    }

    public function recordForm(string $id): string
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        if ($match['state'] !== 'in_progress') {
            redirect('/matches/' . $match['id']);
        }
        $game = Game::find((int) $match['game_id']);
        return view('matches/record', [
            'match'        => $match,
            'game'         => $game,
            'participants' => GameMatch::participants((int) $match['id']),
        ]);
    }

    public function record(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        if ($match['state'] !== 'in_progress') {
            redirect('/matches/' . $match['id']);
        }

        $rows = (array) ($_POST['p'] ?? []);
        $inputs = [];
        foreach ($rows as $partId => $row) {
            $inputs[] = [
                'id'        => (int) $partId,
                'raw_score' => $row['raw_score'] ?? null,
                'result'    => $row['result']    ?? null,
            ];
        }

        try {
            GameMatch::recordResults((int) $match['id'], $inputs);
            Session::flash('_flash.success', 'Uitslag opgeslagen.');
        } catch (\Throwable $e) {
            Session::flash('_flash.error', 'Opslaan mislukt: ' . $e->getMessage());
        }
        redirect('/matches/' . $match['id']);
    }

    public function cancel(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        GameMatch::cancel((int) $match['id']);
        Session::flash('_flash.success', 'Match geannuleerd.');
        redirect('/matches');
    }

    private function notFound(): never
    {
        http_response_code(404);
        echo view('errors/404');
        exit;
    }
}
