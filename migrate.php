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
    try {
        $pdo->beginTransaction();
        // Naive split on `;` at end of line — sufficient for our schema files
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/m', $sql)));
        foreach ($statements as $stmt) {
            if ($stmt === '' || str_starts_with($stmt, '--')) continue;
            $pdo->exec($stmt);
        }
        $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $ins->execute([$name]);
        $pdo->commit();
        $ran++;
        echo "  ok\n";
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fwrite(STDERR, "  FAILED: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "\nDone. Applied {$ran} new migration(s).\n";
