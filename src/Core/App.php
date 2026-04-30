<?php
declare(strict_types=1);

namespace GamesPool\Core;

use GamesPool\Controllers\AdminController;
use GamesPool\Controllers\AuthController;
use GamesPool\Models\GameMatch;
use GamesPool\Controllers\GameController;
use GamesPool\Controllers\HomeController;
use GamesPool\Controllers\LeaderboardController;
use GamesPool\Controllers\MatchController;
use GamesPool\Controllers\ProfileController;
use GamesPool\Controllers\QrController;
use GamesPool\Controllers\TeamController;
use GamesPool\Controllers\TvController;

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
        $r->get('/verify/{token}',     [AuthController::class, 'verify']);
        $r->post('/verify/resend',     [AuthController::class, 'resendVerification']);
        $r->get('/password/reset/{token}',  [AuthController::class, 'showPasswordReset']);
        $r->post('/password/reset/{token}', [AuthController::class, 'resetPassword']);

        // Games
        $r->get('/games',                 [GameController::class, 'index']);
        $r->get('/games/new',             [GameController::class, 'create']);
        $r->post('/games',                [GameController::class, 'store']);
        $r->get('/games/{slug}/edit',     [GameController::class, 'edit']);
        $r->patch('/games/{slug}',        [GameController::class, 'update']);
        $r->delete('/games/{slug}',       [GameController::class, 'destroy']);

        // Matches
        $r->get('/matches',                       [MatchController::class, 'index']);
        $r->get('/matches/scan',                  [MatchController::class, 'scanPage']);
        $r->get('/matches/new',                   [MatchController::class, 'create']);
        $r->post('/matches',                      [MatchController::class, 'store']);
        $r->get('/matches/{id}',                  [MatchController::class, 'show']);
        $r->get('/matches/{id}/record',           [MatchController::class, 'recordForm']);
        $r->post('/matches/{id}/record',          [MatchController::class, 'record']);
        $r->post('/matches/{id}/rematch',         [MatchController::class, 'rematch']);
        $r->post('/matches/{id}/cancel',          [MatchController::class, 'cancel']);

        // Leaderboard
        $r->get('/leaderboard', [LeaderboardController::class, 'index']);

        // Teams
        $r->get('/teams',                                       [TeamController::class, 'index']);
        $r->get('/teams/join',                                  [TeamController::class, 'showJoin']);
        $r->post('/teams/join',                                 [TeamController::class, 'join']);
        $r->get('/teams/new',                                   [TeamController::class, 'showCreate']);
        $r->post('/teams',                                      [TeamController::class, 'create']);
        $r->get('/teams/{id}',                                  [TeamController::class, 'show']);
        $r->post('/teams/{id}/leave',                           [TeamController::class, 'leave']);
        $r->post('/teams/{id}/transfer-leave',                  [TeamController::class, 'transferAndLeave']);
        $r->post('/teams/{teamId}/members/{userId}/approve',    [TeamController::class, 'approve']);
        $r->post('/teams/{teamId}/members/{userId}/reject',     [TeamController::class, 'reject']);
        $r->post('/teams/{teamId}/members/{userId}/tag',        [TeamController::class, 'updateMemberTag']);
        $r->post('/teams/{teamId}/members/{userId}/kick',       [TeamController::class, 'kickMember']);

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
        $r->get('/m/{token}/state.json',  [MatchController::class, 'lobbyState']);
        $r->post('/m/{token}/accept',     [MatchController::class, 'acceptLobby']);
        $r->get('/qr.svg',                [QrController::class,    'svg']);
        $r->get('/scan',                  [MatchController::class, 'scanPage']);

        // Public TV / kiosk view
        $r->get('/tv',                    [TvController::class,    'index']);

        // Admin
        $r->get('/admin',                                 [AdminController::class, 'index']);
        $r->get('/admin/stats',                           [AdminController::class, 'stats']);
        $r->get('/admin/users',                                          [AdminController::class, 'usersIndex']);
        $r->get('/admin/users/{id}',                                     [AdminController::class, 'usersShow']);
        $r->post('/admin/users/{id}/toggle-admin',                       [AdminController::class, 'usersToggleAdmin']);
        $r->post('/admin/users/{id}/teams',                              [AdminController::class, 'usersAddToTeam']);
        $r->post('/admin/users/{id}/teams/{teamId}/remove',              [AdminController::class, 'usersRemoveFromTeam']);
        $r->post('/admin/users/{id}/send-reset',                         [AdminController::class, 'usersSendPasswordReset']);
        $r->post('/admin/users/{id}/resend-verification',                [AdminController::class, 'usersResendVerification']);
        $r->get('/admin/devices',                         [AdminController::class, 'devicesIndex']);
        $r->get('/admin/devices/new',                     [AdminController::class, 'devicesNew']);
        $r->post('/admin/devices',                        [AdminController::class, 'devicesStore']);
        $r->get('/admin/devices/{id}',                    [AdminController::class, 'devicesShow']);
        $r->get('/admin/devices/{id}/edit',               [AdminController::class, 'devicesEdit']);
        $r->patch('/admin/devices/{id}',                  [AdminController::class, 'devicesUpdate']);
        $r->delete('/admin/devices/{id}',                 [AdminController::class, 'devicesDestroy']);
        $r->get('/admin/devices/{id}/print',              [AdminController::class, 'devicesPrint']);

        $r->get('/admin/teams',                           [AdminController::class, 'teamsIndex']);
        $r->get('/admin/teams/{id}/edit',                 [AdminController::class, 'teamsEdit']);
        $r->patch('/admin/teams/{id}',                    [AdminController::class, 'teamsUpdate']);
        $r->post('/admin/teams/{id}/regenerate',          [AdminController::class, 'teamsRegenerateCode']);
        $r->delete('/admin/teams/{id}',                   [AdminController::class, 'teamsDestroy']);

        $r->get('/admin/matches',                         [AdminController::class, 'matchesIndex']);
        $r->get('/admin/matches/{id}/edit',               [AdminController::class, 'matchesEdit']);
        $r->patch('/admin/matches/{id}',                  [AdminController::class, 'matchesUpdate']);
        $r->delete('/admin/matches/{id}',                 [AdminController::class, 'matchesDestroy']);
    }

    public function run(): void
    {
        Csrf::verifyOrAbort();
        // Trigger remember-cookie check NOW (before any output) so the
        // setcookie() call inside Auth::tryRememberCookie() can emit headers.
        Auth::check();
        $this->maybeCleanupStaleMatches();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $this->router->dispatch($method, $uri);
    }

    /**
     * Throttled stale-match cleanup. Geen cron op Plesk nodig — eens per
     * 5 minuten draaien we vanuit een willekeurige request de SQL UPDATE.
     */
    private function maybeCleanupStaleMatches(): void
    {
        $last = (int) (Session::get('_last_cleanup', 0));
        if (time() - $last < 300) return;
        Session::set('_last_cleanup', time());
        try { GameMatch::cancelStale(); } catch (\Throwable $e) { /* stil falen */ }
    }
}
