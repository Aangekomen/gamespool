-- Confirmation flow: beide spelers moeten uitslag bevestigen
ALTER TABLE matches
    MODIFY COLUMN state ENUM('waiting','in_progress','pending_confirmation','completed','cancelled')
    NOT NULL DEFAULT 'in_progress';

ALTER TABLE matches
    ADD COLUMN pending_recorded_by  INT UNSIGNED NULL AFTER ended_at,
    ADD COLUMN pending_recorded_at  DATETIME      NULL AFTER pending_recorded_by,
    ADD COLUMN pending_payload      JSON          NULL AFTER pending_recorded_at,
    ADD CONSTRAINT fk_match_pending_user
        FOREIGN KEY (pending_recorded_by) REFERENCES users(id) ON DELETE SET NULL;

-- Best-of-N series: matches kunnen tot een serie behoren
ALTER TABLE matches
    ADD COLUMN series_id     CHAR(16) NULL AFTER pending_payload,
    ADD COLUMN series_target TINYINT  NULL AFTER series_id,
    ADD INDEX idx_matches_series (series_id);
