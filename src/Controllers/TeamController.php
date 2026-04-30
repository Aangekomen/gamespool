<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;
use GamesPool\Models\Team;

class TeamController
{
    public function index(): string
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $myTeams = Team::forUser($userId);
        $myIds   = array_map(fn($t) => (int) $t['id'], $myTeams);

        // For each captain'd team, also show pending join requests
        $pendingPerTeam = [];
        foreach ($myTeams as $t) {
            if (Team::isCaptain((int) $t['id'], $userId)) {
                $pendingPerTeam[(int) $t['id']] = Team::pendingRequests((int) $t['id']);
            }
        }

        $otherTeams = array_values(array_filter(
            Team::allWithStats(),
            fn($t) => !in_array((int) $t['id'], $myIds, true)
        ));

        return view('teams/index', [
            'teams'          => $myTeams,
            'otherTeams'     => $otherTeams,
            'pendingMine'    => Team::pendingForUser($userId),
            'pendingPerTeam' => $pendingPerTeam,
        ]);
    }

    public function show(string $id): string
    {
        Auth::requireLogin();
        $team = Team::find((int) $id);
        if (!$team) {
            http_response_code(404);
            echo view('errors/404');
            exit;
        }
        $userId = (int) Auth::id();
        return view('teams/show', [
            'team'      => $team,
            'members'   => Team::membersWithRoles((int) $team['id']),
            'matches'   => Team::matchesPlayed((int) $team['id'], 25),
            'isMember'  => Team::isMember((int) $team['id'], $userId),
            'isCaptain' => Team::isCaptain((int) $team['id'], $userId),
        ]);
    }

    public function showJoin(): string
    {
        Auth::requireLogin();
        return view('teams/join', [
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function showCreate(): string
    {
        Auth::requireLogin();
        return view('teams/new', [
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        $name = trim((string) ($_POST['name'] ?? ''));

        $v = (new Validator(['name' => $name]))
            ->required('name')->min('name', 2)->max('name', 100);

        if ($v->fails()) {
            Session::flash('_errors', $v->errors());
            Session::flash('_old', ['name' => $name]);
            redirect('/teams/new');
        }

        $teamId = Team::create($name, (int) Auth::id());
        $team   = Team::find($teamId);
        Session::flash('_flash.success', 'Team aangemaakt. Join-code: ' . ($team['join_code'] ?? '–'));
        redirect('/teams');
    }

    public function join(): void
    {
        Auth::requireLogin();
        $code = preg_replace('/\D/', '', (string) ($_POST['join_code'] ?? '')) ?? '';

        if (strlen($code) !== 6) {
            Session::flash('_errors', ['join_code' => ['Voer een 6-cijferige code in.']]);
            redirect('/teams/join');
        }

        $team = Team::findByCode($code);
        if (!$team) {
            Session::flash('_errors', ['join_code' => ['Geen team gevonden met deze code.']]);
            redirect('/teams/join');
        }

        $userId = (int) Auth::id();
        $status = Team::requestJoin((int) $team['id'], $userId);

        Session::flash('_flash.success', match ($status) {
            'approved' => 'Je zit in team ' . $team['name'] . '.',
            'pending'  => 'Verzoek verstuurd. De captain van ' . $team['name'] . ' moet je nog goedkeuren.',
            default    => 'Verzoek verstuurd.',
        });
        redirect('/teams');
    }

    public function approve(string $teamId, string $userId): void
    {
        Auth::requireLogin();
        $tid = (int) $teamId;
        $uid = (int) $userId;
        if (!Team::isCaptain($tid, (int) Auth::id())) {
            http_response_code(403); echo 'Alleen de captain kan goedkeuren.'; exit;
        }
        Team::approveMember($tid, $uid);
        Session::flash('_flash.success', 'Lid goedgekeurd.');
        redirect('/teams');
    }

    public function reject(string $teamId, string $userId): void
    {
        Auth::requireLogin();
        $tid = (int) $teamId;
        $uid = (int) $userId;
        if (!Team::isCaptain($tid, (int) Auth::id())) {
            http_response_code(403); echo 'Alleen de captain kan afwijzen.'; exit;
        }
        Team::rejectMember($tid, $uid);
        Session::flash('_flash.success', 'Verzoek afgewezen.');
        redirect('/teams');
    }

    public function leave(string $id): void
    {
        Auth::requireLogin();
        $tid = (int) $id;
        $userId = (int) Auth::id();

        // Captain can only leave by transferring captaincy when others are approved members
        if (Team::isCaptain($tid, $userId)) {
            $others = array_values(array_filter(
                Team::membersWithRoles($tid),
                fn ($m) => (int) $m['id'] !== $userId
            ));
            if (!empty($others)) {
                Session::flash('_flash.error', 'Draag eerst captainship over voor je vertrekt.');
                redirect('/teams/' . $tid);
            }
        }

        Team::removeMember($tid, $userId);
        Session::flash('_flash.success', 'Team verlaten.');
        redirect('/teams');
    }

    public function transferAndLeave(string $id): void
    {
        Auth::requireLogin();
        $tid = (int) $id;
        $userId = (int) Auth::id();
        if (!Team::isCaptain($tid, $userId)) {
            redirect('/teams/' . $tid);
        }
        $newCaptainId = (int) ($_POST['new_captain_id'] ?? 0);
        if ($newCaptainId <= 0 || $newCaptainId === $userId) {
            Session::flash('_flash.error', 'Kies een geldige nieuwe captain.');
            redirect('/teams/' . $tid);
        }
        if (!Team::isMember($tid, $newCaptainId)) {
            Session::flash('_flash.error', 'Persoon zit niet in dit team.');
            redirect('/teams/' . $tid);
        }
        Team::transferCaptaincy($tid, $newCaptainId);
        Team::removeMember($tid, $userId);
        Session::flash('_flash.success', 'Captainship overgedragen — je bent uit het team.');
        redirect('/teams');
    }

    public function updateMemberTag(string $teamId, string $userId): void
    {
        Auth::requireLogin();
        $tid = (int) $teamId;
        $uid = (int) $userId;
        if (!Team::isCaptain($tid, (int) Auth::id())) {
            http_response_code(403); echo 'Alleen de captain kan tags wijzigen.'; exit;
        }
        $tag = trim((string) ($_POST['tag'] ?? ''));
        Team::setMemberTag($tid, $uid, $tag !== '' ? $tag : null);
        Session::flash('_flash.success', 'Tag bijgewerkt.');
        redirect('/teams/' . $tid);
    }

    public function kickMember(string $teamId, string $userId): void
    {
        Auth::requireLogin();
        $tid = (int) $teamId;
        $uid = (int) $userId;
        if (!Team::isCaptain($tid, (int) Auth::id())) {
            http_response_code(403); echo 'Alleen de captain kan leden verwijderen.'; exit;
        }
        if ($uid === (int) Auth::id()) {
            Session::flash('_flash.error', 'Captain kan zichzelf niet verwijderen.');
            redirect('/teams/' . $tid);
        }
        Team::removeMember($tid, $uid);
        Session::flash('_flash.success', 'Lid verwijderd.');
        redirect('/teams/' . $tid);
    }
}
