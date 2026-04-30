<?php
declare(strict_types=1);

namespace GamesPool\Core;

use GamesPool\Controllers\AuthController;
use GamesPool\Controllers\HomeController;

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
    }

    public function run(): void
    {
        Csrf::verifyOrAbort();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $this->router->dispatch($method, $uri);
    }
}
