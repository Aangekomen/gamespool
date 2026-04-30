<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Admin;
use GamesPool\Core\Auth;
use GamesPool\Core\Session;
use GamesPool\Models\Game;
use GamesPool\Models\Tournament;

class TournamentController
{
    public function index(): string
    {
        Auth::requireLogin();
        return view('tournaments/index', [
            'tournaments' => Tournament::all(),
        ]);
    }

    public function showCreate(): string
    {
        Admin::require();
        return view('tournaments/new', [
            'games'  => Game::all(),
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function create(): void
    {
        Admin::require();
        $name   = trim((string) ($_POST['name'] ?? ''));
        $gameId = (int) ($_POST['game_id'] ?? 0);
        $maxP   = (int) ($_POST['max_players'] ?? 8);
        $errors = [];
        if ($name === '' || mb_strlen($name) < 2) $errors['name'][] = 'Naam minimaal 2 tekens.';
        if (!Game::find($gameId)) $errors['game_id'][] = 'Kies een bestaand spel.';
        if (!in_array($maxP, Tournament::SIZES, true)) $errors['max_players'][] = 'Kies 2, 4, 8 of 16 spelers.';
        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            redirect('/tournaments/new');
        }
        $id = Tournament::create($name, $gameId, $maxP, (int) Auth::id());
        Session::flash('_flash.success', 'Toernooi aangemaakt — spelers kunnen zich nu aanmelden.');
        redirect('/tournaments/' . $id);
    }

    public function show(string $id): string
    {
        Auth::requireLogin();
        $t = Tournament::find((int) $id);
        if (!$t) { http_response_code(404); echo view('errors/404'); exit; }

        return view('tournaments/show', [
            'tournament'   => $t,
            'game'         => Game::find((int) $t['game_id']),
            'participants' => Tournament::participants((int) $t['id']),
            'isParticipant' => Tournament::isParticipant((int) $t['id'], (int) Auth::id()),
            'bracket'      => $t['state'] !== 'open' ? Tournament::bracket((int) $t['id']) : [],
        ]);
    }

    public function register(string $id): void
    {
        Auth::requireLogin();
        $status = Tournament::register((int) $id, (int) Auth::id());
        Session::flash('_flash.success', match ($status) {
            'ok'      => 'Aangemeld — succes!',
            'already' => 'Je was al aangemeld.',
            'full'    => 'Toernooi is vol.',
            'closed'  => 'Toernooi is niet open voor aanmelding.',
            default   => 'Onbekende status.',
        });
        redirect('/tournaments/' . $id);
    }

    public function unregister(string $id): void
    {
        Auth::requireLogin();
        $t = Tournament::find((int) $id);
        if ($t && $t['state'] === 'open') {
            Tournament::unregister((int) $id, (int) Auth::id());
            Session::flash('_flash.success', 'Aanmelding ingetrokken.');
        }
        redirect('/tournaments/' . $id);
    }

    public function start(string $id): void
    {
        Admin::require();
        $t = Tournament::find((int) $id);
        if (!$t) redirect('/tournaments');
        try {
            Tournament::start((int) $id);
            Session::flash('_flash.success', 'Toernooi gestart!');
        } catch (\Throwable $e) {
            Session::flash('_flash.error', 'Starten mislukt: ' . $e->getMessage());
        }
        redirect('/tournaments/' . $id);
    }
}
