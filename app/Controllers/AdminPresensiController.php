<?php

class AdminPresensiController
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
        $user = $this->guard();
        $pdo = Database::connection();

        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $status = trim((string) ($_GET['status'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        // All teachers
        $teachers = $pdo->query("SELECT t.id as teacher_id, t.nip, u.name as teacher_name, u.email 
                                 FROM guru t 
                                 JOIN pengguna u ON u.id = t.user_id 
                                 WHERE u.deleted_at IS NULL AND u.is_active = 1 
                                 ORDER BY u.name ASC")->fetchAll();

        // Get daily attendances for selected date
        $where = ['a.attendance_date = ?', "a.attendance_type = 'daily'"];
        $params = [$date];

        if ($status !== '' && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'dinas', 'alpa'], true)) {
            $where[] = 'a.status = ?';
            $params[] = $status;
        }

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $where[] = '(u.name LIKE ? OR t.nip LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT a.*, t.id as teacher_id, t.nip, u.name as teacher_name, u.email
                FROM guru t
                JOIN pengguna u ON u.id = t.user_id
                LEFT JOIN presensi_guru a ON a.teacher_id = t.id AND a.attendance_date = ? AND a.attendance_type = 'daily'
                WHERE u.deleted_at IS NULL AND u.is_active = 1";

        $listParams = [$date];
        if ($status !== '' && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'dinas', 'alpa'], true)) {
            $sql .= ' AND a.status = ?';
            $listParams[] = $status;
        }
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $sql .= ' AND (u.name LIKE ? OR t.nip LIKE ?)';
            $listParams[] = $like;
            $listParams[] = $like;
        }

        $sql .= " ORDER BY u.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($listParams);
        $records = $stmt->fetchAll();

        // Stat counts for the selected date
        $statHadir = (int) $pdo->query("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = '{$date}' AND status = 'hadir' AND attendance_type = 'daily'")->fetchColumn();
        $statTerlambat = (int) $pdo->query("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = '{$date}' AND status = 'terlambat' AND attendance_type = 'daily'")->fetchColumn();
        $statIzin = (int) $pdo->query("SELECT COUNT(*) FROM presensi_guru WHERE attendance_date = '{$date}' AND status IN ('izin', 'sakit', 'dinas') AND attendance_type = 'daily'")->fetchColumn();
        $totalTeachers = count($teachers);
        $statBelum = max(0, $totalTeachers - ($statHadir + $statTerlambat + $statIzin));

        $data = [
            'title' => 'Rekap Presensi Guru & Mobile App | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'presensi',
            'date' => $date,
            'status' => $status,
            'q' => $q,
            'guru' => $teachers,
            'teachers' => $teachers,
            'records' => $records,
            'total_teachers' => $totalTeachers,
            'stat_hadir' => $statHadir,
            'stat_terlambat' => $statTerlambat,
            'stat_izin' => $statIzin,
            'stat_belum' => $statBelum,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/presensi/index.php';
    }

    public function correct(): void
    {
        $currentUser = $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/presensi'));
            return;
        }

        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $date = trim((string) ($_POST['attendance_date'] ?? date('Y-m-d')));
        $status = trim((string) ($_POST['status'] ?? 'hadir'));
        $checkInTime = trim((string) ($_POST['check_in_time'] ?? ''));
        $checkOutTime = trim((string) ($_POST['check_out_time'] ?? ''));
        $note = trim((string) ($_POST['note'] ?? ''));

        if ($teacherId <= 0 || $date === '') {
            $_SESSION['_flash_error'] = 'Pilih guru dan tanggal yang valid.';
            header('Location: ' . base_url('admin/presensi?date=' . $date));
            return;
        }

        if (!in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'dinas', 'alpa'], true)) {
            $status = 'hadir';
        }

        $checkInAt = $checkInTime !== '' ? ($date . ' ' . $checkInTime . ':00') : ($date . ' 07:00:00');
        $checkOutAt = $checkOutTime !== '' ? ($date . ' ' . $checkOutTime . ':00') : null;

        $pdo = Database::connection();

        // Skema produksi: `notes` (bukan `note`), tidak ada `method`/`verified_at`.
        $jamMasuk = $checkInTime !== '' ? $checkInTime . ':00' : null;
        $jamPulang = $checkOutTime !== '' ? $checkOutTime . ':00' : null;
        $noteValue = $note !== '' ? $note : null;
        $verifiedBy = $currentUser['id'] ?? null;

        $existingStmt = $pdo->prepare("SELECT id FROM presensi_guru WHERE teacher_id = ? AND attendance_date = ? AND attendance_type = 'daily' AND deleted_at IS NULL LIMIT 1");
        $existingStmt->execute([$teacherId, $date]);
        $existingId = $existingStmt->fetchColumn();

        if ($existingId) {
            $stmt = $pdo->prepare("UPDATE presensi_guru SET guru_id = ?, tanggal = ?, check_in_at = ?, jam_masuk = ?, check_out_at = ?, jam_pulang = ?, status = ?, notes = ?, keterangan = ?, verified_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $teacherId,
                $date,
                $checkInAt,
                $jamMasuk,
                $checkOutAt,
                $jamPulang,
                $status,
                $noteValue,
                $noteValue,
                $verifiedBy,
                $existingId,
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO presensi_guru (teacher_id, guru_id, attendance_date, tanggal, attendance_type, check_in_at, jam_masuk, check_out_at, jam_pulang, status, notes, keterangan, verified_by, created_at, updated_at) VALUES (?, ?, ?, ?, 'daily', ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $teacherId,
                $teacherId,
                $date,
                $date,
                $checkInAt,
                $jamMasuk,
                $checkOutAt,
                $jamPulang,
                $status,
                $noteValue,
                $noteValue,
                $verifiedBy,
            ]);
        }

        Auth::log('attendance.correct', 'presensi_guru', $teacherId, "Koreksi manual presensi guru ID {$teacherId} tanggal {$date} ({$status})");

        $_SESSION['_flash_success'] = 'Data presensi berhasil dicatat/diperbarui.';
        header('Location: ' . base_url('admin/presensi?date=' . $date));
    }
}
