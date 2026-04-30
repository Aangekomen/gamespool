-- Single-elimination toernooien (tot 16 spelers, 4 rondes)
CREATE TABLE IF NOT EXISTS tournaments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(160) NOT NULL,
    game_id      INT UNSIGNED NOT NULL,
    format       ENUM('single_elim') NOT NULL DEFAULT 'single_elim',
    max_players  TINYINT NOT NULL DEFAULT 8,
    state        ENUM('open','running','completed','cancelled') NOT NULL DEFAULT 'open',
    owner_id     INT UNSIGNED NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at   DATETIME NULL,
    ended_at     DATETIME NULL,
    winner_id    INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_tour_slug (slug),
    CONSTRAINT fk_tour_game   FOREIGN KEY (game_id)   REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_tour_owner  FOREIGN KEY (owner_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_tour_winner FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tournament_participants (
    tournament_id INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    seed          TINYINT NULL,
    eliminated_at DATETIME NULL,
    joined_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tournament_id, user_id),
    CONSTRAINT fk_tp_tour FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    CONSTRAINT fk_tp_user FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches kunnen onderdeel zijn van een toernooi-bracket
ALTER TABLE matches
    ADD COLUMN tournament_id INT UNSIGNED NULL AFTER series_target,
    ADD COLUMN bracket_round TINYINT NULL AFTER tournament_id,
    ADD COLUMN bracket_slot  TINYINT NULL AFTER bracket_round,
    ADD INDEX idx_matches_tournament (tournament_id, bracket_round, bracket_slot),
    ADD CONSTRAINT fk_match_tour FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE SET NULL;
