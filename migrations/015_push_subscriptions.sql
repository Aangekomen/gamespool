-- Web Push subscriptions: één rij per browser/device per gebruiker
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    endpoint        VARCHAR(500) NOT NULL,
    p256dh_key      VARCHAR(255) NOT NULL,
    auth_secret     VARCHAR(255) NOT NULL,
    user_agent      VARCHAR(255) NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_endpoint (endpoint(190)),
    KEY idx_push_user (user_id),
    CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bijhouden welke push-bericht een gebruiker al gehad heeft (voorkom dubbele
-- inactiviteits-pings binnen X dagen)
CREATE TABLE IF NOT EXISTS push_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    kind        VARCHAR(40) NOT NULL,
    sent_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pushlog_user_kind (user_id, kind, sent_at),
    CONSTRAINT fk_pushlog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
