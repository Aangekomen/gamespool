-- Pending approval for team join requests
ALTER TABLE team_members ADD COLUMN status ENUM('pending','approved') NOT NULL DEFAULT 'approved' AFTER role;
ALTER TABLE team_members ADD COLUMN requested_at DATETIME NULL AFTER joined_at;

-- Existing rows are treated as approved (default fits) so nothing breaks for current members.
