<?php
declare(strict_types=1);

/**
 * CLI migration runner.
 * Usage: php migrate.php
 *
 * Reads SQL files from /migrations in alphanumeric order, runs each statement,
 * and tracks applied files in the `migrations` table.
 */

define('BASE_PATH', __DIR__);

require BASE_PATH . '/vendor/autoload.php';

use GamesPool\Core\Config;
use GamesPool\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Only runnable from CLI\n");
}

Config::load(BASE_PATH . '/config/config.php');

$pdo = Database::pdo();
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_mig_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$applied = [];
foreach ($pdo->query('SELECT filename FROM migrations') as $row) {
    $applied[$row['filename']] = true;
}

$files = glob(BASE_PATH . '/migrations/*.sql') ?: [];
sort($files);

// MySQL fout-codes die "wijziging is al doorgevoerd" betekenen — we
// behandelen ze als no-op zodat half-toegepaste migraties of handmatig
// aangepaste schema's niet alles blokkeren.
$idempotentCodes = [
    '1050', // Table '...' already exists
    '1060', // Duplicate column name
    '1061', // Duplicate key name
    '1091', // Can't DROP, check it exists
    '1826', // Duplicate foreign key constraint name
];
// InnoDB-specifieke patronen voor FK-naam collisions die niet altijd een
// nette SQLSTATE-code opleveren:
$idempotentPatterns = [
    'errno: 121',                         // duplicate FK / unique key on write
    'Duplicate key on write or update',
    'Duplicate foreign key constraint',
];
function isIdempotent(\Throwable $e, array $codes, array $patterns): bool {
    $msg = $e->getMessage();
    foreach ($codes as $c) {
        if (str_contains($msg, $c)) return true;
    }
    foreach ($patterns as $p) {
        if (str_contains($msg, $p)) return true;
    }
    return false;
}

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        echo "= already applied: {$name}\n";
        continue;
    }
    echo "→ running: {$name}\n";
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "  could not read {$file}\n");
        exit(1);
    }
    // Geen omhullende transactie: DDL committeert in MySQL toch impliciet,
    // en bij idempotente fouten willen we per-statement door kunnen.
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/m', $sql)));
    $skipped = 0;
    $hardError = null;
    foreach ($statements as $stmt) {
        if ($stmt === '' || str_starts_with($stmt, '--')) continue;
        try {
            $pdo->exec($stmt);
        } catch (\Throwable $e) {
            if (isIdempotent($e, $idempotentCodes, $idempotentPatterns)) {
                $skipped++;
                echo "  ~ skipped (al toegepast): " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . "\n";
                continue;
            }
            $hardError = $e;
            break;
        }
    }
    if ($hardError) {
        fwrite(STDERR, "  FAILED: " . $hardError->getMessage() . "\n");
        exit(1);
    }
    try {
        $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)')->execute([$name]);
        $ran++;
        echo $skipped > 0
            ? "  ok ({$skipped} idempotente statement(s) overgeslagen)\n"
            : "  ok\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "  FAILED bij registratie: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "\nDone. Applied {$ran} new migration(s).\n";
