<?php

require_once __DIR__ . '/../Models/Akademik.php';

class AdminJadwalController
{
    private Akademik $akademikModel;

    public function __construct()
    {
        $this->akademikModel = new Akademik();
    }

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

        // 1. Ambil tahun ajaran melalui Model
        $academicYears = $this->akademikModel->getAllAcademicYears();
        $activeYear = $academicYears[0] ?? null;
        $yearId = isset($_GET['year_id']) ? (int) $_GET['year_id'] : (int) ($activeYear['id'] ?? 0);

        // 2. Ambil kelas untuk tahun ajaran terpilih melalui Model
        $classes = $this->akademikModel->getClassesByYear($yearId);
        $selectedClassId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : (int) ($classes[0]['id'] ?? 0);

        // 3. Ambil jadwal pelajaran melalui Model
        $schedules = $selectedClassId > 0 ? $this->akademikModel->getSchedulesByClass($selectedClassId, $yearId) : [];

        // 4. Ambil mata pelajaran dan guru untuk modal dropdown melalui Model
        $subjects = $this->akademikModel->getAllSubjects();
        $teachers = $this->akademikModel->getAllTeachers();

        $data = [
            'title' => 'Jadwal Pelajaran | Panel Admin MTs Al-Huda',
            'user' => $user,
            'role' => Auth::displayRole(),
            'active_menu' => 'jadwal',
            'tahun_akademik' => $academicYears,
            'academic_years' => $academicYears,
            'current_year_id' => $yearId,
            'kelas' => $classes,
            'classes' => $classes,
            'current_class_id' => $selectedClassId,
            'jadwal' => $schedules,
            'schedules' => $schedules,
            'mata_pelajaran' => $subjects,
            'subjects' => $subjects,
            'guru' => $teachers,
            'teachers' => $teachers,
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

        // Dapatkan/buat class_subject_id
        $classSubjectId = $this->akademikModel->getOrCreateClassSubject($classId, $subjectId, $teacherId);

        // Cek bentrok jadwal guru
        if ($this->akademikModel->hasTeacherClash($teacherId, $yearId, $dayOfWeek, $startTime, $endTime)) {
            $_SESSION['_flash_error'] = 'Bentrok jadwal! Guru yang dipilih sudah memiliki jadwal mengajar di kelas lain pada jam dan hari tersebut.';
            header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
            return;
        }

        // Cek bentrok jadwal kelas
        if ($this->akademikModel->hasClassClash($classId, $yearId, $dayOfWeek, $startTime, $endTime)) {
            $_SESSION['_flash_error'] = 'Bentrok jadwal! Kelas ini sudah memiliki mata pelajaran lain pada jam dan hari tersebut.';
            header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
            return;
        }

        $scheduleId = $this->akademikModel->addSchedule([
            'class_subject_id' => $classSubjectId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $teacherId,
            'academic_year_id' => $yearId,
            'day_of_week' => $dayOfWeek,
            'hari' => $this->dayName($dayOfWeek),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => $room,
        ]);

        Auth::log('jadwal.create', 'jadwal', $scheduleId, "Menambah jadwal kelas ID {$classId}");

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

        $this->akademikModel->deleteSchedule($id);

        Auth::log('jadwal.delete', 'jadwal', $id, "Menghapus jadwal ID {$id}");

        $_SESSION['_flash_success'] = 'Jadwal berhasil dihapus.';
        header('Location: ' . base_url('admin/jadwal?class_id=' . $classId . '&year_id=' . $yearId));
    }
}
