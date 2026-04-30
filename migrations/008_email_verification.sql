-- Email verification
ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email;
ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) NULL AFTER email_verified_at;
ALTER TABLE users ADD UNIQUE KEY uniq_users_verification_token (verification_token);

-- Existing users are treated as verified (don't lock them out)
UPDATE users SET email_verified_at = NOW() WHERE email_verified_at IS NULL;
