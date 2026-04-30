-- Per-team tag (Discord-style) editable by captain
ALTER TABLE team_members ADD COLUMN tag VARCHAR(20) NULL AFTER role;
