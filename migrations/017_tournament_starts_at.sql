-- Geplande starttijd voor een toernooi (los van started_at, dat is wanneer
-- de admin daadwerkelijk op "Start" tikt en de bracket gegenereerd wordt).
ALTER TABLE tournaments
    ADD COLUMN starts_at DATETIME NULL AFTER state;
