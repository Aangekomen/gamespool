<?php
declare(strict_types=1);

namespace GamesPool\Core;

class Mailer
{
    /**
     * Sends a plain-text email via PHP's mail(). On Plesk this routes
     * through the server's local MTA (Postfix/Qmail).
     */
    public static function send(string $to, string $subject, string $body): bool
    {
        $fromEmail = self::fromEmail();
        $fromName  = (string) Config::get('mail.from_name', (string) Config::get('app.name', 'FlexiComp'));

        // Encode subject as UTF-8 so non-ASCII characters render correctly
        $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encName    = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $headers   = [];
        $headers[] = "From: {$encName} <{$fromEmail}>";
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: FlexiComp";

        return @mail($to, $encSubject, $body, implode("\r\n", $headers), '-f' . $fromEmail);
    }

    private static function fromEmail(): string
    {
        $configured = (string) Config::get('mail.from_email', '');
        if ($configured !== '') return $configured;

        $appUrl = (string) Config::get('app.url', '');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        return 'noreply@' . $host;
    }
}
