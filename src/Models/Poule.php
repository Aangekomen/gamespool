<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;
use GamesPool\Core\Slug;

/**
 * Round-robin poule: alle deelnemers spelen 1 keer tegen elkaar. Per
 * gespeelde match krijg je 3 punten voor winst, 1 voor gelijk, 0 voor
 * verlies (apart van de game's score-config — een poule heeft een
 * eigen, klassieke poule-stand). Tiebreak op doelsaldo (raw_score).
 */
class Poule
{
    public const POULE_WIN  = 3;
    public const POULE_DRAW = 1;
    public const POULE_LOSS = 0;

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM poules WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT p.*, g.name AS game_name, g.slug AS game_slug,
                    (SELECT COUNT(*) FROM poule_participants pp WHERE pp.poule_id = p.id) AS player_count
               FROM poules p
               JOIN games g ON g.id = p.game_id
              ORDER BY (p.state = "running") DESC, (p.state = "open") DESC, p.created_at DESC'
        );
    }

    public static function create(string $name, int $gameId, int $ownerId, ?string $startsAt = null): int
    {
        $slug = Slug::unique($name, fn ($s) => (bool) Database::fetch('SELECT id FROM poules WHERE slug = ?', [$s]));
        return Database::insert(
            'INSERT INTO poules (name, slug, game_id, owner_id, starts_at) VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, $gameId, $ownerId, $startsAt]
        );
    }

    public static function delete(int $pouleId): void
    {
        Database::query('UPDATE matches SET poule_id = NULL WHERE poule_id = ?', [$pouleId]);
        Database::query('DELETE FROM poules WHERE id = ?', [$pouleId]);
    }

    public static function participants(int $pouleId): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.display_name, u.avatar_path, pp.joined_at
               FROM poule_participants pp
               JOIN users u ON u.id = pp.user_id
              WHERE pp.poule_id = ?
              ORDER BY u.display_name ASC',
            [$pouleId]
        );
    }

    public static function isParticipant(int $pouleId, int $userId): bool
    {
        return (bool) Database::fetch(
            'SELECT 1 FROM poule_participants WHERE poule_id = ? AND user_id = ? LIMIT 1',
            [$pouleId, $userId]
        );
    }

    public static function register(int $pouleId, int $userId): string
    {
        $p = self::find($pouleId);
        if (!$p || $p['state'] !== 'open') return 'closed';
        if (self::isParticipant($pouleId, $userId)) return 'already';
        Database::query(
            'INSERT INTO poule_participants (poule_id, user_id) VALUES (?, ?)',
            [$pouleId, $userId]
        );
        return 'ok';
    }

    public static function unregister(int $pouleId, int $userId): void
    {
        Database::query(
            'DELETE FROM poule_participants WHERE poule_id = ? AND user_id = ?',
            [$pouleId, $userId]
        );
    }

    /**
     * Genereer alle (n × (n-1) / 2) matches en zet poule op running.
     * Matches starten in 'in_progress' zodat spelers ze kunnen vinden;
     * volgorde is willekeurig zodat de speelorder eerlijk verdeeld is.
     */
    public static function start(int $pouleId): void
    {
        $p = self::find($pouleId);
        if (!$p || $p['state'] !== 'open') return;
        $players = self::participants($pouleId);
        if (count($players) < 2) return;

        $ids = array_map(fn ($r) => (int) $r['id'], $players);
        $pairs = [];
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $pairs[] = [$ids[$i], $ids[$j]];
            }
        }
        shuffle($pairs);

        Database::pdo()->beginTransaction();
        try {
            foreach ($pairs as $idx => [$a, $b]) {
                $token = bin2hex(random_bytes(8));
                $matchId = Database::insert(
                    'INSERT INTO matches (game_id, label, state, join_token, created_by, poule_id)
                     VALUES (?, ?, "in_progress", ?, NULL, ?)',
                    [(int) $p['game_id'], 'Poule #' . ($idx + 1), $token, $pouleId]
                );
                Database::query(
                    'INSERT INTO match_participants (match_id, user_id) VALUES (?, ?), (?, ?)',
                    [$matchId, $a, $matchId, $b]
                );
            }
            Database::query(
                'UPDATE poules SET state = "running", started_at = NOW() WHERE id = ?',
                [$pouleId]
            );
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Stand: per speler aantal gespeeld/winst/gelijk/verlies + punten + doelsaldo.
     * Punten = vaste poule-puntentelling (3/1/0), niet de game-config.
     * Doelsaldo = som(raw_score zelf) - som(raw_score tegenstander).
     * Sortering: punten DESC, doelsaldo DESC, gemaakt DESC, naam ASC.
     */
    public static function standings(int $pouleId): array
    {
        $players = self::participants($pouleId);
        if (!$players) return [];
        $byUser = [];
        foreach ($players as $p) {
            $byUser[(int) $p['id']] = [
                'user_id'      => (int) $p['id'],
                'display_name' => (string) $p['display_name'],
                'avatar_path'  => $p['avatar_path'] ?? null,
                'played'       => 0,
                'wins'         => 0,
                'draws'        => 0,
                'losses'       => 0,
                'goals_for'    => 0,
                'goals_against'=> 0,
                'points'       => 0,
            ];
        }
        $rows = Database::fetchAll(
            "SELECT m.id AS match_id, mp.user_id, mp.result, mp.raw_score
               FROM matches m
               JOIN match_participants mp ON mp.match_id = m.id
              WHERE m.poule_id = ? AND m.state = 'completed'",
            [$pouleId]
        );
        // Bouw per match een [user_id => raw_score] map zodat we doelsaldo kunnen berekenen
        $perMatch = [];
        foreach ($rows as $r) {
            $perMatch[(int) $r['match_id']][] = $r;
        }
        foreach ($perMatch as $rs) {
            if (count($rs) !== 2) continue;
            [$x, $y] = $rs;
            $xId = (int) $x['user_id']; $yId = (int) $y['user_id'];
            if (!isset($byUser[$xId]) || !isset($byUser[$yId])) continue;
            foreach ([[$x, $y], [$y, $x]] as [$me, $opp]) {
                $uid = (int) $me['user_id'];
                $byUser[$uid]['played']++;
                $byUser[$uid]['goals_for']     += (int) ($me['raw_score']  ?? 0);
                $byUser[$uid]['goals_against'] += (int) ($opp['raw_score'] ?? 0);
                if (($me['result'] ?? null) === 'win') {
                    $byUser[$uid]['wins']++;
                    $byUser[$uid]['points'] += self::POULE_WIN;
                } elseif (($me['result'] ?? null) === 'draw') {
                    $byUser[$uid]['draws']++;
                    $byUser[$uid]['points'] += self::POULE_DRAW;
                } else {
                    $byUser[$uid]['losses']++;
                    $byUser[$uid]['points'] += self::POULE_LOSS;
                }
            }
        }
        $out = array_values($byUser);
        usort($out, function ($a, $b) {
            return [$b['points'], ($b['goals_for'] - $b['goals_against']), $b['goals_for'], $a['display_name']]
                <=> [$a['points'], ($a['goals_for'] - $a['goals_against']), $a['goals_for'], $b['display_name']];
        });
        return $out;
    }

    /** Alle matches in deze poule (voor de fixture-lijst). */
    public static function matches(int $pouleId): array
    {
        return Database::fetchAll(
            "SELECT m.id, m.label, m.state, m.started_at,
                    GROUP_CONCAT(CONCAT_WS(':', mp.user_id, u.display_name, IFNULL(mp.result,''), IFNULL(mp.raw_score,''))
                                 ORDER BY mp.id ASC SEPARATOR '|') AS players
               FROM matches m
          LEFT JOIN match_participants mp ON mp.match_id = m.id
          LEFT JOIN users u ON u.id = mp.user_id
              WHERE m.poule_id = ?
              GROUP BY m.id, m.label, m.state, m.started_at
              ORDER BY m.id ASC",
            [$pouleId]
        );
    }

    /** Aantal nog te spelen matches in een lopende poule. */
    public static function remainingCount(int $pouleId): int
    {
        $row = Database::fetch(
            "SELECT COUNT(*) c FROM matches WHERE poule_id = ? AND state IN ('in_progress','waiting','pending_confirmation')",
            [$pouleId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
