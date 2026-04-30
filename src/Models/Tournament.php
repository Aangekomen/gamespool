<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;
use GamesPool\Core\Slug;

/**
 * Single-elimination toernooi. Spelers zijn gepowered tot 2/4/8/16; lege
 * slots krijgen een "bye" (tegenstander = null) en de overige speler
 * gaat automatisch door.
 *
 * Bracket-coördinaten:
 *  - bracket_round: 1 = eerste ronde, 2 = kwartfinale, ...
 *  - bracket_slot:  0..(bracket_size/2^round - 1) — index binnen de ronde
 */
class Tournament
{
    /** Quick-pick brackets in de admin-form. Custom getallen (2..32) zijn ook geldig. */
    public const SIZES = [2, 4, 8, 16];
    public const MAX_PLAYERS = 32;

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM tournaments WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM tournaments WHERE slug = ?', [$slug]);
    }

    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT t.*, g.name AS game_name, g.slug AS game_slug,
                    (SELECT COUNT(*) FROM tournament_participants tp WHERE tp.tournament_id = t.id) AS player_count,
                    u.display_name AS winner_name
               FROM tournaments t
               JOIN games g ON g.id = t.game_id
          LEFT JOIN users u ON u.id = t.winner_id
              ORDER BY (t.state = "running") DESC, (t.state = "open") DESC, t.created_at DESC'
        );
    }

    public static function create(string $name, int $gameId, int $maxPlayers, int $ownerId, ?string $startsAt = null): int
    {
        $maxPlayers = max(2, min(self::MAX_PLAYERS, $maxPlayers));
        $slug = Slug::unique($name, fn ($s) => (bool) Database::fetch('SELECT id FROM tournaments WHERE slug = ?', [$s]));
        return Database::insert(
            'INSERT INTO tournaments (name, slug, game_id, max_players, owner_id, starts_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $slug, $gameId, $maxPlayers, $ownerId, $startsAt]
        );
    }

    public static function delete(int $tournamentId): void
    {
        // Detach matches (we behouden de match-historie, set tournament_id NULL)
        Database::query('UPDATE matches SET tournament_id = NULL WHERE tournament_id = ?', [$tournamentId]);
        Database::query('DELETE FROM tournaments WHERE id = ?', [$tournamentId]);
    }

    /** Open of komende toernooien (nog niet voorbij). */
    public static function upcoming(int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT t.*, g.name AS game_name, g.slug AS game_slug,
                    (SELECT COUNT(*) FROM tournament_participants tp WHERE tp.tournament_id = t.id) AS player_count
               FROM tournaments t
               JOIN games g ON g.id = t.game_id
              WHERE t.state IN ('open','running')
                AND (t.starts_at IS NULL OR t.starts_at >= (NOW() - INTERVAL 1 DAY))
              ORDER BY (t.starts_at IS NULL) ASC, t.starts_at ASC, t.created_at DESC
              LIMIT " . (int) $limit
        );
    }

    /** Eerstvolgende macht-van-2 ≥ n; minimaal 2. */
    public static function bracketSize(int $n): int
    {
        if ($n < 2) return 2;
        return (int) (2 ** ceil(log(max(2, $n), 2)));
    }

    public static function participants(int $tournamentId): array
    {
        return Database::fetchAll(
            "SELECT u.id, u.display_name, u.avatar_path, tp.seed, tp.eliminated_at, tp.joined_at
               FROM tournament_participants tp
               JOIN users u ON u.id = tp.user_id
              WHERE tp.tournament_id = ?
              ORDER BY tp.seed ASC, tp.joined_at ASC",
            [$tournamentId]
        );
    }

    public static function isParticipant(int $tournamentId, int $userId): bool
    {
        return (bool) Database::fetch(
            'SELECT 1 FROM tournament_participants WHERE tournament_id = ? AND user_id = ? LIMIT 1',
            [$tournamentId, $userId]
        );
    }

    public static function register(int $tournamentId, int $userId): string
    {
        $t = self::find($tournamentId);
        if (!$t || $t['state'] !== 'open') return 'closed';
        if (self::isParticipant($tournamentId, $userId)) return 'already';
        $count = (int) (Database::fetch(
            'SELECT COUNT(*) c FROM tournament_participants WHERE tournament_id = ?', [$tournamentId]
        )['c'] ?? 0);
        if ($count >= (int) $t['max_players']) return 'full';
        Database::query(
            'INSERT INTO tournament_participants (tournament_id, user_id) VALUES (?, ?)',
            [$tournamentId, $userId]
        );
        return 'ok';
    }

    public static function unregister(int $tournamentId, int $userId): void
    {
        Database::query(
            'DELETE FROM tournament_participants WHERE tournament_id = ? AND user_id = ?',
            [$tournamentId, $userId]
        );
    }

    /**
     * Genereer eerste ronde + bouw lege placeholder-matches voor latere
     * rondes. Bye-slots worden meteen geadvanced.
     */
    public static function start(int $tournamentId): void
    {
        $t = self::find($tournamentId);
        if (!$t || $t['state'] !== 'open') return;
        $players = self::participants($tournamentId);
        if (count($players) < 2) return;

        $size = (int) $t['max_players'];
        // Voeg null-byes toe tot bracketgrootte
        $ids = array_map(fn ($p) => (int) $p['id'], $players);
        shuffle($ids); // willekeurige seed
        while (count($ids) < $size) $ids[] = null;

        $rounds = (int) round(log($size, 2));
        Database::pdo()->beginTransaction();
        try {
            // Round 1: maak match per paar
            for ($slot = 0; $slot < $size / 2; $slot++) {
                $a = $ids[$slot * 2] ?? null;
                $b = $ids[$slot * 2 + 1] ?? null;
                self::createBracketMatch($tournamentId, (int) $t['game_id'], 1, $slot, $a, $b);
            }
            // Latere rondes: lege placeholders zodat bracket UI altijd compleet is
            for ($r = 2; $r <= $rounds; $r++) {
                $slots = $size / (2 ** $r);
                for ($slot = 0; $slot < $slots; $slot++) {
                    self::createBracketMatch($tournamentId, (int) $t['game_id'], $r, $slot, null, null);
                }
            }
            Database::query(
                'UPDATE tournaments SET state = "running", started_at = NOW() WHERE id = ?',
                [$tournamentId]
            );
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
            throw $e;
        }
        // Eerste ronde: byes meteen advancen
        self::advanceByes($tournamentId);
    }

    private static function createBracketMatch(int $tournamentId, int $gameId, int $round, int $slot, ?int $userA, ?int $userB): int
    {
        $token = bin2hex(random_bytes(8));
        // State: in_progress als beide spelers er zijn, anders waiting (placeholder)
        $state = ($userA && $userB) ? 'in_progress' : 'waiting';
        $matchId = Database::insert(
            'INSERT INTO matches (game_id, label, state, join_token, created_by, tournament_id, bracket_round, bracket_slot)
             VALUES (?, ?, ?, ?, NULL, ?, ?, ?)',
            [$gameId, 'R' . $round . ' #' . ($slot + 1), $state, $token, $tournamentId, $round, $slot]
        );
        if ($userA) Database::query(
            'INSERT INTO match_participants (match_id, user_id) VALUES (?, ?)', [$matchId, $userA]
        );
        if ($userB) Database::query(
            'INSERT INTO match_participants (match_id, user_id) VALUES (?, ?)', [$matchId, $userB]
        );
        return $matchId;
    }

    /**
     * Voor elke bye in ronde 1: advance de aanwezige speler meteen naar
     * ronde 2 (zonder dat ze hoeven te spelen).
     */
    public static function advanceByes(int $tournamentId): void
    {
        $byes = Database::fetchAll(
            "SELECT m.id, m.bracket_round, m.bracket_slot, mp.user_id
               FROM matches m
               JOIN match_participants mp ON mp.match_id = m.id
              WHERE m.tournament_id = ? AND m.state = 'waiting'
                AND (SELECT COUNT(*) FROM match_participants p2 WHERE p2.match_id = m.id) = 1",
            [$tournamentId]
        );
        foreach ($byes as $b) {
            Database::query(
                'UPDATE matches SET state = "completed", ended_at = NOW() WHERE id = ?',
                [$b['id']]
            );
            Database::query(
                "UPDATE match_participants SET result = 'win', points_awarded = 0 WHERE match_id = ?",
                [$b['id']]
            );
            self::advanceWinner($tournamentId, (int) $b['bracket_round'], (int) $b['bracket_slot'], (int) $b['user_id']);
        }
    }

    /**
     * Verplaats winner naar de juiste slot in de volgende ronde.
     */
    public static function advanceWinner(int $tournamentId, int $round, int $slot, int $userId): void
    {
        $next = Database::fetch(
            'SELECT id, state FROM matches
              WHERE tournament_id = ? AND bracket_round = ? AND bracket_slot = ? LIMIT 1',
            [$tournamentId, $round + 1, intdiv($slot, 2)]
        );
        if (!$next) {
            // Was finale: schrijf winner
            Database::query(
                'UPDATE tournaments SET state = "completed", ended_at = NOW(), winner_id = ? WHERE id = ?',
                [$userId, $tournamentId]
            );
            return;
        }
        Database::query(
            'INSERT INTO match_participants (match_id, user_id) VALUES (?, ?)',
            [(int) $next['id'], $userId]
        );
        // Beide aanwezig? Activeer de match.
        $cnt = (int) (Database::fetch(
            'SELECT COUNT(*) c FROM match_participants WHERE match_id = ?', [(int) $next['id']]
        )['c'] ?? 0);
        if ($cnt >= 2) {
            Database::query(
                'UPDATE matches SET state = "in_progress" WHERE id = ?', [(int) $next['id']]
            );
        }
    }

    /**
     * Bracket-data voor view: rondes met match-info.
     */
    public static function bracket(int $tournamentId): array
    {
        $rows = Database::fetchAll(
            'SELECT m.id, m.bracket_round, m.bracket_slot, m.state, m.label,
                    GROUP_CONCAT(CONCAT_WS(":", mp.user_id, u.display_name, IFNULL(mp.result,""))
                                 ORDER BY mp.id ASC SEPARATOR "|") AS players
               FROM matches m
          LEFT JOIN match_participants mp ON mp.match_id = m.id
          LEFT JOIN users u ON u.id = mp.user_id
              WHERE m.tournament_id = ?
              GROUP BY m.id, m.bracket_round, m.bracket_slot, m.state, m.label
              ORDER BY m.bracket_round ASC, m.bracket_slot ASC',
            [$tournamentId]
        );
        $rounds = [];
        foreach ($rows as $r) {
            $players = [];
            foreach (array_filter(explode('|', (string) $r['players'])) as $part) {
                $bits = explode(':', $part, 3);
                $players[] = [
                    'user_id'     => (int) ($bits[0] ?? 0),
                    'display_name'=> (string) ($bits[1] ?? '–'),
                    'result'      => $bits[2] ?? null,
                ];
            }
            $rounds[(int) $r['bracket_round']][] = [
                'id'    => (int) $r['id'],
                'slot'  => (int) $r['bracket_slot'],
                'state' => $r['state'],
                'label' => $r['label'],
                'players' => $players,
            ];
        }
        return $rounds;
    }
}
