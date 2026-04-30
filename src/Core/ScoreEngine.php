<?php
declare(strict_types=1);

namespace GamesPool\Core;

use GamesPool\Models\Game;

/**
 * Applies a game's score_type rules to a set of participants.
 *
 * Input participant shape (per row):
 *   ['user_id'?, 'guest_name'?, 'team_id'?, 'raw_score'?, 'result'?]
 *
 * Output adds: points_awarded, rating_before?, rating_after?
 */
class ScoreEngine
{
    public static function compute(array $game, array $participants): array
    {
        $type = $game['score_type'] ?? 'win_loss';
        $cfg  = Game::decodeConfig($game);

        return match ($type) {
            'points_per_match' => self::points($participants),
            'elo'              => self::elo($game, $cfg, $participants),
            'team_score'       => self::teamScore($cfg, $participants),
            default            => self::winLoss($cfg, $participants),
        };
    }

    /**
     * Team A vs Team B. Each participant has 'match_side' ('A'/'B') and
     * 'raw_score' (team's final score, same for everyone on that side).
     * Higher side score → its players get win_points, others get loss_points;
     * tie → everyone gets draw_points.
     */
    private static function teamScore(array $cfg, array $participants): array
    {
        $win  = (int) ($cfg['win_points']  ?? 3);
        $draw = (int) ($cfg['draw_points'] ?? 1);
        $loss = (int) ($cfg['loss_points'] ?? 0);

        $sideScores = ['A' => null, 'B' => null];
        foreach ($participants as $p) {
            $side = $p['match_side'] ?? null;
            if (($side === 'A' || $side === 'B') && isset($p['raw_score'])) {
                $sideScores[$side] = (int) $p['raw_score'];
            }
        }
        $winner = null;
        if ($sideScores['A'] !== null && $sideScores['B'] !== null) {
            if ($sideScores['A'] > $sideScores['B']) {
                $winner = 'A';
            } elseif ($sideScores['B'] > $sideScores['A']) {
                $winner = 'B';
            }
        }

        foreach ($participants as &$p) {
            $side = $p['match_side'] ?? null;
            if ($winner === null) {
                $p['result'] = 'draw';
                $p['points_awarded'] = $draw;
            } elseif ($side === $winner) {
                $p['result'] = 'win';
                $p['points_awarded'] = $win;
            } else {
                $p['result'] = 'loss';
                $p['points_awarded'] = $loss;
            }
        }
        return $participants;
    }

    private static function winLoss(array $cfg, array $participants): array
    {
        $win  = (int) ($cfg['win_points']  ?? 3);
        $draw = (int) ($cfg['draw_points'] ?? 1);
        $loss = (int) ($cfg['loss_points'] ?? 0);

        foreach ($participants as &$p) {
            $r = $p['result'] ?? null;
            $p['points_awarded'] = match ($r) {
                'win'  => $win,
                'draw' => $draw,
                'loss' => $loss,
                default => 0,
            };
        }
        return $participants;
    }

    private static function points(array $participants): array
    {
        $best = null;
        foreach ($participants as $p) {
            $score = (int) ($p['raw_score'] ?? 0);
            if ($best === null || $score > $best) $best = $score;
        }
        foreach ($participants as &$p) {
            $score = (int) ($p['raw_score'] ?? 0);
            $p['points_awarded'] = $score;
            // Derive result for context (winner = highest score; ties => draw)
            if ($score === $best) {
                $count = 0;
                foreach ($participants as $q) {
                    if ((int) ($q['raw_score'] ?? 0) === $best) $count++;
                }
                $p['result'] = $count > 1 ? 'draw' : 'win';
            } else {
                $p['result'] = 'loss';
            }
        }
        return $participants;
    }

    /**
     * Standard Elo. Limited to 1v1 for now (two participants with user_id).
     * Falls back to win_loss for invalid setups.
     */
    private static function elo(array $game, array $cfg, array $participants): array
    {
        $start = (int) ($cfg['start_rating'] ?? 1000);
        $k     = (int) ($cfg['k_factor']     ?? 24);

        $registered = array_values(array_filter(
            $participants,
            fn($p) => !empty($p['user_id']) && in_array($p['result'] ?? null, ['win','loss','draw'], true)
        ));

        if (count($registered) !== 2 || count($participants) !== 2) {
            // Not a clean 1v1 — fall back so something useful is recorded
            return self::winLoss(['win_points' => 1, 'draw_points' => 0, 'loss_points' => 0], $participants);
        }

        [$a, $b] = $registered;
        $ratingA = self::loadRating((int) $a['user_id'], (int) $game['id'], $start);
        $ratingB = self::loadRating((int) $b['user_id'], (int) $game['id'], $start);

        $expectedA = 1 / (1 + 10 ** (($ratingB - $ratingA) / 400));
        $scoreA = match ($a['result']) { 'win' => 1.0, 'draw' => 0.5, default => 0.0 };
        $scoreB = 1.0 - $scoreA;

        $newA = (int) round($ratingA + $k * ($scoreA - $expectedA));
        $newB = (int) round($ratingB + $k * ($scoreB - (1 - $expectedA)));

        foreach ($participants as &$p) {
            if ((int) ($p['user_id'] ?? 0) === (int) $a['user_id']) {
                $p['rating_before']  = $ratingA;
                $p['rating_after']   = $newA;
                $p['points_awarded'] = $newA - $ratingA;
            } elseif ((int) ($p['user_id'] ?? 0) === (int) $b['user_id']) {
                $p['rating_before']  = $ratingB;
                $p['rating_after']   = $newB;
                $p['points_awarded'] = $newB - $ratingB;
            }
        }
        return $participants;
    }

    private static function loadRating(int $userId, int $gameId, int $default): int
    {
        $row = Database::fetch(
            'SELECT rating FROM user_ratings WHERE user_id = ? AND game_id = ?',
            [$userId, $gameId]
        );
        return $row ? (int) $row['rating'] : $default;
    }

    public static function persistRatings(array $game, array $participants): void
    {
        if (($game['score_type'] ?? '') !== 'elo') return;
        foreach ($participants as $p) {
            if (empty($p['user_id']) || !isset($p['rating_after'])) continue;
            Database::query(
                'INSERT INTO user_ratings (user_id, game_id, rating, matches_played)
                 VALUES (?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE rating = VALUES(rating), matches_played = matches_played + 1',
                [(int) $p['user_id'], (int) $game['id'], (int) $p['rating_after']]
            );
        }
    }
}
