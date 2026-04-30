<?php
declare(strict_types=1);

namespace GamesPool\Core;

/**
 * Push-notification helper. Functioneel als minishlink/web-push beschikbaar
 * is (composer require minishlink/web-push). Zonder die library blijft de
 * stack draaien, alleen het feitelijke verzenden wordt overgeslagen.
 *
 * VAPID-keys instellen in config/config.php onder 'push' =>
 *     ['vapid_public' => '...', 'vapid_private' => '...', 'subject' => 'mailto:admin@example.com']
 *
 * Genereer keys met:
 *     vendor/bin/generate-vapid-keys   (van minishlink/web-push)
 *  of een online generator zoals https://vapidkeys.com/
 */
class Push
{
    public static function publicKey(): ?string
    {
        $k = (string) Config::get('push.vapid_public', '');
        return $k !== '' ? $k : null;
    }

    public static function isConfigured(): bool
    {
        return self::publicKey() !== ''
            && (string) Config::get('push.vapid_private', '') !== ''
            && class_exists('\\Minishlink\\WebPush\\WebPush');
    }

    public static function subscribe(int $userId, array $sub, ?string $userAgent): void
    {
        $endpoint = (string) ($sub['endpoint'] ?? '');
        $p256dh   = (string) ($sub['keys']['p256dh'] ?? '');
        $auth     = (string) ($sub['keys']['auth']   ?? '');
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            throw new \InvalidArgumentException('Onvolledige push-subscription.');
        }
        Database::query(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_secret, user_agent)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id),
                                     p256dh_key = VALUES(p256dh_key),
                                     auth_secret = VALUES(auth_secret),
                                     user_agent = VALUES(user_agent),
                                     last_used_at = NOW()',
            [$userId, $endpoint, $p256dh, $auth, $userAgent ? mb_substr($userAgent, 0, 250) : null]
        );
    }

    public static function unsubscribe(string $endpoint): void
    {
        Database::query('DELETE FROM push_subscriptions WHERE endpoint = ?', [$endpoint]);
    }

    /**
     * Stuur push naar 1 gebruiker (alle ingeschreven devices). Title/body/url
     * verschijnen in het notificatie-paneel. Faalt geruisloos als de package
     * of VAPID-keys niet aanwezig zijn (bijv. lokale dev).
     */
    public static function sendToUser(int $userId, string $title, string $body, ?string $url = null): int
    {
        $subs = Database::fetchAll(
            'SELECT * FROM push_subscriptions WHERE user_id = ?', [$userId]
        );
        return self::sendToSubscriptions($subs, $title, $body, $url);
    }

    /**
     * Inactiviteits-trigger: gebruikers die >$days niet gespeeld hebben.
     * Verstuurt max 1× per maand per gebruiker (push_log).
     */
    public static function notifyInactive(int $days = 14): int
    {
        if (!self::isConfigured()) return 0;
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $rows = Database::fetchAll(
            "SELECT u.id, u.first_name
               FROM users u
              WHERE NOT EXISTS (SELECT 1 FROM push_log pl
                                 WHERE pl.user_id = u.id AND pl.kind = 'inactive'
                                   AND pl.sent_at >= (NOW() - INTERVAL 30 DAY))
                AND EXISTS (SELECT 1 FROM push_subscriptions ps WHERE ps.user_id = u.id)
                AND COALESCE(
                    (SELECT MAX(m.ended_at) FROM matches m
                       JOIN match_participants p ON p.match_id = m.id
                      WHERE p.user_id = u.id AND m.state = 'completed'),
                    u.created_at
                ) < ?",
            [$cutoff]
        );
        $sent = 0;
        foreach ($rows as $u) {
            $name = (string) ($u['first_name'] ?? '');
            $msg = $name !== '' ? "Hey {$name}, kom je weer een potje spelen?" : 'Tijd voor een nieuw potje?';
            $count = self::sendToUser((int) $u['id'], '🎯 We missen je bij FlexiComp', $msg, '/scan');
            if ($count > 0) {
                Database::query(
                    "INSERT INTO push_log (user_id, kind) VALUES (?, 'inactive')",
                    [(int) $u['id']]
                );
                $sent++;
            }
        }
        return $sent;
    }

    private static function sendToSubscriptions(array $subs, string $title, string $body, ?string $url): int
    {
        if (empty($subs) || !self::isConfigured()) return 0;

        $auth = [
            'VAPID' => [
                'subject'    => (string) Config::get('push.subject', 'mailto:admin@flexicomp.local'),
                'publicKey'  => (string) Config::get('push.vapid_public'),
                'privateKey' => (string) Config::get('push.vapid_private'),
            ],
        ];
        try {
            $webPush = new \Minishlink\WebPush\WebPush($auth);
        } catch (\Throwable $e) {
            error_log('[Push] WebPush init mislukt: ' . $e->getMessage());
            return 0;
        }

        $payload = json_encode([
            'title' => $title, 'body' => $body, 'url' => $url ?? '/',
        ], JSON_UNESCAPED_UNICODE);

        $sent = 0;
        foreach ($subs as $s) {
            try {
                $sub = \Minishlink\WebPush\Subscription::create([
                    'endpoint'        => $s['endpoint'],
                    'publicKey'       => $s['p256dh_key'],
                    'authToken'       => $s['auth_secret'],
                    'contentEncoding' => 'aes128gcm',
                ]);
                $webPush->queueNotification($sub, $payload);
                $sent++;
            } catch (\Throwable $e) {
                error_log('[Push] queue mislukt: ' . $e->getMessage());
            }
        }
        // Verwerk + ruim verlopen subscriptions op
        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $code = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
                if (in_array($code, [404, 410], true)) {
                    self::unsubscribe((string) $report->getRequest()->getUri());
                }
            }
        }
        return $sent;
    }
}
