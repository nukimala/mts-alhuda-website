<?php
/**
 * Dashboard panel admin.
 *
 * Mengagregasi data dari database: CMS, Akreditasi, dan Ekosistem Mobile App MADRASAHKU.
 */

class AdminController
{
    public function dashboard(): void
    {
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            return;
        }

        // Kata sandi sementara belum diganti — kunci akses ke halaman lain.
        $user = Auth::user();
        if ($user !== null && (int) $user['must_change_password'] === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            return;
        }

        $pdo = Database::connection();

        $data = [
            'title' => 'Beranda Panel Admin | MTs Al-Huda Gondang',
            'user'  => $user,
            'role'  => Auth::displayRole(),
            'stats' => $this->stats($pdo),
            'migrasi' => $this->migrationState($pdo),
            'pengaturan' => $this->settingsPreview($pdo),
            'flash' => $_SESSION['_flash_success'] ?? null,
        ];

        unset($_SESSION['_flash_success']);

        require_once __DIR__ . '/../Views/admin/dashboard.php';
    }

    /**
     * @return array<string,int>
     */
    private function stats(PDO $pdo): array
    {
        return [
            'pengguna'        => $this->count($pdo, 'pengguna', 'deleted_at IS NULL'),
            'guru'            => $this->count($pdo, 'pengguna', "role = 'guru' AND deleted_at IS NULL"),
            'siswa'           => $this->count($pdo, 'pengguna', "role = 'siswa' AND deleted_at IS NULL"),
            'berita'          => $this->count($pdo, 'berita', "status = 'published'"),
            'presensi'        => $this->count($pdo, 'presensi_guru', "attendance_date = CURDATE()"),
            'presensi_mobile' => $this->count($pdo, 'presensi_guru', "attendance_date = CURDATE() AND method = 'self'"),
            'bukti'           => $this->count($pdo, 'bukti', null),
            'jadwal'          => $this->count($pdo, 'jadwal', "is_active = 1"),
            'tugas'           => $this->count($pdo, 'tugas', "is_published = 1"),
            'tokens'          => $this->count($pdo, 'token_api', "revoked_at IS NULL"),
        ];
    }

    private function count(PDO $pdo, string $table, ?string $where): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM `' . $table . '`';
            if ($where !== null) {
                $sql .= ' WHERE ' . $where;
            }

            return (int) $pdo->query($sql)->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function migrationState(PDO $pdo): array
    {
        try {
            return $pdo->query(
                'SELECT version, name, batch, executed_at
                 FROM schema_migrations
                 ORDER BY version'
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function settingsPreview(PDO $pdo): array
    {
        try {
            return $pdo->query(
                "SELECT setting_key, setting_value, group_name
                 FROM pengaturan
                 WHERE is_public = 1
                 ORDER BY group_name, setting_key
                 LIMIT 12"
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
