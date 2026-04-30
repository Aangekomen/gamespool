-- Companies (deduped by normalized_name)
CREATE TABLE IF NOT EXISTS companies (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150) NOT NULL,                -- as entered, original casing
    normalized_name VARCHAR(150) NOT NULL,                -- lowercase + collapsed whitespace
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_companies_normalized (normalized_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users: split name + optional company
ALTER TABLE users ADD COLUMN first_name VARCHAR(80) NULL AFTER email;
ALTER TABLE users ADD COLUMN last_name  VARCHAR(80) NULL AFTER first_name;
ALTER TABLE users ADD COLUMN company_id INT UNSIGNED NULL AFTER last_name;
ALTER TABLE users ADD CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL;

-- Backfill: copy display_name into first_name for existing users so they aren't blank
UPDATE users SET first_name = display_name WHERE first_name IS NULL;
