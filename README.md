# FlexiComp

Flexibel score- en competitiesysteem voor barspellen (poolbiljart, darten, etc.). Gebruikers, teams, competities, leaderboards en QR-codes om snel mee te doen.

## Stack

- **PHP 8.1+** (vanilla, geen zwaar framework — werkt direct op Plesk)
- **MySQL / MariaDB** (via PDO)
- **Tailwind via CDN** (geen build step)
- **Composer** voor een paar libs (QR generator)

## Plesk deploy

1. Maak een MySQL database aan in Plesk en noteer host / dbname / user / pass.
2. In Plesk → Domain → **Document root** instellen op `public/` (i.p.v. `httpdocs/`).
3. Clone deze repo in de Plesk httpdocs map (of gebruik Plesk Git extension):
   ```
   git clone https://github.com/Aangekomen/gamespool.git .
   ```
4. Composer install (Plesk → PHP Composer of via SSH):
   ```
   composer install --no-dev --optimize-autoloader
   ```
5. Kopieer config:
   ```
   cp config/config.example.php config/config.php
   ```
   Vul `config/config.php` met je DB-gegevens, app URL en een random `app_secret`.
6. Run de migraties:
   ```
   php migrate.php
   ```
7. Zorg dat `public/uploads/avatars/` en `public/uploads/logos/` schrijfbaar zijn (chmod 775 of 755 met juiste owner).

## Lokaal draaien

```
php -S localhost:8000 -t public
```

Bezoek `http://localhost:8000`.

## Web-push notificaties (optioneel)

Voor push-meldingen ("bevestig de uitslag", inactiviteits-pings):

1. Installeer de library: `composer require minishlink/web-push`
2. Genereer VAPID-keys: `vendor/bin/generate-vapid-keys`
3. Voeg toe aan `config/config.php`:
   ```php
   'push' => [
       'vapid_public'  => 'BPgxxx...',
       'vapid_private' => 'xxx...',
       'subject'       => 'mailto:admin@jouwdomein.nl',
   ],
   ```
4. Cron voor inactiviteits-pings (Plesk → Scheduled Tasks, dagelijks):
   ```
   /usr/bin/php /var/www/vhosts/jouwdomein.nl/httpdocs/notify-inactive.php
   ```

Zonder deze config blijft alles werken; alleen de feitelijke push-verzending wordt overgeslagen.

## Structuur

```
config/        DB + app config (config.php is gitignored)
migrations/    SQL migrations
public/        Webroot (Plesk wijst hierheen)
src/           PHP classes (Core + Controllers + Models)
views/         PHP templates
migrate.php    CLI migratie runner
```
