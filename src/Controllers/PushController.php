<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Database;
use GamesPool\Core\Push;
use GamesPool\Core\Session;

class PushController
{
    /** GET /push/key — VAPID public key voor de browser-subscribe call */
    public function publicKey(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['key' => Push::publicKey()]);
    }

    /** POST /push/subscribe — body = JSON van PushSubscription */
    public function subscribe(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input') ?: '';
        try {
            $sub = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ongeldige JSON']);
            return;
        }
        try {
            Push::subscribe(
                (int) Auth::id(),
                is_array($sub) ? $sub : [],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            echo json_encode(['ok' => true]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    /** POST /push/unsubscribe — body = {endpoint: ...} */
    public function unsubscribe(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input') ?: '';
        try {
            $body = json_decode($raw, true, 4, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            http_response_code(400); echo json_encode(['ok' => false]); return;
        }
        $endpoint = (string) ($body['endpoint'] ?? '');
        if ($endpoint !== '') Push::unsubscribe($endpoint);
        echo json_encode(['ok' => true]);
    }

    /** POST /push/test — stuur jezelf een test-notificatie (handig voor admins) */
    public function sendTest(): void
    {
        Auth::requireLogin();
        $sent = Push::sendToUser((int) Auth::id(), 'FlexiComp', 'Test! Je notificaties werken.', '/profile');
        Session::flash('_flash.success', $sent > 0
            ? "Test-notificatie verstuurd ($sent device(s))."
            : 'Geen device gevonden of push is niet geconfigureerd.');
        redirect('/profile/settings');
    }
}
