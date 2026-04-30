<?php
declare(strict_types=1);

namespace GamesPool\Models;

use GamesPool\Core\Database;
use GamesPool\Core\Slug;

class Game
{
    public const SCORE_TYPES = ['win_loss', 'points_per_match', 'elo', 'team_score'];

    public static function defaultConfig(string $scoreType): array
    {
        // Standaard 1 punt per winst — kleinere getallen lezen rustiger op
        // het scoreboard en passen beter bij barspellen waar mensen in series
        // spelen (totaal loopt sneller naar 10/20 dan naar 30/60).
        return match ($scoreType) {
            'win_loss'         => ['win_points' => 1, 'loss_points' => 0, 'draw_points' => 0],
            'points_per_match' => [],
            'elo'              => ['start_rating' => 1000, 'k_factor' => 24],
            'team_score'       => ['win_points' => 1, 'loss_points' => 0, 'draw_points' => 0],
            default            => [],
        };
    }

    public static function all(): array
    {
        return Database::fetchAll('SELECT * FROM games ORDER BY name ASC');
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM games WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM games WHERE slug = ?', [$slug]);
    }

    public static function slugTaken(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            return (bool) Database::fetch('SELECT id FROM games WHERE slug = ?', [$slug]);
        }
        return (bool) Database::fetch('SELECT id FROM games WHERE slug = ? AND id <> ?', [$slug, $exceptId]);
    }

    public static function create(array $data, int $userId): int
    {
        $slug = Slug::unique($data['name'], fn($s) => self::slugTaken($s));
        return Database::insert(
            'INSERT INTO games (name, slug, score_type, score_config, rules, created_by) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['name'],
                $slug,
                $data['score_type'],
                json_encode(self::sanitizeConfig($data['score_type'], $data['score_config'] ?? []), JSON_THROW_ON_ERROR),
                $data['rules'] ?? null,
                $userId,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        $current = self::find($id);
        if (!$current) return;

        $slug = $current['slug'];
        if (($data['name'] ?? '') !== '' && $data['name'] !== $current['name']) {
            $slug = Slug::unique($data['name'], fn($s) => self::slugTaken($s, $id));
        }
        Database::query(
            'UPDATE games SET name = ?, slug = ?, score_type = ?, score_config = ?, rules = ? WHERE id = ?',
            [
                $data['name'] ?? $current['name'],
                $slug,
                $data['score_type'] ?? $current['score_type'],
                json_encode(self::sanitizeConfig($data['score_type'] ?? $current['score_type'], $data['score_config'] ?? []), JSON_THROW_ON_ERROR),
                array_key_exists('rules', $data) ? $data['rules'] : ($current['rules'] ?? null),
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM games WHERE id = ?', [$id]);
    }

    /**
     * Coerce posted config values to expected ints, dropping unknown keys.
     */
    public static function sanitizeConfig(string $scoreType, array $raw): array
    {
        $defaults = self::defaultConfig($scoreType);
        $clean = [];
        foreach ($defaults as $key => $default) {
            $value = $raw[$key] ?? $default;
            $clean[$key] = is_numeric($value) ? (int) $value : $default;
        }
        return $clean;
    }

    public static function decodeConfig(array $game): array
    {
        if (empty($game['score_config'])) {
            return self::defaultConfig($game['score_type']);
        }
        try {
            $decoded = json_decode((string) $game['score_config'], true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : self::defaultConfig($game['score_type']);
        } catch (\JsonException) {
            return self::defaultConfig($game['score_type']);
        }
    }

    public static function scoreTypeLabel(string $type): string
    {
        return match ($type) {
            'win_loss'         => 'Winnaar / verliezer (vaste punten)',
            'points_per_match' => 'Score per match (vrije punten)',
            'elo'              => 'Elo-rating',
            'team_score'       => 'Team vs Team (eindstand per team)',
            default            => $type,
        };
    }
}
