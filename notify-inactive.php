<?php
/**
 * CLI: stuur push-herinnering naar gebruikers die >14 dagen niet
 * gespeeld hebben. Bedoeld voor een cronjob (Plesk → Scheduled Tasks):
 *
 *   /usr/bin/php /var/www/vhosts/<domain>/httpdocs/notify-inactive.php
 *
 * Draai bv. dagelijks om 18:00. De helper logt zelf welke gebruikers
 * deze maand al een ping kregen, dus dubbele pings worden voorkomen.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

define('BASE_PATH', __DIR__);
require BASE_PATH . '/vendor/autoload.php';

\GamesPool\Core\Config::load(BASE_PATH . '/config/config.php');

if (!\GamesPool\Core\Push::isConfigured()) {
    fwrite(STDERR, "Push is niet geconfigureerd (vapid_public/private + minishlink/web-push). Skip.\n");
    exit(1);
}

$days = (int) ($argv[1] ?? 14);
$sent = \GamesPool\Core\Push::notifyInactive($days);
echo "Verzonden: {$sent} push-bericht(en) naar inactieve gebruikers (>{$days} dagen).\n";
