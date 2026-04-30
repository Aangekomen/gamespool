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
            'matches' => GameMatch::recent(50, (int) Auth::id(), \GamesPool\Core\ActiveGame::id()),
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

        // Vertaal pending_payload naar mens-leesbare voorbeeld-uitslag
        $pendingPreview = null;
        if ($match['state'] === 'pending_confirmation' && !empty($match['pending_payload'])) {
            try {
                $pp = json_decode((string) $match['pending_payload'], true, 32, JSON_THROW_ON_ERROR);
                $byPid = [];
                foreach ($participants as $p) $byPid[(int) $p['id']] = $p;
                $lines = [];
                if ($game['score_type'] === 'team_score') {
                    $sideName = ['A' => [], 'B' => []];
                    $sideScore = ['A' => null, 'B' => null];
                    foreach ($pp as $row) {
                        $pid  = (int) ($row['id'] ?? 0);
                        $side = $row['match_side'] ?? null;
                        if ($side === 'A' || $side === 'B') {
                            $sideName[$side][] = $byPid[$pid]['display_name'] ?? '?';
                            if (isset($row['raw_score'])) $sideScore[$side] = (int) $row['raw_score'];
                        }
                    }
                    $lines[] = 'Team A (' . implode(', ', $sideName['A']) . ') ' . ($sideScore['A'] ?? '–') .
                               ' — Team B (' . implode(', ', $sideName['B']) . ') ' . ($sideScore['B'] ?? '–');
                } elseif ($game['score_type'] === 'points_per_match') {
                    foreach ($pp as $row) {
                        $pid = (int) ($row['id'] ?? 0);
                        $name = $byPid[$pid]['display_name'] ?? '?';
                        $lines[] = $name . ': ' . (int) ($row['raw_score'] ?? 0);
                    }
                } else {
                    foreach ($pp as $row) {
                        $pid = (int) ($row['id'] ?? 0);
                        $name = $byPid[$pid]['display_name'] ?? '?';
                        $r = $row['result'] ?? null;
                        if ($r === 'win')  $lines[] = '🏆 ' . $name . ' wint';
                        elseif ($r === 'draw') $lines[] = '⚖️ ' . $name . ' (gelijk)';
                    }
                }
                $byUser = Database::fetch('SELECT display_name FROM users WHERE id = ?', [(int) $match['pending_recorded_by']]);
                $pendingPreview = [
                    'lines'      => $lines,
                    'by_name'    => $byUser['display_name'] ?? 'iemand',
                    'by_user_id' => (int) $match['pending_recorded_by'],
                ];
            } catch (\JsonException) {}
        }

        // Best-of-N serie-context
        $series = !empty($match['series_id'])
            ? GameMatch::seriesSummary((string) $match['series_id'])
            : null;

        // Lopende takeover-vraag? Naam ophalen voor de banner.
        $takeover = null;
        if (($match['takeover_status'] ?? null) === 'pending'
            && !empty($match['takeover_requested_by'])) {
            $by = Database::fetch(
                'SELECT display_name, avatar_path FROM users WHERE id = ?',
                [(int) $match['takeover_requested_by']]
            );
            $takeover = [
                'name'   => (string) ($by['display_name'] ?? '?'),
                'avatar' => $by['avatar_path'] ?? null,
                'since'  => $match['takeover_requested_at'] ?? null,
            ];
        }

        return view('matches/show', [
            'match'          => $match,
            'game'           => $game,
            'participants'   => $participants,
            'h2h'            => $h2h,
            'pendingPreview' => $pendingPreview,
            'series'         => $series,
            'takeover'       => $takeover,
        ]);
    }

    public function recordForm(string $id): string
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        if ($match['state'] !== 'in_progress') {
            // Pending of completed: terug naar show voor confirm/dispute of view
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
            GameMatch::recordPending((int) $match['id'], (int) Auth::id(), $inputs);
            Session::flash('_flash.success', 'Uitslag genoteerd — wacht op bevestiging van een tegenstander.');
        } catch (\Throwable $e) {
            Session::flash('_flash.error', 'Opslaan mislukt: ' . $e->getMessage());
        }
        redirect('/matches/' . $match['id']);
    }

    /**
     * POST /matches/{id}/confirm — andere deelnemer bevestigt de uitslag.
     */
    public function confirm(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        if ($match['state'] !== 'pending_confirmation') {
            redirect('/matches/' . $match['id']);
        }
        $userId = (int) Auth::id();
        if ((int) ($match['pending_recorded_by'] ?? 0) === $userId) {
            Session::flash('_flash.error', 'Iemand anders moet jouw uitslag bevestigen.');
            redirect('/matches/' . $match['id']);
        }
        // Alleen deelnemers mogen bevestigen
        $isParticipant = false;
        foreach (GameMatch::participants((int) $match['id']) as $p) {
            if ((int) ($p['user_id'] ?? 0) === $userId) { $isParticipant = true; break; }
        }
        if (!$isParticipant) {
            Session::flash('_flash.error', 'Alleen deelnemers kunnen bevestigen.');
            redirect('/matches/' . $match['id']);
        }
        GameMatch::confirmPending((int) $match['id']);
        Session::flash('_flash.success', 'Uitslag bevestigd!');
        redirect('/matches/' . $match['id']);
    }

    /**
     * POST /matches/{id}/dispute — uitslag betwisten, terug naar in_progress.
     */
    public function dispute(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        if ($match['state'] !== 'pending_confirmation') {
            redirect('/matches/' . $match['id']);
        }
        $userId = (int) Auth::id();
        $isParticipant = false;
        foreach (GameMatch::participants((int) $match['id']) as $p) {
            if ((int) ($p['user_id'] ?? 0) === $userId) { $isParticipant = true; break; }
        }
        if (!$isParticipant) {
            Session::flash('_flash.error', 'Alleen deelnemers kunnen betwisten.');
            redirect('/matches/' . $match['id']);
        }
        GameMatch::disputePending((int) $match['id']);
        Session::flash('_flash.success', 'Uitslag betwist — voer de juiste uitslag opnieuw in.');
        redirect('/matches/' . $match['id'] . '/record');
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
     * Server-Sent Events stream voor live lobby/match updates. Stuurt elke
     * 2s een snapshot zolang er iets verandert; valt na 2 minuten netjes uit
     * zodat de browser opnieuw connecteert (voorkomt dat shared hosting
     * de connectie keelhalt).
     */
    public function matchStream(string $token): void
    {
        Auth::requireLogin();

        // CRITICAL: sessie loslaten vóór de lange loop. PHP file-sessions
        // locken per gebruiker, dus zonder dit blijft elke andere request
        // van dezelfde user 30+ seconden hangen achter onze SSE → 504.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $match = GameMatch::findByToken($token);
        if (!$match) { http_response_code(404); return; }

        // Output buffering uit — events moeten direct naar de client
        while (ob_get_level() > 0) ob_end_clean();
        ignore_user_abort(false);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        // Vermijd execution-limit fatal errors — we stoppen zelf netjes.
        @set_time_limit(45);

        $matchId = (int) $match['id'];
        $endsAt = time() + 30; // korte sessions; client reconnect automatisch
        $lastFingerprint = '';

        while (time() < $endsAt) {
            $current = GameMatch::find($matchId);
            if (!$current) break;
            $parts = GameMatch::participants($matchId);
            $snapshot = [
                'state'             => $current['state'],
                'participant_count' => count($parts),
                'pending_by'        => $current['pending_recorded_by'] ?? null,
                'takeover_status'   => $current['takeover_status'] ?? null,
                'takeover_by'       => $current['takeover_requested_by'] ?? null,
            ];
            $fp = md5(json_encode($snapshot));
            if ($fp !== $lastFingerprint) {
                echo "event: snapshot\n";
                echo 'data: ' . json_encode($snapshot) . "\n\n";
                $lastFingerprint = $fp;
            } else {
                echo ": keep-alive\n\n";
            }
            @flush();
            if (connection_aborted()) break;

            // Stop streaming als de match afgelopen is — client navigeert wel.
            if (in_array($current['state'], ['completed','cancelled'], true)) break;
            sleep(3);
        }
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

        // Optioneel: rematch start of vervolgt een Best-of-N serie.
        $seriesId     = $match['series_id'] ?? null;
        $seriesTarget = !empty($match['series_target']) ? (int) $match['series_target'] : null;
        $bestOf = (int) ($_POST['best_of'] ?? 0);
        if (!$seriesId && in_array($bestOf, [3, 5, 7], true)) {
            $seriesId     = bin2hex(random_bytes(8));
            $seriesTarget = $bestOf;
            // Markeer ook de huidige (originele) match als deel van de serie
            \GamesPool\Core\Database::query(
                'UPDATE matches SET series_id = ?, series_target = ? WHERE id = ?',
                [$seriesId, $seriesTarget, (int) $match['id']]
            );
        }

        $newId = GameMatch::create(
            (int) $match['game_id'],
            (int) Auth::id(),
            $participants,
            $match['label'] ?? null,
            !empty($match['device_id']) ? (int) $match['device_id'] : null
        );
        if ($seriesId) {
            \GamesPool\Core\Database::query(
                'UPDATE matches SET series_id = ?, series_target = ? WHERE id = ?',
                [$seriesId, $seriesTarget, $newId]
            );
        }
        Session::flash('_flash.success', $seriesId ? 'Volgende match in de serie!' : 'Rematch gestart!');
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
            // in_progress: misschien zit een groep al een tijd te spelen.
            // Na 5 min: vraag of ze nog doorgaan ("Speel je nog door?"-flow).
            $startTs = strtotime((string) $existing['started_at']);
            $minutesRunning = $startTs ? floor((time() - $startTs) / 60) : 0;
            if ($minutesRunning >= 5 && (int) ($existing['created_by'] ?? 0) !== (int) Auth::id()) {
                GameMatch::requestTakeover((int) $existing['id'], (int) Auth::id());
                redirect('/m/' . $existing['join_token'] . '/wait');
            }
            Session::flash('_flash.error', 'Op dit apparaat is al een match bezig.');
            redirect('/matches/' . $existing['id']);
        }

        $matchId = GameMatch::createWaiting($device, (int) Auth::id());
        $match = GameMatch::find($matchId);
        redirect('/m/' . $match['join_token']);
    }

    /**
     * Wachtkamer voor de speler die wacht tot het apparaat vrijkomt.
     * Toont de huidige spelers en hun reactie (still_playing / released).
     */
    public function takeoverWait(string $token): string
    {
        Auth::requireLogin();
        $match = GameMatch::findByToken($token);
        if (!$match) $this->notFound();

        $game     = Game::find((int) $match['game_id']);
        $device   = $match['device_id'] ? Device::find((int) $match['device_id']) : null;
        $parts    = GameMatch::participants((int) $match['id']);
        $startTs  = strtotime((string) $match['started_at']);
        $minutesRunning = $startTs ? floor((time() - $startTs) / 60) : 0;

        return view('matches/takeover_wait', [
            'match'          => $match,
            'game'           => $game,
            'device'         => $device,
            'participants'   => $parts,
            'minutesRunning' => $minutesRunning,
        ]);
    }

    /** JSON-snapshot voor de wachtkamer-poll. */
    public function takeoverState(string $token): void
    {
        Auth::requireLogin();
        $match = GameMatch::findByToken($token);
        header('Content-Type: application/json');
        if (!$match) { echo json_encode(['ok' => false]); return; }
        echo json_encode([
            'ok'              => true,
            'state'           => $match['state'],
            'takeover_status' => $match['takeover_status'] ?? null,
            'device_id'       => (int) ($match['device_id'] ?? 0),
        ]);
    }

    /**
     * Huidige speler reageert op de takeover-vraag.
     * POST body: response = 'still_playing' | 'released'
     */
    public function takeoverRespond(string $id): void
    {
        Auth::requireLogin();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        $userId = (int) Auth::id();
        $isParticipant = false;
        foreach (GameMatch::participants((int) $match['id']) as $p) {
            if ((int) ($p['user_id'] ?? 0) === $userId) { $isParticipant = true; break; }
        }
        if (!$isParticipant) {
            Session::flash('_flash.error', 'Alleen huidige spelers kunnen reageren.');
            redirect('/matches/' . $match['id']);
        }
        $response = (string) ($_POST['response'] ?? '');
        GameMatch::respondTakeover((int) $match['id'], $userId, $response);
        Session::flash('_flash.success', $response === 'released'
            ? 'Tafel vrijgegeven.'
            : 'Bedankt — we laten de wachtende speler weten dat je doorspeelt.');
        redirect('/matches/' . $match['id']);
    }

    /**
     * Wachtende speler claimt vrijgegeven tafel: maak nieuwe waiting-match.
     */
    public function takeoverClaim(string $token): void
    {
        Auth::requireLogin();
        $oldMatch = GameMatch::findByToken($token);
        if (!$oldMatch || empty($oldMatch['device_id'])) $this->notFound();
        if (($oldMatch['takeover_status'] ?? null) !== 'released') {
            Session::flash('_flash.error', 'De tafel is nog niet vrijgegeven.');
            redirect('/m/' . $token . '/wait');
        }
        // Andere actieve match? Dan was iemand anders sneller.
        $busy = GameMatch::activeForDevice((int) $oldMatch['device_id']);
        if ($busy) {
            Session::flash('_flash.error', 'Iemand anders heeft de tafel net geclaimd.');
            redirect('/matches/' . $busy['id']);
        }
        $device = Device::find((int) $oldMatch['device_id']);
        if (!$device) $this->notFound();
        $matchId = GameMatch::createWaiting($device, (int) Auth::id());
        $newMatch = GameMatch::find($matchId);
        redirect('/m/' . $newMatch['join_token']);
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
