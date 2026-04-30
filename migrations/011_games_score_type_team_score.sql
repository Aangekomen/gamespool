-- Add team_score to the games.score_type enum (was win_loss / points_per_match / elo)
ALTER TABLE games
    MODIFY COLUMN score_type ENUM('win_loss','points_per_match','elo','team_score')
    NOT NULL DEFAULT 'win_loss';
