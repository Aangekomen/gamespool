-- Round-robin poules: iedereen speelt tegen iedereen.
-- Klassieke stand: punten + winst/gelijk/verlies + doelsaldo.
CREATE TABLE IF NOT EXISTS poules (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(160) NOT NULL,
    game_id      INT UNSIGNED NOT NULL,
    state        ENUM('open','running','completed','cancelled') NOT NULL DEFAULT 'open',
    starts_at    DATETIME NULL,
    owner_id     INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at   DATETIME NULL,
    ended_at     DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_poule_slug (slug),
    CONSTRAINT fk_poule_game  FOREIGN KEY (game_id)  REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_poule_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poule_participants (
    poule_id  INT UNSIGNED NOT NULL,
    user_id   INT UNSIGNED NOT NULL,
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (poule_id, user_id),
    CONSTRAINT fk_pp_poule FOREIGN KEY (poule_id) REFERENCES poules(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE matches
    ADD COLUMN poule_id INT UNSIGNED NULL AFTER bracket_slot,
    ADD INDEX idx_matches_poule (poule_id),
    ADD CONSTRAINT fk_match_poule FOREIGN KEY (poule_id) REFERENCES poules(id) ON DELETE SET NULL;
