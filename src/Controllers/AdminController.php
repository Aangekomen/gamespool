<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Admin;
use GamesPool\Core\Database;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;
use GamesPool\Models\Device;
use GamesPool\Models\Game;

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

    public function usersIndex(): string
    {
        Admin::require();
        $users = Database::fetchAll(
            'SELECT u.id, u.email, u.display_name, u.first_name, u.last_name,
                    u.is_admin, u.created_at, c.name AS company_name,
                    (SELECT COUNT(*) FROM match_participants p
                       JOIN matches m ON m.id = p.match_id
                      WHERE p.user_id = u.id AND m.state = "completed") AS matches_played
               FROM users u
          LEFT JOIN companies c ON c.id = u.company_id
              ORDER BY u.created_at DESC'
        );
        return view('admin/users/index', ['users' => $users]);
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
