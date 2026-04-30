-- "Speel je nog door?" — wanneer iemand een bezet apparaat scant na N
-- minuten kunnen ze de huidige spelers vragen of ze de tafel vrijgeven.
ALTER TABLE matches
    ADD COLUMN takeover_requested_by  INT UNSIGNED NULL AFTER pending_payload,
    ADD COLUMN takeover_requested_at  DATETIME      NULL AFTER takeover_requested_by,
    ADD COLUMN takeover_status        ENUM('pending','still_playing','released') NULL AFTER takeover_requested_at,
    ADD COLUMN takeover_responded_at  DATETIME      NULL AFTER takeover_status,
    ADD COLUMN takeover_response_by   INT UNSIGNED NULL AFTER takeover_responded_at,
    ADD CONSTRAINT fk_match_takeover_req
        FOREIGN KEY (takeover_requested_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_takeover_resp
        FOREIGN KEY (takeover_response_by) REFERENCES users(id) ON DELETE SET NULL;
