<?php

class AdminJadwalController
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

    /**
     * Skema produksi menyimpan hari sebagai ENUM nama, bukan hanya angka.
     */
    private function dayName(int $day): string
    {
        return match ($day) {
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => 'Senin',
        };
    }

    public function index(): void
    {
        $user = $this->guard();
        $pdo = Database::connection();

        // Get academic years
        $academicYears = $pdo->query("SELECT * FROM tahun_akademik ORDER BY is_active DESC, name DESC")->fetchAll();
        $activeYear = $academicYears[0] ?? null;

        $yearId = isset($_GET['year_id']) ? (int) $_GET['year_id'] : (int) ($activeYear['id'] ?? 0);

        // Get classes for selected year
        $classesStmt = $pdo->prepare("SELECT * FROM kelas WHERE academic_year_id = ? ORDER BY level ASC, name ASC");
        $classesStmt->execute([$yearId]);
        $classes = $classesStmt->fetchAll();

        $selectedClassId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : (int) ($classes[0]['id'] ?? 0);

        // Get schedules for selected class and year
        $schedules = [];
        if ($selectedClassId > 0) {
            $sStmt = $pdo->prepare("SELECT s.*, cs.class_id, sub.name as subject_name, sub.code as subject_code, u.name as teacher_name
                                    FROM jadwal s
                                    JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
                                    JOIN mata_pelajaran sub ON sub.id = cs.subject_id
                                    JOIN guru t ON t.id = s.teacher_id
                                    JOIN pengguna u ON u.id = t.user_id
                                    WHERE cs.class_id = ? AND s.academic_year_id = ?
                                    ORDER BY s.day_of_week ASC, s.start_time ASC");
            $sStmt->execute([$selectedClassId, $yearId]);
            $schedules = $sStmt->fetchAll();
        }

        // Get subjects & teachers for modal dropdown
        $subjects = $pdo->query("SELECT * FROM mata_pelajaran WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
        $teachers = $pdo->query("SELECT t.id, u.name, t.nip FROM guru t JOIN pengguna u ON u.id = t.user_id WHERE u.is_active = 1 AND u.deleted_at IS NULL ORDER BY u.name ASC")->fetchAll();

        $data = [
            'title' => 'Jadwal Pelajaran | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'jadwal',
            'tahun_akademik' => $academicYears,
            'current_year_id' => $yearId,
            'kelas' => $classes,
            'current_class_id' => $selectedClassId,
            'jadwal' => $schedules,
            'mata_pelajaran' => $subjects,
            'guru' => $teachers,
            'flash' => $_SESSION['_flash_success'] ?? null,
            'error' => $_SESSION['_flash_error'] ?? null,
        ];

        unset($_SESSION['_flash_success'], $_SESSION['_flash_error']);

        require_once __DIR__ . '/../Views/admin/jadwal/index.php';
    }

    public function store(): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/jadwal'));
            return;
        }

        $classId = (int) ($_POST['class_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $yearId = (int) ($_POST['year_id'] ?? 0);
        $dayOfWeek = (int) ($_POST['day_of_week'] ?? 1);
        $startTime = trim((string) ($_POST['start_time'] ?? ''));
        $endTime = trim((string) ($_POST['end_time'] ?? ''));
        $room = trim((string) ($_POST['room'] ?? ''));

        if ($classId <= 0 || $subjectId <= 0 || $teacherId <= 0 || $yearId <= 0 || $startTime === '' || $endTime === '') {
            $_SESSION['_flash_error'] = 'Mohon lengkapi semua kolom wajib (tahun ajaran, kelas, mapel, guru, hari, jam).';
            header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
            return;
        }

        $pdo = Database::connection();

        // Ensure class_subjects entry exists or find it
        $csStmt = $pdo->prepare("SELECT id FROM mata_pelajaran_kelas WHERE class_id = ? AND subject_id = ?");
        $csStmt->execute([$classId, $subjectId]);
        $classSubjectId = $csStmt->fetchColumn();

        if (!$classSubjectId) {
            $insertCs = $pdo->prepare("INSERT INTO mata_pelajaran_kelas (class_id, kelas_id, subject_id, mata_pelajaran_id, teacher_id, guru_id, weekly_hours, jam_mingguan, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 2, 2, 1, NOW(), NOW())");
            $insertCs->execute([$classId, $classId, $subjectId, $subjectId, $teacherId, $teacherId]);
            $classSubjectId = (int) $pdo->lastInsertId();
        }

        // Check teacher clash: same teacher, same year, same day, overlapping time
        $clashStmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal 
                                    WHERE teacher_id = ? AND academic_year_id = ? AND day_of_week = ? 
                                    AND (start_time < ? AND end_time > ?)");
        $clashStmt->execute([$teacherId, $yearId, $dayOfWeek, $endTime, $startTime]);
        if ((int) $clashStmt->fetchColumn() > 0) {
            $_SESSION['_flash_error'] = 'Bentrok jadwal! Guru yang dipilih sudah memiliki jadwal mengajar di kelas lain pada jam dan hari tersebut.';
            header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
            return;
        }

        // Check class clash: same class, same year, same day, overlapping time
        $classClashStmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal s
                                         JOIN mata_pelajaran_kelas cs ON cs.id = s.class_subject_id
                                         WHERE cs.class_id = ? AND s.academic_year_id = ? AND s.day_of_week = ?
                                         AND (s.start_time < ? AND s.end_time > ?)");
        $classClashStmt->execute([$classId, $yearId, $dayOfWeek, $endTime, $startTime]);
        if ((int) $classClashStmt->fetchColumn() > 0) {
            $_SESSION['_flash_error'] = 'Bentrok jadwal! Kelas ini sudah memiliki mata pelajaran lain pada jam dan hari tersebut.';
            header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
            return;
        }

        $insStmt = $pdo->prepare("INSERT INTO jadwal (class_subject_id, kelas_id, subject_id, mata_pelajaran_id, teacher_id, guru_id, academic_year_id, day_of_week, hari, start_time, jam_mulai, end_time, jam_selesai, room, ruangan, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $insStmt->execute([
            $classSubjectId,
            $classId,
            $subjectId,
            $subjectId,
            $teacherId,
            $teacherId,
            $yearId,
            $dayOfWeek,
            $this->dayName($dayOfWeek),
            $startTime,
            $startTime,
            $endTime,
            $endTime,
            $room !== '' ? $room : null,
            $room !== '' ? $room : null,
        ]);

        Auth::log('jadwal.create', 'jadwal', (int) $pdo->lastInsertId(), "Menambah jadwal kelas ID {$classId}");

        $_SESSION['_flash_success'] = 'Jadwal pelajaran berhasil ditambahkan.';
        header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
    }

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $_SESSION['_flash_error'] = 'Token CSRF tidak valid.';
            header('Location: ' . base_url('admin/jadwal'));
            return;
        }

        $classId = (int) ($_POST['class_id'] ?? 0);
        $yearId = (int) ($_POST['year_id'] ?? 0);

        $pdo = Database::connection();
        $stmt = $pdo->prepare("DELETE FROM jadwal WHERE id = ?");
        $stmt->execute([$id]);

        Auth::log('jadwal.delete', 'jadwal', $id, "Menghapus jadwal ID {$id}");

        $_SESSION['_flash_success'] = 'Jadwal berhasil dihapus.';
        header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
    }
}
