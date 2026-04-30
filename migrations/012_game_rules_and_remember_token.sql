-- Per-game rules booklet + persistent login ("remember me") tokens
ALTER TABLE games
    ADD COLUMN rules TEXT NULL AFTER score_config;

ALTER TABLE users
    ADD COLUMN remember_token CHAR(64) NULL AFTER password_hash,
    ADD COLUMN remember_expires_at DATETIME NULL AFTER remember_token,
    ADD UNIQUE KEY uniq_users_remember_token (remember_token);
