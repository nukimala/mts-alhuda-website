<?php

class AdminPenggunaController
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

    public function index(): void
    {
        $currentUser = $this->guard();
        $pdo = Database::connection();

        $role = trim((string) ($_GET['role'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        $where = ['u.deleted_at IS NULL'];
        $params = [];

        if ($role !== '' && in_array($role, ['admin', 'operator', 'kepala', 'guru', 'siswa', 'akreditasi'], true)) {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }

        if ($status === 'active') {
            $where[] = 'u.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'u.is_active = 0';
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR t.nip LIKE ? OR s.nis LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT u.*, 
                       t.id AS teacher_id, t.nip, t.subject_specialty,
                       s.id AS student_id, s.nis, s.nisn, s.class_id,
                       c.name AS class_name
                FROM pengguna u
                LEFT JOIN guru t ON t.user_id = u.id
                LEFT JOIN siswa s ON s.user_id = u.id
                LEFT JOIN kelas c ON c.id = s.class_id
                WHERE {$whereSql}
                ORDER BY u.role ASC, u.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Daftar kelas untuk modal siswa
        $classes = $pdo->query("SELECT id, name, level FROM kelas WHERE is_active = 1 ORDER BY level ASC, name ASC")->fetchAll();

        // Statistik
        $statGuru = (int) $pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'guru' AND deleted_at IS NULL")->fetchColumn();
        $statSiswa = (int) $pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'siswa' AND deleted_at IS NULL")->fetchColumn();
        $statOperator = (int) $pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'operator' AND deleted_at IS NULL")->fetchColumn();
        $statKepala = (int) $pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'kepala' AND deleted_at IS NULL")->fetchColumn();
        $statAkreditasi = (int) $pdo->query("SELECT COUNT(*) FROM pengguna WHERE role = 'akreditasi' AND deleted_at IS NULL")->fetchColumn();

        $data = [
            'title' => 'Pengguna & Akun Mobile App | Panel Admin MTs Al-Huda',
            'user' => $currentUser,
            'role' => Auth::displayRole(),
            'active_menu' => 'pengguna',
            'users' => $users,
            'pengguna' => $users,
            'classes' => $classes,
            'stat_guru' => $statGuru,
            'stat_siswa' => $statSiswa,
            'stat_operator' => $statOperator,
            'stat_kepala' => $statKepala,
            'stat_akreditasi' => $statAkreditasi,
            'current_role' => $role,
            'current_status' => $status,
            'q' => $q,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/pengguna/index.php';
    }

    public function store(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? 'guru'));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '') {
            $_SESSION['_flash_error'] = 'Nama lengkap dan email wajib diisi.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_flash_error'] = 'Format email tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        // Enum `pengguna.role` di produksi hanya: admin, operator, kepala, guru, siswa.
        // 'akreditasi' tidak ada di enum, jadi dipetakan ke operator agar INSERT tidak ditolak.
        if ($role === 'akreditasi') {
            $role = 'operator';
        }

        if (!in_array($role, ['admin', 'operator', 'kepala', 'guru', 'siswa'], true)) {
            $role = 'guru';
        }

        if ($password === '') {
            $password = '123';
        }

        $pdo = Database::connection();

        // `uq_pengguna_email` berlaku untuk semua baris (termasuk yang di-soft-delete).
        $check = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE email = ?");
        $check->execute([$email]);
        if ((int) $check->fetchColumn() > 0) {
            $_SESSION['_flash_error'] = 'Email sudah digunakan oleh pengguna lain.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        // Cek keunikan username jika diisi
        if ($username !== '') {
            $checkU = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ?");
            $checkU->execute([$username]);
            if ((int) $checkU->fetchColumn() > 0) {
                $_SESSION['_flash_error'] = 'Nama pengguna (username) sudah digunakan.';
                header('Location: ' . base_url('admin/pengguna'));
                return;
            }
        } else {
            $username = explode('@', $email)[0];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $gender = in_array($_POST['gender'] ?? 'L', ['L', 'P'], true) ? (string) $_POST['gender'] : 'L';
        $classId = !empty($_POST['class_id']) && (int) $_POST['class_id'] > 0 ? (int) $_POST['class_id'] : null;

        // Siswa wajib punya kelas (kolom siswa.class_id NOT NULL di skema produksi).
        if ($role === 'siswa' && $classId === null) {
            $_SESSION['_flash_error'] = 'Kelas wajib dipilih untuk akun siswa.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO pengguna (name, nama, email, username, password_hash, role, is_active, must_change_password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, 0, NOW(), NOW())");
            $stmt->execute([
                $name,
                $name,
                $email,
                $username,
                $passwordHash,
                $role,
            ]);

            $newUserId = (int) $pdo->lastInsertId();

            // Jika role Guru, buat record di tabel guru
            if ($role === 'guru') {
                $nip = !empty($_POST['nip']) ? trim((string) $_POST['nip']) : null;
                $nuptk = !empty($_POST['nuptk']) ? trim((string) $_POST['nuptk']) : null;
                $specialty = !empty($_POST['subject_specialty']) ? trim((string) $_POST['subject_specialty']) : null;

                $tStmt = $pdo->prepare("INSERT INTO guru (user_id, nama_lengkap, nip, nuptk, gender, jenis_kelamin, subject_specialty, bidang_studi, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $tStmt->execute([$newUserId, $name, $nip, $nuptk, $gender, $gender, $specialty, $specialty]);
            }

            // Jika role Siswa, buat record di tabel siswa
            if ($role === 'siswa') {
                $nis = !empty($_POST['nis']) ? trim((string) $_POST['nis']) : ('S' . date('Y') . sprintf('%04d', rand(1, 9999)));
                $nisn = !empty($_POST['nisn']) ? trim((string) $_POST['nisn']) : null;

                $sStmt = $pdo->prepare("INSERT INTO siswa (user_id, nama_lengkap, nis, nisn, class_id, kelas_id, gender, jenis_kelamin, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
                $sStmt->execute([$newUserId, $name, $nis, $nisn, $classId, $classId, $gender, $gender]);
            }

            $pdo->commit();

            Auth::log('pengguna.create', 'pengguna', $newUserId, "Membuat akun pengguna: {$email} ({$role})");

            $_SESSION['_flash_success'] = "Pengguna '{$name}' ({$role}) berhasil dibuat. Sandi awal: {$password}";
        } catch (Throwable $e) {
            $pdo->rollBack();
            $_SESSION['_flash_error'] = 'Gagal membuat pengguna: ' . $e->getMessage();
        }

        header('Location: ' . base_url('admin/pengguna'));
    }

    public function resetPassword(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $pdo = Database::connection();
        $tempPassword = '123';
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("UPDATE pengguna SET password_hash = ?, must_change_password = 0, failed_login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$passwordHash, $id]);

        Auth::log('pengguna.reset_password', 'pengguna', $id, "Mereset kata sandi pengguna ID {$id}");

        $_SESSION['_flash_success'] = "Kata sandi berhasil direset menjadi: 123";
        header('Location: ' . base_url('admin/pengguna'));
    }

    public function toggleStatus(int $id): void
    {
        $currentUser = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if ((int) $currentUser['id'] === $id) {
            $_SESSION['_flash_error'] = 'Anda tidak dapat menonaktifkan akun sendiri.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE pengguna SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);

        Auth::log('pengguna.toggle_status', 'pengguna', $id, "Mengubah status aktif pengguna ID {$id}");

        $_SESSION['_flash_success'] = 'Status aktif pengguna berhasil diubah.';
        header('Location: ' . base_url('admin/pengguna'));
    }

    public function delete(int $id): void
    {
        $currentUser = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        if ((int) $currentUser['id'] === $id) {
            $_SESSION['_flash_error'] = 'Anda tidak dapat menghapus akun sendiri.';
            header('Location: ' . base_url('admin/pengguna'));
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE pengguna SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);

        Auth::log('pengguna.delete', 'pengguna', $id, "Menghapus (soft delete) pengguna ID {$id}");

        $_SESSION['_flash_success'] = 'Pengguna berhasil dihapus.';
        header('Location: ' . base_url('admin/pengguna'));
    }
}
