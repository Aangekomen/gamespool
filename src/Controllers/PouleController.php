<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Admin;
use GamesPool\Core\Auth;
use GamesPool\Core\Session;
use GamesPool\Models\Game;
use GamesPool\Models\Poule;

class PouleController
{
    public function index(): string
    {
        Auth::requireLogin();
        return view('poules/index', ['poules' => Poule::all()]);
    }

    public function showCreate(): string
    {
        Admin::require();
        return view('poules/new', [
            'games'  => Game::all(),
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function create(): void
    {
        Admin::require();
        $name = trim((string) ($_POST['name'] ?? ''));
        $gameId = (int) ($_POST['game_id'] ?? 0);
        $startsAtRaw = trim((string) ($_POST['starts_at'] ?? ''));
        $startsAt = null;
        if ($startsAtRaw !== '') {
            $ts = strtotime(str_replace('T', ' ', $startsAtRaw));
            $startsAt = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }
        $errors = [];
        if (mb_strlen($name) < 2) $errors['name'][] = 'Naam minimaal 2 tekens.';
        if (!Game::find($gameId)) $errors['game_id'][] = 'Kies een bestaand spel.';
        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            redirect('/poules/new');
        }
        $id = Poule::create($name, $gameId, (int) Auth::id(), $startsAt);
        Session::flash('_flash.success', 'Poule aangemaakt — spelers kunnen zich aanmelden.');
        redirect('/poules/' . $id);
    }

    public function show(string $id): string
    {
        Auth::requireLogin();
        $p = Poule::find((int) $id);
        if (!$p) { http_response_code(404); echo view('errors/404'); exit; }

        return view('poules/show', [
            'poule'         => $p,
            'game'          => Game::find((int) $p['game_id']),
            'participants'  => Poule::participants((int) $p['id']),
            'isParticipant' => Poule::isParticipant((int) $p['id'], (int) Auth::id()),
            'standings'     => $p['state'] !== 'open' ? Poule::standings((int) $p['id']) : [],
            'matches'       => $p['state'] !== 'open' ? Poule::matches((int) $p['id']) : [],
            'remaining'     => $p['state'] === 'running' ? Poule::remainingCount((int) $p['id']) : 0,
        ]);
    }

    public function register(string $id): void
    {
        Auth::requireLogin();
        $status = Poule::register((int) $id, (int) Auth::id());
        Session::flash('_flash.success', match ($status) {
            'ok'      => 'Aangemeld voor de poule.',
            'already' => 'Je was al aangemeld.',
            default   => 'Poule is gesloten.',
        });
        redirect('/poules/' . $id);
    }

    public function unregister(string $id): void
    {
        Auth::requireLogin();
        $p = Poule::find((int) $id);
        if ($p && $p['state'] === 'open') {
            Poule::unregister((int) $id, (int) Auth::id());
            Session::flash('_flash.success', 'Aanmelding ingetrokken.');
        }
        redirect('/poules/' . $id);
    }

    public function start(string $id): void
    {
        Admin::require();
        try {
            Poule::start((int) $id);
            Session::flash('_flash.success', 'Poule gestart — alle wedstrijden zijn aangemaakt.');
        } catch (\Throwable $e) {
            Session::flash('_flash.error', 'Starten mislukt: ' . $e->getMessage());
        }
        redirect('/poules/' . $id);
    }

    public function destroy(string $id): void
    {
        Admin::require();
        Poule::delete((int) $id);
        Session::flash('_flash.success', 'Poule verwijderd.');
        redirect('/poules');
    }
}
