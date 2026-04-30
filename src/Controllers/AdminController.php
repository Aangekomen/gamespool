<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Admin;
use GamesPool\Core\Auth;
use GamesPool\Core\Config;
use GamesPool\Core\Database;
use GamesPool\Core\Mailer;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;
use GamesPool\Models\Device;
use GamesPool\Models\Game;
use GamesPool\Models\GameMatch;
use GamesPool\Models\Team;

class AdminController
{
    public function index(): string
    {
        Admin::require();
        return view('admin/index', [
            'counts' => [
                'users'   => (int) (Database::fetch('SELECT COUNT(*) c FROM users')['c']    ?? 0),
                'games'   => (int) (Database::fetch('SELECT COUNT(*) c FROM games')['c']    ?? 0),
                'devices' => (int) (Database::fetch('SELECT COUNT(*) c FROM devices')['c']  ?? 0),
                'teams'   => (int) (Database::fetch('SELECT COUNT(*) c FROM teams')['c']    ?? 0),
                'matches' => (int) (Database::fetch('SELECT COUNT(*) c FROM matches')['c']  ?? 0),
            ],
        ]);
    }

    /**
     * Bar-stats dashboard: piekuren, populaire spellen, drukste tafels, etc.
     * Bedoeld voor de bar-eigenaar om te zien wanneer er actie is.
     */
    public function stats(): string
    {
        Admin::require();

        // Totalen laatste 30 dagen
        $totals = Database::fetch(
            "SELECT COUNT(*) AS matches_30d,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, started_at, IFNULL(ended_at, NOW()))),0) AS minutes_30d
               FROM matches
              WHERE state = 'completed' AND ended_at >= (NOW() - INTERVAL 30 DAY)"
        ) ?? ['matches_30d' => 0, 'minutes_30d' => 0];

        $newUsers30 = (int) (Database::fetch(
            "SELECT COUNT(*) c FROM users WHERE created_at >= (NOW() - INTERVAL 30 DAY)"
        )['c'] ?? 0);

        // Per uur (0-23) — laatste 30 dagen
        $byHour = Database::fetchAll(
            "SELECT HOUR(started_at) AS hr, COUNT(*) AS c
               FROM matches
              WHERE started_at >= (NOW() - INTERVAL 30 DAY)
                AND state IN ('completed','in_progress','waiting')
              GROUP BY HOUR(started_at)
              ORDER BY hr"
        );
        $hours = array_fill(0, 24, 0);
        foreach ($byHour as $r) $hours[(int) $r['hr']] = (int) $r['c'];

        // Per weekdag (0=zo … 6=za) — laatste 90 dagen
        $byDow = Database::fetchAll(
            "SELECT DAYOFWEEK(started_at) - 1 AS dow, COUNT(*) AS c
               FROM matches
              WHERE started_at >= (NOW() - INTERVAL 90 DAY)
                AND state IN ('completed','in_progress','waiting')
              GROUP BY dow
              ORDER BY dow"
        );
        $days = array_fill(0, 7, 0);
        foreach ($byDow as $r) $days[(int) $r['dow']] = (int) $r['c'];

        // Populairste spellen
        $topGames = Database::fetchAll(
            "SELECT g.id, g.name, g.slug,
                    COUNT(m.id) AS matches,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, m.started_at, IFNULL(m.ended_at, NOW()))),0) AS minutes
               FROM games g
          LEFT JOIN matches m ON m.game_id = g.id
                          AND m.started_at >= (NOW() - INTERVAL 30 DAY)
                          AND m.state IN ('completed','in_progress')
              GROUP BY g.id, g.name, g.slug
              ORDER BY matches DESC
              LIMIT 8"
        );

        // Drukste tafels
        $topDevices = Database::fetchAll(
            "SELECT d.id, d.name, d.code,
                    COUNT(m.id) AS matches,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, m.started_at, IFNULL(m.ended_at, NOW()))),0) AS minutes
               FROM devices d
          LEFT JOIN matches m ON m.device_id = d.id
                          AND m.started_at >= (NOW() - INTERVAL 30 DAY)
                          AND m.state IN ('completed','in_progress')
              GROUP BY d.id, d.name, d.code
              ORDER BY matches DESC
              LIMIT 8"
        );

        // Groei: nieuwe gebruikers per week, laatste 8 weken
        $growth = Database::fetchAll(
            "SELECT YEARWEEK(created_at, 1) AS yw,
                    DATE(MIN(created_at)) AS week_start,
                    COUNT(*) AS c
               FROM users
              WHERE created_at >= (NOW() - INTERVAL 8 WEEK)
              GROUP BY yw
              ORDER BY yw"
        );

        return view('admin/stats', [
            'totals'    => [
                'matches_30d' => (int) $totals['matches_30d'],
                'hours_30d'   => (int) round(((int) $totals['minutes_30d']) / 60),
                'new_users_30d' => $newUsers30,
            ],
            'hours'     => $hours,
            'days'      => $days,
            'topGames'  => $topGames,
            'topDevices'=> $topDevices,
            'growth'    => $growth,
        ]);
    }

    public function usersIndex(): string
    {
        Admin::require();
        $users = Database::fetchAll(
            'SELECT u.id, u.email, u.display_name, u.first_name, u.last_name,
                    u.avatar_path, u.is_admin, u.created_at, c.name AS company_name,
                    (SELECT COUNT(*) FROM match_participants p
                       JOIN matches m ON m.id = p.match_id
                      WHERE p.user_id = u.id AND m.state = "completed") AS matches_played
               FROM users u
          LEFT JOIN companies c ON c.id = u.company_id
              ORDER BY u.created_at DESC'
        );
        return view('admin/users/index', ['users' => $users]);
    }

    public function usersShow(string $id): string
    {
        Admin::require();
        $user = Database::fetch(
            'SELECT u.*, c.name AS company_name
               FROM users u
          LEFT JOIN companies c ON c.id = u.company_id
              WHERE u.id = ?',
            [(int) $id]
        );
        if (!$user) $this->notFound();

        $teams      = Team::forUser((int) $user['id']);
        $teamIds    = array_map(fn($t) => (int) $t['id'], $teams);
        $allTeams   = Team::allWithStats();
        $availableTeams = array_values(array_filter(
            $allTeams,
            fn($t) => !in_array((int) $t['id'], $teamIds, true)
        ));

        return view('admin/users/show', [
            'user'           => $user,
            'teams'          => $teams,
            'availableTeams' => $availableTeams,
        ]);
    }

    public function usersToggleAdmin(string $id): void
    {
        Admin::require();
        $uid = (int) $id;
        if ($uid === (int) Auth::id()) {
            Session::flash('_flash.error', 'Je kan je eigen admin-status niet wijzigen.');
            redirect('/admin/users/' . $uid);
        }
        $user = Database::fetch('SELECT id, is_admin FROM users WHERE id = ?', [$uid]);
        if (!$user) $this->notFound();
        Database::query('UPDATE users SET is_admin = ? WHERE id = ?', [(int) !$user['is_admin'], $uid]);
        Session::flash('_flash.success', 'Admin-rol bijgewerkt.');
        redirect('/admin/users/' . $uid);
    }

    public function usersAddToTeam(string $id): void
    {
        Admin::require();
        $uid = (int) $id;
        $teamId = (int) ($_POST['team_id'] ?? 0);
        $role   = ($_POST['role'] ?? 'member') === 'captain' ? 'captain' : 'member';
        $user = Database::fetch('SELECT id FROM users WHERE id = ?', [$uid]);
        $team = Team::find($teamId);
        if (!$user || !$team) {
            Session::flash('_flash.error', 'Gebruiker of team niet gevonden.');
            redirect('/admin/users/' . $uid);
        }
        Team::addMemberApproved($teamId, $uid, $role);
        Session::flash('_flash.success', 'Toegevoegd aan ' . $team['name'] . '.');
        redirect('/admin/users/' . $uid);
    }

    public function usersRemoveFromTeam(string $id, string $teamId): void
    {
        Admin::require();
        $uid = (int) $id;
        $tid = (int) $teamId;
        Team::removeMember($tid, $uid);
        Session::flash('_flash.success', 'Uit team verwijderd.');
        redirect('/admin/users/' . $uid);
    }

    public function usersSendPasswordReset(string $id): void
    {
        Admin::require();
        $uid = (int) $id;
        $user = Database::fetch('SELECT id, email, first_name FROM users WHERE id = ?', [$uid]);
        if (!$user) $this->notFound();

        $token = bin2hex(random_bytes(32));
        Database::query(
            'UPDATE users SET password_reset_token = ?, password_reset_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?',
            [$token, $uid]
        );

        $appName = (string) Config::get('app.name', 'FlexiComp');
        $resetUrl = url('/password/reset/' . $token);
        $body = "Hallo " . trim((string) $user['first_name']) . ",\n\n"
              . "Een admin van {$appName} heeft een wachtwoord-reset voor je aangevraagd.\n"
              . "Stel een nieuw wachtwoord in via deze link (24 uur geldig):\n"
              . $resetUrl . "\n\n"
              . "Heb je dit niet aangevraagd? Negeer deze mail.\n\n"
              . "— {$appName}";
        Mailer::send((string) $user['email'], "Wachtwoord opnieuw instellen", $body);

        Session::flash('_flash.success', 'Reset-link gemaild naar ' . $user['email'] . '.');
        redirect('/admin/users/' . $uid);
    }

    public function usersResendVerification(string $id): void
    {
        Admin::require();
        $uid = (int) $id;
        $user = Database::fetch('SELECT id, email, first_name, email_verified_at FROM users WHERE id = ?', [$uid]);
        if (!$user) $this->notFound();
        if ($user['email_verified_at']) {
            Session::flash('_flash.success', 'Account is al bevestigd.');
            redirect('/admin/users/' . $uid);
        }
        $token = bin2hex(random_bytes(32));
        Database::query('UPDATE users SET verification_token = ? WHERE id = ?', [$token, $uid]);

        $appName = (string) Config::get('app.name', 'FlexiComp');
        $verifyUrl = url('/verify/' . $token);
        $body = "Hallo " . trim((string) $user['first_name']) . ",\n\n"
              . "Bevestig je e-mailadres voor {$appName} via deze link:\n"
              . $verifyUrl . "\n\n"
              . "— {$appName}";
        Mailer::send((string) $user['email'], "Bevestig je e-mail voor {$appName}", $body);

        Session::flash('_flash.success', 'Bevestigingsmail verzonden.');
        redirect('/admin/users/' . $uid);
    }

    public function devicesIndex(): string
    {
        Admin::require();
        return view('admin/devices/index', [
            'devices' => Device::all(),
        ]);
    }

    public function devicesNew(): string
    {
        Admin::require();
        return view('admin/devices/form', [
            'device' => null,
            'games'  => Game::all(),
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function devicesStore(): void
    {
        Admin::require();
        $data = $this->payload();
        $errors = $this->validate($data);
        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            Session::flash('_old', $data);
            redirect('/admin/devices/new');
        }
        $id = Device::create($data['name'], $data['game_id'], $data['location']);
        Session::flash('_flash.success', 'Apparaat toegevoegd.');
        redirect('/admin/devices/' . $id);
    }

    public function devicesEdit(string $id): string
    {
        Admin::require();
        $device = Device::find((int) $id) ?? $this->notFound();
        return view('admin/devices/form', [
            'device' => $device,
            'games'  => Game::all(),
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function devicesUpdate(string $id): void
    {
        Admin::require();
        $device = Device::find((int) $id) ?? $this->notFound();
        $data = $this->payload();
        $errors = $this->validate($data);
        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            redirect('/admin/devices/' . $device['id'] . '/edit');
        }
        Device::update((int) $device['id'], $data['name'], $data['game_id'], $data['location']);
        Session::flash('_flash.success', 'Apparaat bijgewerkt.');
        redirect('/admin/devices/' . $device['id']);
    }

    public function devicesShow(string $id): string
    {
        Admin::require();
        $device = Device::find((int) $id) ?? $this->notFound();
        $game = $device['game_id'] ? Game::find((int) $device['game_id']) : null;
        return view('admin/devices/show', [
            'device' => $device,
            'game'   => $game,
            'qrUrl'  => url('/d/' . $device['code']),
        ]);
    }

    public function devicesPrint(string $id): string
    {
        Admin::require();
        $device = Device::find((int) $id) ?? $this->notFound();
        $game = $device['game_id'] ? Game::find((int) $device['game_id']) : null;
        return view('admin/devices/print', [
            'device' => $device,
            'game'   => $game,
            'qrUrl'  => url('/d/' . $device['code']),
        ]);
    }

    public function devicesDestroy(string $id): void
    {
        Admin::require();
        Device::delete((int) $id);
        Session::flash('_flash.success', 'Apparaat verwijderd.');
        redirect('/admin/devices');
    }

    // ---- Teams (admin) ----

    public function teamsIndex(): string
    {
        Admin::require();
        return view('admin/teams/index', [
            'teams' => Team::allWithStats(),
        ]);
    }

    public function teamsEdit(string $id): string
    {
        Admin::require();
        $team = Team::find((int) $id) ?? $this->notFound();
        return view('admin/teams/edit', [
            'team'    => $team,
            'members' => Team::membersWithRoles((int) $team['id']),
            'errors'  => Session::pull('_errors', []),
        ]);
    }

    public function teamsUpdate(string $id): void
    {
        Admin::require();
        $team = Team::find((int) $id) ?? $this->notFound();
        $name = trim((string) ($_POST['name'] ?? ''));
        $v = (new Validator(['name' => $name]))->required('name')->min('name', 2)->max('name', 100);
        if ($v->fails()) {
            Session::flash('_errors', $v->errors());
            redirect('/admin/teams/' . $team['id'] . '/edit');
        }
        Team::rename((int) $team['id'], $name);
        Session::flash('_flash.success', 'Team bijgewerkt.');
        redirect('/admin/teams/' . $team['id'] . '/edit');
    }

    public function teamsRegenerateCode(string $id): void
    {
        Admin::require();
        $team = Team::find((int) $id) ?? $this->notFound();
        $code = Team::regenerateCode((int) $team['id']);
        Session::flash('_flash.success', 'Nieuwe join-code: ' . $code);
        redirect('/admin/teams/' . $team['id'] . '/edit');
    }

    public function teamsDestroy(string $id): void
    {
        Admin::require();
        $team = Team::find((int) $id) ?? $this->notFound();
        Team::delete((int) $team['id']);
        Session::flash('_flash.success', 'Team verwijderd.');
        redirect('/admin/teams');
    }

    // ---- Matches (admin) ----

    public function matchesIndex(): string
    {
        Admin::require();
        return view('admin/matches/index', [
            'matches' => GameMatch::allRecent(100),
        ]);
    }

    public function matchesEdit(string $id): string
    {
        Admin::require();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        return view('admin/matches/edit', [
            'match'        => $match,
            'game'         => Game::find((int) $match['game_id']),
            'participants' => GameMatch::participants((int) $match['id']),
            'errors'       => Session::pull('_errors', []),
        ]);
    }

    public function matchesUpdate(string $id): void
    {
        Admin::require();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        $label = trim((string) ($_POST['label'] ?? '')) ?: null;
        GameMatch::updateLabel((int) $match['id'], $label);
        Session::flash('_flash.success', 'Match bijgewerkt.');
        redirect('/admin/matches/' . $match['id'] . '/edit');
    }

    public function matchesDestroy(string $id): void
    {
        Admin::require();
        $match = GameMatch::find((int) $id) ?? $this->notFound();
        GameMatch::delete((int) $match['id']);
        Session::flash('_flash.success', 'Match verwijderd.');
        redirect('/admin/matches');
    }

    // ---- helpers ----

    private function payload(): array
    {
        $gameId = trim((string) ($_POST['game_id'] ?? ''));
        return [
            'name'     => trim((string) ($_POST['name'] ?? '')),
            'game_id'  => $gameId === '' ? null : (int) $gameId,
            'location' => trim((string) ($_POST['location'] ?? '')) ?: null,
        ];
    }

    private function validate(array $data): array
    {
        $v = (new Validator($data))
            ->required('name')->min('name', 2)->max('name', 120);
        return $v->errors();
    }

    private function notFound(): never
    {
        http_response_code(404);
        echo view('errors/404');
        exit;
    }
}
