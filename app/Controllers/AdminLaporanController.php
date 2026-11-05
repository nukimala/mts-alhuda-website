<?php

/**
 * Laporan & Audit Trail — panel admin MTs Al-Huda.
 *
 * Menampilkan:
 *  - Kartu ringkasan eksekutif (siswa, guru, berita, galeri, presensi hari ini, akreditasi)
 *  - Tabel statistik per modul
 *  - Audit trail dari tabel activity_logs (paginated)
 *  - Tombol cetak (window.print())
 */

class AdminLaporanController
{
    private function guard(): array
    {
        Auth::start();
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            exit;
        }

        $user = Auth::user();
        if ($user !== null && (int) ($user['must_change_password'] ?? 0) === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            exit;
        }

        return $user;
    }

    // -----------------------------------------------------------------------
    // Halaman utama laporan
    // -----------------------------------------------------------------------

    public function index(): void
    {
        $user = $this->guard();
        $pdo  = Database::connection();

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $limit   = 25;
        $offset  = ($page - 1) * $limit;

        $filterAksi  = trim((string) ($_GET['aksi']  ?? ''));
        $filterUser  = trim((string) ($_GET['user']  ?? ''));
        $filterTgl   = trim((string) ($_GET['tgl']   ?? ''));

        // ── Ringkasan ──────────────────────────────────────────────────────
        $summary = $this->buildSummary($pdo);

        // ── Audit log ──────────────────────────────────────────────────────
        [$logs, $total] = $this->fetchLogs($pdo, $limit, $offset, $filterAksi, $filterUser, $filterTgl);
        $totalPages = (int) ceil($total / $limit);

        // ── Daftar aksi unik untuk filter ─────────────────────────────────
        $aksiList = $this->distinctActions($pdo);

        $data = [
            'title'       => 'Laporan & Audit Trail | Panel Admin MTs Al-Huda',
            'user'        => $user,
            'role'        => Auth::displayRole(),
            'active_menu' => 'laporan',
            'summary'     => $summary,
            'logs'        => $logs,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => $totalPages,
            'limit'       => $limit,
            'filter_aksi' => $filterAksi,
            'filter_user' => $filterUser,
            'filter_tgl'  => $filterTgl,
            'aksi_list'   => $aksiList,
            'flash'       => $_SESSION['_flash_success'] ?? null,
            'error'       => $_SESSION['_flash_error']   ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/laporan/index.php';
    }

    // -----------------------------------------------------------------------
    // Tandai log sebagai sudah dibaca (soft flag)
    // -----------------------------------------------------------------------

    public function read(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/laporan'));
            return;
        }

        try {
            $pdo = Database::connection();
            $pdo->prepare('UPDATE log_aktivitas SET is_read = 1 WHERE id = ?')
                ->execute([$id]);
        } catch (\PDOException $e) {
            // kolom is_read mungkin belum ada — abaikan
        }

        header('Location: ' . base_url('admin/laporan'));
    }

    // -----------------------------------------------------------------------
    // Arsipkan log (hapus lunak via kolom archived_at)
    // -----------------------------------------------------------------------

    public function archive(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/laporan'));
            return;
        }

        try {
            $pdo = Database::connection();
            $pdo->prepare('UPDATE log_aktivitas SET archived_at = NOW() WHERE id = ?')
                ->execute([$id]);
            $_SESSION['_flash_success'] = 'Log berhasil diarsipkan.';
        } catch (\PDOException $e) {
            $_SESSION['_flash_error'] = 'Gagal mengarsipkan log.';
        }

        header('Location: ' . base_url('admin/laporan'));
    }

    // -----------------------------------------------------------------------
    // Hapus log permanen
    // -----------------------------------------------------------------------

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/laporan'));
            return;
        }

        try {
            $pdo = Database::connection();
            $pdo->prepare('DELETE FROM log_aktivitas WHERE id = ?')
                ->execute([$id]);
            $_SESSION['_flash_success'] = 'Log berhasil dihapus.';
        } catch (\PDOException $e) {
            $_SESSION['_flash_error'] = 'Gagal menghapus log.';
        }

        header('Location: ' . base_url('admin/laporan'));
    }

    // =======================================================================
    // Private helpers
    // =======================================================================

    /**
     * @return array<string,mixed>
     */
    private function buildSummary(\PDO $pdo): array
    {
        $q = fn(string $sql): int => $this->scalarInt($pdo, $sql);

        // Statistik akademik
        $totalSiswa  = $q("SELECT COUNT(*) FROM pengguna WHERE role = 'siswa' AND deleted_at IS NULL");
        $totalGuru   = $q("SELECT COUNT(*) FROM pengguna WHERE role = 'guru'  AND deleted_at IS NULL");
        $totalAdmin  = $q("SELECT COUNT(*) FROM pengguna WHERE role IN ('admin','operator') AND deleted_at IS NULL");

        // Konten
        $beritaTayang    = $q("SELECT COUNT(*) FROM berita WHERE status = 'published'");
        $beritaDraft     = $q("SELECT COUNT(*) FROM berita WHERE status = 'draft'");
        $totalGaleri     = $q("SELECT COUNT(*) FROM galeri");
        $totalFasilitas  = $q("SELECT COUNT(*) FROM fasilitas");

        // Layanan
        $ppdbAktif       = $q("SELECT COUNT(*) FROM dokumen_ppdb WHERE is_active = 1");
        $ppdbTotal       = $q("SELECT COUNT(*) FROM dokumen_ppdb");

        // Akademik
        $jadwalTotal     = $q("SELECT COUNT(*) FROM jadwal");
        $presensiHariIni = $q("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = CURDATE()");
        $presensiHadir   = $q("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = CURDATE() AND status = 'hadir'");

        // Akreditasi
        $buktiTotal      = $q("SELECT COUNT(*) FROM bukti");
        $butirTotal      = $q("SELECT COUNT(*) FROM butir_akreditasi");
        $butirTerpenuhi  = $q("SELECT COUNT(*) FROM butir_akreditasi WHERE status = 'terpenuhi'");
        $dkaTotal        = $q("SELECT COUNT(*) FROM entri_dka WHERE LENGTH(TRIM(content)) > 0");

        $akreditasiPct   = $butirTotal > 0
            ? round($butirTerpenuhi / $butirTotal * 100, 1)
            : 0.0;

        // Aktivitas
        $logTotal  = $q("SELECT COUNT(*) FROM log_aktivitas");
        $logBulanIni = $q("SELECT COUNT(*) FROM log_aktivitas WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");

        return compact(
            'totalSiswa', 'totalGuru', 'totalAdmin',
            'beritaTayang', 'beritaDraft',
            'totalGaleri', 'totalFasilitas',
            'ppdbAktif', 'ppdbTotal',
            'jadwalTotal',
            'presensiHariIni', 'presensiHadir',
            'buktiTotal', 'butirTotal', 'butirTerpenuhi', 'dkaTotal', 'akreditasiPct',
            'logTotal', 'logBulanIni'
        );
    }

    /**
     * @return array{list<array<string,mixed>>, int}
     */
    private function fetchLogs(
        \PDO $pdo,
        int $limit,
        int $offset,
        string $filterAksi,
        string $filterUser,
        string $filterTgl
    ): array {
        try {
            $where  = [];
            $params = [];

            if ($filterAksi !== '') {
                $where[]  = 'al.action LIKE ?';
                $params[] = '%' . $filterAksi . '%';
            }
            if ($filterUser !== '') {
                $where[]  = '(u.name LIKE ? OR u.email LIKE ?)';
                $params[] = '%' . $filterUser . '%';
                $params[] = '%' . $filterUser . '%';
            }
            if ($filterTgl !== '') {
                $where[]  = 'DATE(al.created_at) = ?';
                $params[] = $filterTgl;
            }

            $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countSql = "SELECT COUNT(*) FROM log_aktivitas al
                         LEFT JOIN pengguna u ON u.id = al.user_id
                         $whereStr";
            $total = (int) $pdo->prepare($countSql)->execute($params) ? 0 : 0;
            $stmt  = $pdo->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $dataSql = "SELECT al.id, al.action, al.description, al.ip_address,
                               al.created_at,
                               u.name AS user_name, u.email AS user_email, u.role AS user_role
                        FROM log_aktivitas al
                        LEFT JOIN pengguna u ON u.id = al.user_id
                        $whereStr
                        ORDER BY al.created_at DESC
                        LIMIT $limit OFFSET $offset";
            $stmt = $pdo->prepare($dataSql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            return [$logs, $total];
        } catch (\PDOException $e) {
            return [[], 0];
        }
    }

    /**
     * @return list<string>
     */
    private function distinctActions(\PDO $pdo): array
    {
        try {
            return $pdo->query('SELECT DISTINCT action FROM log_aktivitas ORDER BY action')
                ->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    private function scalarInt(\PDO $pdo, string $sql): int
    {
        try {
            return (int) $pdo->query($sql)->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }
}
