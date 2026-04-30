<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Session;
use GamesPool\Models\Device;
use GamesPool\Models\Game;
use GamesPool\Models\GameMatch;

class MatchController
{
    public function index(): string
    {
        Auth::requireLogin();
        return view('matches/index', [
            'matches' => GameMatch::recent(50, (int) Auth::id()),
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

        $lockedGame = null;
        $rawGameId = $_GET['game_id'] ?? null;
        if ($rawGameId !== null) {
            $candidate = Game::find((int) $rawGameId);
            if ($candidate) $lockedGame = $candidate;
        }

        return view('matches/new', [
            'games'      => $games,
            'lockedGame' => $lockedGame,
            'users'      => Database::fetchAll('SELECT id, display_name FROM users ORDER BY display_name'),
            'errors'     => Session::pull('_errors', []),
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

        // Only user_ids; guest play is removed
        $userIds = (array) ($_POST['participants']['user_id'] ?? []);

        $participants = [];
        $seen   = [];
        $errors = [];

        foreach ($userIds as $u) {
            $uid = (int) $u;
            if ($uid <= 0) continue;
            if (isset($seen[$uid])) {
                $errors['participants'][] = 'Een speler kan niet twee keer meedoen.';
                continue;
            }
            $seen[$uid] = true;
            $participants[] = ['user_id' => $uid];
        }

        if (count($participants) < 2) {
            $errors['participants'][] = 'Minimaal 2 deelnemers.';
        }
        if ($game['score_type'] === 'elo' && count($participants) !== 2) {
            $errors['participants'][] = 'Elo-spellen ondersteunen alleen 1-tegen-1.';
        }

        if (!empty($errors)) {
            $errors['participants'] = array_values(array_unique($errors['participants']));
            Session::flash('_errors', $errors);
            Session::flash('_old', ['game_id' => $gameId, 'label' => $label]);
            $back = '/matches/new' . ($gameId ? '?game_id=' . $gameId : '');
            redirect($back);
        }

        // Optional device code attaches the match to a printed QR-station
        $deviceId = null;
        $deviceCode = strtoupper(trim((string) ($_POST['device_code'] ?? '')));
        if ($deviceCode !== '') {
            $device = Device::findByCode($deviceCode);
            if (!$device) {
                Session::flash('_errors', ['device_code' => ['Onbekende apparaat-code.']]);
                Session::flash('_old', ['game_id' => $gameId, 'label' => $label, 'device_code' => $deviceCode]);
                $back = '/matches/new' . ($gameId ? '?game_id=' . $gameId : '');
                redirect($back);
            }
            // One active match per device
            $busy = GameMatch::activeForDevice((int) $device['id']);
            if ($busy) {
                Session::flash('_errors', ['device_code' => ['Op dit apparaat is al een match bezig.']]);
                Session::flash('_old', ['game_id' => $gameId, 'label' => $label, 'device_code' => $deviceCode]);
                $back = '/matches/new' . ($gameId ? '?game_id=' . $gameId : '');
                redirect($back);
            }
            $deviceId = (int) $device['id'];
        }

        $matchId = GameMatch::create($gameId, (int) Auth::id(), $participants, $label, $deviceId);
        redirect('/matches/' . $matchId . '/record');
    }

    public function show(string $id): string
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        $game = Game::find((int) $match['game_id']);
        $participants = GameMatch::participants((int) $match['id']);

        // Head-to-head balans alleen tonen bij exact 2 deelnemers (1v1).
        $h2h = null;
        if (count($participants) === 2 && $match['state'] === 'completed') {
            $a = (int) ($participants[0]['user_id'] ?? 0);
            $b = (int) ($participants[1]['user_id'] ?? 0);
            if ($a > 0 && $b > 0) {
                $h2h = [
                    'a_name'  => $participants[0]['display_name'] ?? '',
                    'b_name'  => $participants[1]['display_name'] ?? '',
                    'data'    => GameMatch::headToHead($a, $b, (int) $match['game_id']),
                ];
            }
        }

        return view('matches/show', [
            'match'        => $match,
            'game'         => $game,
            'participants' => $participants,
            'h2h'          => $h2h,
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
        } elseif ($game['score_type'] === 'team_score') {
            $sides       = (array) ($_POST['side'] ?? []);
            $teamScores  = (array) ($_POST['team_score'] ?? []);
            $scoreA      = isset($teamScores['A']) ? (int) $teamScores['A'] : null;
            $scoreB      = isset($teamScores['B']) ? (int) $teamScores['B'] : null;

            // At least 1 player per side + both scores set
            $countA = 0; $countB = 0;
            foreach ($existing as $p) {
                $side = $sides[(int) $p['id']] ?? null;
                if ($side === 'A') $countA++; elseif ($side === 'B') $countB++;
            }
            if ($scoreA === null || $scoreB === null || $countA < 1 || $countB < 1) {
                Session::flash('_flash.error', 'Verdeel alle spelers over Team A en Team B en vul beide scores in.');
                redirect('/matches/' . $match['id'] . '/record');
            }

            foreach ($existing as $p) {
                $side  = $sides[(int) $p['id']] ?? null;
                $score = $side === 'A' ? $scoreA : ($side === 'B' ? $scoreB : null);
                $inputs[] = [
                    'id'         => (int) $p['id'],
                    'match_side' => $side,
                    'raw_score'  => $score,
                    'result'     => null,
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

    /**
     * Lightweight JSON snapshot for lobby polling — avoids full page reloads.
     */
    public function lobbyState(string $token): void
    {
        Auth::requireLogin();
        $match = GameMatch::findByToken($token);
        header('Content-Type: application/json');
        if (!$match) { echo json_encode(['ok' => false]); return; }
        $parts = GameMatch::participants((int) $match['id']);
        echo json_encode([
            'ok'                => true,
            'state'             => $match['state'],
            'participant_count' => count($parts),
            'match_id'          => (int) $match['id'],
        ]);
    }

    /**
     * Show the in-app QR scanner page (camera + manual code entry).
     */
    public function scanPage(): string
    {
        Auth::requireLogin();
        $devices = Database::fetchAll(
            "SELECT d.id, d.name, d.code, g.name AS game_name,
                    (SELECT COUNT(*) FROM matches m
                       WHERE m.device_id = d.id AND m.state IN ('waiting','in_progress')) AS active_count,
                    (SELECT m2.started_at FROM matches m2
                       WHERE m2.device_id = d.id AND m2.state IN ('waiting','in_progress')
                       ORDER BY m2.id DESC LIMIT 1) AS active_started_at
               FROM devices d
          LEFT JOIN games g ON g.id = d.game_id
              ORDER BY d.name ASC"
        );
        return view('matches/scan', ['devices' => $devices]);
    }

    /**
     * /matches/{id}/rematch — create a new match with the same game and the
     * same registered participants. Lets a group "punten opsparen" over a
     * series without having to re-enter players each time.
     */
    public function rematch(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();

        $existing = GameMatch::participants((int) $match['id']);
        $participants = [];
        foreach ($existing as $p) {
            if (!empty($p['user_id'])) {
                $participants[] = [
                    'user_id' => (int) $p['user_id'],
                    'team_id' => $p['team_id'] ?? null,
                ];
            }
        }
        if (count($participants) < 2) {
            Session::flash('_flash.error', 'Niet genoeg deelnemers voor een rematch.');
            redirect('/matches/' . $match['id']);
        }
        $newId = GameMatch::create(
            (int) $match['game_id'],
            (int) Auth::id(),
            $participants,
            $match['label'] ?? null,
            !empty($match['device_id']) ? (int) $match['device_id'] : null
        );
        Session::flash('_flash.success', 'Rematch gestart!');
        redirect('/matches/' . $newId . '/record');
    }

    public function cancel(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        GameMatch::cancel((int) $match['id']);
        Session::flash('_flash.success', 'Match geannuleerd.');
        redirect('/matches');
    }

    /**
     * /d/<code> — landing after scanning a printed device QR code.
     * If a waiting match for this device already exists → join lobby.
     * Otherwise → create a fresh waiting match with current user as host.
     */
    public function scanDevice(string $code): void
    {
        Auth::requireLogin();
        $device = Device::findByCode($code);
        if (!$device) {
            http_response_code(404);
            echo view('errors/404');
            exit;
        }

        // One active match per device — redirect to existing if any
        $existing = GameMatch::activeForDevice((int) $device['id']);
        if ($existing) {
            if ($existing['state'] === 'waiting') {
                redirect('/m/' . $existing['join_token']);
            }
            // in_progress → can't start a new one
            Session::flash('_flash.error', 'Op dit apparaat is al een match bezig.');
            redirect('/matches/' . $existing['id']);
        }

        $matchId = GameMatch::createWaiting($device, (int) Auth::id());
        $match = GameMatch::find($matchId);
        redirect('/m/' . $match['join_token']);
    }

    /**
     * /m/<token> — match lobby + share screen.
     */
    public function lobby(string $token): string
    {
        Auth::requireLogin();
        $match = GameMatch::findByToken($token);
        if (!$match) $this->notFound();

        if ($match['state'] === 'in_progress' || $match['state'] === 'completed') {
            redirect('/matches/' . $match['id']);
        }

        $game   = Game::find((int) $match['game_id']);
        $device = $match['device_id'] ? Device::find((int) $match['device_id']) : null;
        $parts  = GameMatch::participants((int) $match['id']);
        $userId = (int) Auth::id();
        $isHost        = (int) $match['created_by'] === $userId;
        $isParticipant = false;
        foreach ($parts as $p) {
            if ((int) ($p['user_id'] ?? 0) === $userId) { $isParticipant = true; break; }
        }

        return view('matches/lobby', [
            'match'         => $match,
            'game'          => $game,
            'device'        => $device,
            'participants'  => $parts,
            'isHost'        => $isHost,
            'isParticipant' => $isParticipant,
            'shareUrl'      => url('/m/' . $match['join_token']),
        ]);
    }

    /**
     * POST /m/<token>/accept — current user accepts the invite, match becomes in_progress.
     */
    public function acceptLobby(string $token): void
    {
        Auth::requireLogin();
        $match = GameMatch::findByToken($token) ?? $this->notFound();
        if ($match['state'] !== 'waiting') {
            redirect('/matches/' . $match['id']);
        }
        try {
            GameMatch::acceptInvite((int) $match['id'], (int) Auth::id());
        } catch (\Throwable $e) {
            Session::flash('_flash.error', 'Aansluiten mislukt: ' . $e->getMessage());
            redirect('/m/' . $token);
        }
        Session::flash('_flash.success', 'Match gestart!');
        redirect('/matches/' . $match['id']);
    }

    private function notFound(): never
    {
        http_response_code(404);
        echo view('errors/404');
        exit;
    }
}
