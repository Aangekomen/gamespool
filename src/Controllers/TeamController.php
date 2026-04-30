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
        return view('teams/index', [
            'teams'  => Team::forUser($userId),
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
            redirect('/teams');
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
            redirect('/teams');
        }

        $team = Team::findByCode($code);
        if (!$team) {
            Session::flash('_errors', ['join_code' => ['Geen team gevonden met deze code.']]);
            redirect('/teams');
        }

        $userId = (int) Auth::id();
        if (Team::isMember((int) $team['id'], $userId)) {
            Session::flash('_flash.success', 'Je zit al in team ' . $team['name'] . '.');
            redirect('/teams');
        }

        Team::addMember((int) $team['id'], $userId);
        Session::flash('_flash.success', 'Je bent toegevoegd aan team ' . $team['name'] . '.');
        redirect('/teams');
    }

    public function leave(string $id): void
    {
        Auth::requireLogin();
        Team::removeMember((int) $id, (int) Auth::id());
        Session::flash('_flash.success', 'Team verlaten.');
        redirect('/teams');
    }
}
