<?php
declare(strict_types=1);

namespace GamesPool\Core;

use GamesPool\Controllers\AdminController;
use GamesPool\Controllers\AuthController;
use GamesPool\Controllers\GameController;
use GamesPool\Controllers\HomeController;
use GamesPool\Controllers\LeaderboardController;
use GamesPool\Controllers\MatchController;
use GamesPool\Controllers\ProfileController;
use GamesPool\Controllers\QrController;
use GamesPool\Controllers\TeamController;

class App
{
    private Router $router;

    public static function boot(): self
    {
        Config::load(BASE_PATH . '/config/config.php');

        date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

        $env = (string) Config::get('app.env', 'production');
        if ($env === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            ini_set('display_errors', '0');
        }

        Session::start();

        return new self();
    }

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $r = $this->router;

        $r->get('/',          [HomeController::class, 'index']);

        $r->get('/register',  [AuthController::class, 'showRegister']);
        $r->post('/register', [AuthController::class, 'register']);
        $r->get('/login',     [AuthController::class, 'showLogin']);
        $r->post('/login',    [AuthController::class, 'login']);
        $r->post('/logout',   [AuthController::class, 'logout']);

        // Games
        $r->get('/games',                 [GameController::class, 'index']);
        $r->get('/games/new',             [GameController::class, 'create']);
        $r->post('/games',                [GameController::class, 'store']);
        $r->get('/games/{slug}/edit',     [GameController::class, 'edit']);
        $r->patch('/games/{slug}',        [GameController::class, 'update']);
        $r->delete('/games/{slug}',       [GameController::class, 'destroy']);

        // Matches
        $r->get('/matches',                       [MatchController::class, 'index']);
        $r->get('/matches/new',                   [MatchController::class, 'create']);
        $r->post('/matches',                      [MatchController::class, 'store']);
        $r->get('/matches/{id}',                  [MatchController::class, 'show']);
        $r->get('/matches/{id}/record',           [MatchController::class, 'recordForm']);
        $r->post('/matches/{id}/record',          [MatchController::class, 'record']);
        $r->post('/matches/{id}/cancel',          [MatchController::class, 'cancel']);

        // Leaderboard
        $r->get('/leaderboard', [LeaderboardController::class, 'index']);

        // Teams
        $r->get('/teams',                                       [TeamController::class, 'index']);
        $r->get('/teams/join',                                  [TeamController::class, 'showJoin']);
        $r->post('/teams/join',                                 [TeamController::class, 'join']);
        $r->get('/teams/new',                                   [TeamController::class, 'showCreate']);
        $r->post('/teams',                                      [TeamController::class, 'create']);
        $r->post('/teams/{id}/leave',                           [TeamController::class, 'leave']);
        $r->post('/teams/{teamId}/members/{userId}/approve',    [TeamController::class, 'approve']);
        $r->post('/teams/{teamId}/members/{userId}/reject',     [TeamController::class, 'reject']);

        // Profile
        $r->get('/profile',           [ProfileController::class, 'index']);
        $r->get('/profile/settings',  [ProfileController::class, 'settings']);
        $r->patch('/profile',         [ProfileController::class, 'updateInfo']);
        $r->post('/profile/avatar',   [ProfileController::class, 'uploadAvatar']);
        $r->post('/profile/password', [ProfileController::class, 'changePassword']);
        $r->post('/profile/delete',   [ProfileController::class, 'deleteAccount']);

        // QR / device match flow
        $r->get('/d/{code}',              [MatchController::class, 'scanDevice']);
        $r->get('/m/{token}',             [MatchController::class, 'lobby']);
        $r->post('/m/{token}/accept',     [MatchController::class, 'acceptLobby']);
        $r->get('/qr.svg',                [QrController::class,    'svg']);

        // Admin
        $r->get('/admin',                                 [AdminController::class, 'index']);
        $r->get('/admin/users',                           [AdminController::class, 'usersIndex']);
        $r->get('/admin/devices',                         [AdminController::class, 'devicesIndex']);
        $r->get('/admin/devices/new',                     [AdminController::class, 'devicesNew']);
        $r->post('/admin/devices',                        [AdminController::class, 'devicesStore']);
        $r->get('/admin/devices/{id}',                    [AdminController::class, 'devicesShow']);
        $r->get('/admin/devices/{id}/edit',               [AdminController::class, 'devicesEdit']);
        $r->patch('/admin/devices/{id}',                  [AdminController::class, 'devicesUpdate']);
        $r->delete('/admin/devices/{id}',                 [AdminController::class, 'devicesDestroy']);
        $r->get('/admin/devices/{id}/print',              [AdminController::class, 'devicesPrint']);
    }

    public function run(): void
    {
        Csrf::verifyOrAbort();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $this->router->dispatch($method, $uri);
    }
}
