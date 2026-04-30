-- Core schema for GamesPool

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email           VARCHAR(190) NOT NULL,
    display_name    VARCHAR(80)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    avatar_path     VARCHAR(255) DEFAULT NULL,
    is_admin        TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS games (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(120) NOT NULL,
    -- score_type: how points get awarded for a match
    --   win_loss        : winner gets +win_points, loser +loss_points
    --   points_per_match: each player records an integer score, that score = points
    --   elo             : Elo rating per player (default 1000)
    score_type      ENUM('win_loss','points_per_match','elo') NOT NULL DEFAULT 'win_loss',
    -- JSON config: e.g. {"win_points":3,"loss_points":0,"draw_points":1,"k_factor":24,"start_rating":1000}
    score_config    JSON NULL,
    icon            VARCHAR(255) DEFAULT NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_games_slug (slug),
    CONSTRAINT fk_games_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(120) NOT NULL,
    logo_path       VARCHAR(255) DEFAULT NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_teams_slug (slug),
    CONSTRAINT fk_teams_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_members (
    team_id         INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    role            ENUM('captain','member') NOT NULL DEFAULT 'member',
    joined_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (team_id, user_id),
    CONSTRAINT fk_tm_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_tm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competitions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150) NOT NULL,
    slug            VARCHAR(160) NOT NULL,
    description     TEXT NULL,
    visibility      ENUM('open','closed') NOT NULL DEFAULT 'open',
    join_token      VARCHAR(64)  NOT NULL,
    game_id         INT UNSIGNED NULL,
    starts_at       DATETIME NULL,
    ends_at         DATETIME NULL,
    owner_id        INT UNSIGNED NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_comp_slug (slug),
    UNIQUE KEY uniq_comp_join (join_token),
    KEY idx_comp_owner (owner_id),
    CONSTRAINT fk_comp_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comp_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competition_members (
    competition_id  INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
    joined_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (competition_id, user_id),
    CONSTRAINT fk_cm_comp FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    game_id         INT UNSIGNED NOT NULL,
    competition_id  INT UNSIGNED NULL,
    -- Free-text label, useful for QR-based "current game" tracking
    label           VARCHAR(150) NULL,
    state           ENUM('in_progress','completed','cancelled') NOT NULL DEFAULT 'in_progress',
    started_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at        DATETIME NULL,
    join_token      VARCHAR(64) NOT NULL,
    created_by      INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_match_token (join_token),
    KEY idx_match_state (state),
    KEY idx_match_comp (competition_id),
    CONSTRAINT fk_match_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_comp FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_participants (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    match_id        INT UNSIGNED NOT NULL,
    -- Either user_id is set (registered participant) or guest_name (gast via QR)
    user_id         INT UNSIGNED NULL,
    guest_name      VARCHAR(80)  NULL,
    team_id         INT UNSIGNED NULL,
    raw_score       INT NULL,        -- e.g. 7 balls potted, 301 points, etc.
    result          ENUM('win','loss','draw') NULL,
    points_awarded  INT NOT NULL DEFAULT 0,  -- after applying score_type rules
    rating_before   INT NULL,
    rating_after    INT NULL,
    PRIMARY KEY (id),
    KEY idx_mp_match (match_id),
    KEY idx_mp_user  (user_id),
    KEY idx_mp_team  (team_id),
    CONSTRAINT fk_mp_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_mp_user  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_mp_team  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Long-lived rating per (user, game) for Elo-type score systems
CREATE TABLE IF NOT EXISTS user_ratings (
    user_id         INT UNSIGNED NOT NULL,
    game_id         INT UNSIGNED NOT NULL,
    rating          INT NOT NULL DEFAULT 1000,
    matches_played  INT NOT NULL DEFAULT 0,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
