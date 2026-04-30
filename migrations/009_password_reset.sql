-- Password reset token (self-service or admin-triggered)
ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL AFTER verification_token;
ALTER TABLE users ADD COLUMN password_reset_expires_at DATETIME NULL AFTER password_reset_token;
ALTER TABLE users ADD UNIQUE KEY uniq_users_password_reset_token (password_reset_token);
