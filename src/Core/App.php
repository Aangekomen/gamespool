<?php
declare(strict_types=1);

namespace GamesPool\Core;

use GamesPool\Controllers\AuthController;
use GamesPool\Controllers\GameController;
use GamesPool\Controllers\HomeController;
use GamesPool\Controllers\MatchController;

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
    }

    public function run(): void
    {
        Csrf::verifyOrAbort();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $this->router->dispatch($method, $uri);
    }
}
