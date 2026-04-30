-- 6-digit numeric code for joining a team
ALTER TABLE teams ADD COLUMN join_code CHAR(6) NULL AFTER slug;
ALTER TABLE teams ADD UNIQUE KEY uniq_teams_join_code (join_code);
