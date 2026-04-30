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

        // Parallel arrays: participants[user_id][], participants[guest_name][]
        $userIds    = (array) ($_POST['participants']['user_id']    ?? []);
        $guestNames = (array) ($_POST['participants']['guest_name'] ?? []);

        $participants = [];
        $seenUsers  = [];
        $seenGuests = [];
        $errors = [];

        $rows = max(count($userIds), count($guestNames));
        for ($i = 0; $i < $rows; $i++) {
            $u = $userIds[$i]    ?? '';
            $g = trim((string) ($guestNames[$i] ?? ''));

            // Empty row → skip
            if ($u === '' && $g === '') continue;

            // Can't have both user_id AND guest_name on the same row
            if ($u !== '' && $g !== '') {
                $errors['participants'][] = 'Vul per rij óf een speler óf een gastnaam in, niet allebei.';
                continue;
            }

            if ($u !== '') {
                $uid = (int) $u;
                if (isset($seenUsers[$uid])) {
                    $errors['participants'][] = 'Een speler kan niet twee keer meedoen.';
                    continue;
                }
                $seenUsers[$uid] = true;
                $participants[] = ['user_id' => $uid, 'guest_name' => null];
            } else {
                $key = mb_strtolower($g);
                if (isset($seenGuests[$key])) {
                    $errors['participants'][] = 'Dezelfde gastnaam komt twee keer voor.';
                    continue;
                }
                $seenGuests[$key] = true;
                $participants[] = ['user_id' => null, 'guest_name' => $g];
            }
        }

        if (count($participants) < 2) {
            $errors['participants'][] = 'Minimaal 2 unieke deelnemers.';
        }
        // Elo only supports 1v1 cleanly
        if ($game['score_type'] === 'elo' && count($participants) !== 2) {
            $errors['participants'][] = 'Elo-spellen ondersteunen alleen 1-tegen-1.';
        }

        if (!empty($errors)) {
            // Dedupe error messages
            $errors['participants'] = array_values(array_unique($errors['participants']));
            Session::flash('_errors', $errors);
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
        $game = Game::find((int) $match['game_id']);
        if (!$game) $this->notFound();

        $existing = GameMatch::participants((int) $match['id']);
        $inputs   = [];

        if ($game['score_type'] === 'points_per_match') {
            $rows = (array) ($_POST['p'] ?? []);
            foreach ($existing as $p) {
                $row = $rows[(int) $p['id']] ?? [];
                $inputs[] = [
                    'id'        => (int) $p['id'],
                    'raw_score' => isset($row['raw_score']) ? (int) $row['raw_score'] : 0,
                    'result'    => null, // engine derives
                ];
            }
        } else {
            // win_loss + elo: outcome_mode = 'winner' (with winner_id) or 'draw'
            $mode    = (string) ($_POST['outcome_mode'] ?? '');
            $winnerId = (int) ($_POST['winner_id'] ?? 0);

            if ($mode === 'draw') {
                foreach ($existing as $p) {
                    $inputs[] = ['id' => (int) $p['id'], 'raw_score' => null, 'result' => 'draw'];
                }
            } elseif ($mode === 'winner' && $winnerId > 0) {
                $valid = false;
                foreach ($existing as $p) {
                    $isWin = ((int) $p['id'] === $winnerId);
                    if ($isWin) $valid = true;
                    $inputs[] = [
                        'id'        => (int) $p['id'],
                        'raw_score' => null,
                        'result'    => $isWin ? 'win' : 'loss',
                    ];
                }
                if (!$valid) {
                    Session::flash('_flash.error', 'Geldige winnaar is verplicht.');
                    redirect('/matches/' . $match['id'] . '/record');
                }
            } else {
                Session::flash('_flash.error', 'Kies een winnaar of "gelijkspel".');
                redirect('/matches/' . $match['id'] . '/record');
            }
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
