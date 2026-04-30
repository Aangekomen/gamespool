<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;
use GamesPool\Models\Game;

class GameController
{
    public function index(): string
    {
        Auth::requireLogin();
        return view('games/index', [
            'games' => Game::all(),
        ]);
    }

    public function create(): string
    {
        Auth::requireLogin();
        return view('games/form', [
            'game'   => null,
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        $data = $this->payload();

        $v = (new Validator($data))
            ->required('name')->min('name', 2)->max('name', 100)
            ->required('score_type');

        $errors = $v->errors();
        if (!in_array($data['score_type'] ?? '', Game::SCORE_TYPES, true)) {
            $errors['score_type'][] = 'Onbekend score-type';
        }

        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            Session::flash('_old', $data);
            redirect('/games/new');
        }

        Game::create($data, (int) Auth::id());
        Session::flash('_flash.success', 'Spel toegevoegd.');
        redirect('/games');
    }

    public function edit(string $slug): string
    {
        Auth::requireLogin();
        $game = Game::findBySlug($slug) ?? $this->notFound();
        $this->authorize($game);
        return view('games/form', [
            'game'   => $game,
            'errors' => Session::pull('_errors', []),
        ]);
    }

    public function update(string $slug): void
    {
        Auth::requireLogin();
        $game = Game::findBySlug($slug) ?? $this->notFound();
        $this->authorize($game);
        $data = $this->payload();

        $v = (new Validator($data))
            ->required('name')->min('name', 2)->max('name', 100)
            ->required('score_type');

        $errors = $v->errors();
        if (!in_array($data['score_type'] ?? '', Game::SCORE_TYPES, true)) {
            $errors['score_type'][] = 'Onbekend score-type';
        }

        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            Session::flash('_old', $data);
            redirect('/games/' . $slug . '/edit');
        }

        Game::update((int) $game['id'], $data);
        Session::flash('_flash.success', 'Spel bijgewerkt.');
        redirect('/games');
    }

    public function destroy(string $slug): void
    {
        Auth::requireLogin();
        $game = Game::findBySlug($slug) ?? $this->notFound();
        $this->authorize($game);
        Game::delete((int) $game['id']);
        Session::flash('_flash.success', 'Spel verwijderd.');
        redirect('/games');
    }

    private function payload(): array
    {
        return [
            'name'         => trim((string) ($_POST['name'] ?? '')),
            'score_type'   => (string) ($_POST['score_type'] ?? ''),
            'score_config' => is_array($_POST['score_config'] ?? null) ? $_POST['score_config'] : [],
        ];
    }

    private function authorize(array $game): void
    {
        $u = Auth::user();
        if (!$u) redirect('/login');
        if ((int) ($game['created_by'] ?? 0) !== (int) $u['id'] && empty($u['is_admin'])) {
            http_response_code(403);
            echo 'Geen toegang tot dit spel.';
            exit;
        }
    }

    private function notFound(): never
    {
        http_response_code(404);
        echo view('errors/404');
        exit;
    }
}
