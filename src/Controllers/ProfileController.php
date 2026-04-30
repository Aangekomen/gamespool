<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

use GamesPool\Core\Auth;
use GamesPool\Core\Config;
use GamesPool\Core\Database;
use GamesPool\Core\ImageUpload;
use GamesPool\Core\Session;
use GamesPool\Core\Validator;
use GamesPool\Models\Achievements;
use GamesPool\Models\Company;
use GamesPool\Models\GameMatch;
use GamesPool\Models\Leaderboard;
use GamesPool\Models\Team;

class ProfileController
{
    public function index(): string
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $user   = $this->loadUser($userId);

        return view('profile/index', [
            'user'          => $user,
            'stats'         => $this->stats($userId),
            'teams'         => Team::forUser($userId),
            'recentMatches' => GameMatch::recent(5, $userId),
            'streak'        => Achievements::streak($userId),
            'badges'        => Achievements::badges($userId),
            'nemesis'       => Achievements::nemesis($userId),
            'favOpponent'   => Achievements::favoriteOpponent($userId),
        ]);
    }

    public function settings(): string
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        return view('profile/settings', [
            'user'   => $this->loadUser($userId),
            'errors' => Session::pull('_errors', []),
        ]);
    }

    private function loadUser(int $userId): ?array
    {
        return Database::fetch(
            'SELECT u.*, c.name AS company_name
               FROM users u
          LEFT JOIN companies c ON c.id = u.company_id
              WHERE u.id = ?',
            [$userId]
        );
    }

    public function updateInfo(): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $data = [
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'last_name'  => trim((string) ($_POST['last_name'] ?? '')),
            'email'      => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'company'    => trim((string) ($_POST['company'] ?? '')),
        ];

        $v = (new Validator($data))
            ->required('first_name')->min('first_name', 2)->max('first_name', 80)
            ->required('last_name')->min('last_name', 2)->max('last_name', 80)
            ->required('email')->email('email')->max('email', 190)
            ->max('company', 150);

        $errors = $v->errors();
        $existing = Database::fetch('SELECT id FROM users WHERE email = ? AND id <> ?', [$data['email'], $userId]);
        if ($existing) {
            $errors['email'][] = 'Dit e-mailadres is al in gebruik.';
        }

        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            redirect('/profile/settings');
        }

        $companyId = null;
        if ($data['company'] !== '') {
            $company = Company::findOrCreate($data['company']);
            $companyId = $company ? (int) $company['id'] : null;
        }

        Database::query(
            'UPDATE users SET first_name = ?, last_name = ?, email = ?, company_id = ?, display_name = ? WHERE id = ?',
            [
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $companyId,
                trim($data['first_name'] . ' ' . $data['last_name']),
                $userId,
            ]
        );
        Session::flash('_flash.success', 'Profiel bijgewerkt.');
        redirect('/profile/settings');
    }

    public function uploadAvatar(): void
    {
        Auth::requireLogin();
        $userId = (int) Auth::id();
        $user   = Auth::user();

        if (empty($_FILES['avatar']['name'])) {
            Session::flash('_flash.error', 'Geen bestand geselecteerd.');
            redirect('/profile/settings');
        }

        $dir = BASE_PATH . '/public/uploads/' . trim((string) Config::get('uploads.avatars_dir', 'avatars'), '/');
        $maxBytes = (int) Config::get('uploads.max_bytes', 4 * 1024 * 1024);

        try {
            $filename = ImageUpload::storeSquare($_FILES['avatar'], $dir, 256, $maxBytes);
        } catch (\Throwable $e) {
            Session::flash('_flash.error', $e->getMessage());
            redirect('/profile/settings');
        }

        ImageUpload::delete($dir, $user['avatar_path'] ?? null);
        Database::query('UPDATE users SET avatar_path = ? WHERE id = ?', [$filename, $userId]);
        Session::flash('_flash.success', 'Foto bijgewerkt.');
        redirect('/profile/settings');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        $userId  = (int) Auth::id();
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirmation'] ?? '');

        $errors = [];
        $row = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$userId]);
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors['current_password'][] = 'Huidig wachtwoord klopt niet.';
        }
        if (mb_strlen($new) < 8) {
            $errors['new_password'][] = 'Minimaal 8 tekens.';
        }
        if ($new !== $confirm) {
            $errors['new_password'][] = 'Bevestiging komt niet overeen.';
        }
        if (!empty($errors)) {
            Session::flash('_errors', $errors);
            redirect('/profile/settings');
        }

        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($new, PASSWORD_DEFAULT), $userId]
        );
        Session::flash('_flash.success', 'Wachtwoord gewijzigd.');
        redirect('/profile/settings');
    }

    public function deleteAccount(): void
    {
        Auth::requireLogin();
        $userId  = (int) Auth::id();
        $confirm = (string) ($_POST['confirm'] ?? '');
        if ($confirm !== 'VERWIJDER') {
            Session::flash('_flash.error', 'Type "VERWIJDER" om te bevestigen.');
            redirect('/profile/settings');
        }

        $user = Auth::user();
        $dir = BASE_PATH . '/public/uploads/' . trim((string) Config::get('uploads.avatars_dir', 'avatars'), '/');
        ImageUpload::delete($dir, $user['avatar_path'] ?? null);

        Database::query('DELETE FROM users WHERE id = ?', [$userId]);
        Auth::logout();
        redirect('/');
    }

    private function stats(int $userId): array
    {
        $row = Database::fetch(
            "SELECT COUNT(p.id) AS matches_played,
                    COALESCE(SUM(p.result = 'win'), 0)    AS wins,
                    COALESCE(SUM(p.result = 'loss'), 0)   AS losses,
                    COALESCE(SUM(p.result = 'draw'), 0)   AS draws,
                    COALESCE(SUM(p.points_awarded), 0)    AS total_points
               FROM match_participants p
               JOIN matches m ON m.id = p.match_id
              WHERE p.user_id = ? AND m.state = 'completed'",
            [$userId]
        ) ?? [];

        $matches = (int) ($row['matches_played'] ?? 0);
        $wins    = (int) ($row['wins'] ?? 0);

        $rank = null;
        $totalRanked = 0;
        foreach (Leaderboard::players('lifetime') as $i => $p) {
            $totalRanked++;
            if ($rank === null && (int) $p['id'] === $userId) $rank = $i + 1;
        }

        return [
            'matches'      => $matches,
            'wins'         => $wins,
            'losses'       => (int) ($row['losses'] ?? 0),
            'draws'        => (int) ($row['draws'] ?? 0),
            'win_rate'     => $matches > 0 ? (int) round(($wins / $matches) * 100) : 0,
            'total_points' => (int) ($row['total_points'] ?? 0),
            'rank'         => $rank,
            'total_ranked' => $totalRanked,
        ];
    }
}
