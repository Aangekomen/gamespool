-- Side label per participant for team-vs-team games (tafelvoetbal etc.)
ALTER TABLE match_participants ADD COLUMN match_side ENUM('A','B') NULL AFTER team_id;
