-- Devices: physical game stations with a printed QR code
CREATE TABLE IF NOT EXISTS devices (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    code        VARCHAR(20)  NOT NULL,        -- short alphanumeric in QR URL
    game_id     INT UNSIGNED NULL,            -- which game this device hosts
    location    VARCHAR(120) NULL,            -- optional, e.g. "Achterzaal"
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_devices_code (code),
    CONSTRAINT fk_devices_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Allow matches to reference a device
ALTER TABLE matches ADD COLUMN device_id INT UNSIGNED NULL AFTER competition_id;
ALTER TABLE matches ADD CONSTRAINT fk_match_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL;

-- Add 'waiting' state for matches awaiting an opponent to accept
ALTER TABLE matches MODIFY COLUMN state ENUM('waiting','in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress';
