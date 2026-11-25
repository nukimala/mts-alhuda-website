<?php

require_once __DIR__ . '/../Models/Presensi.php';

class AdminPresensiController
{
    private Presensi $presensiModel;

    public function __construct()
    {
        $this->presensiModel = new Presensi();
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

    public function index(): void
    {
        $user = $this->guard();

        $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
        $status = trim((string) ($_GET['status'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));

        // 1. Ambil seluruh guru melalui Model
        $teachers = $this->presensiModel->getTeachers();
        $totalTeachers = count($teachers);

        // 2. Ambil rekaman presensi harian melalui Model
        $records = $this->presensiModel->getAttendanceRecords($date, $status, $q);

        // 3. Ambil statistik presensi melalui Model
        $stats = $this->presensiModel->getDailyStats($date, $totalTeachers);

        // 4. Siapkan data untuk dikirim ke View
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
            'stat_hadir' => $stats['stat_hadir'],
            'stat_terlambat' => $stats['stat_terlambat'],
            'stat_izin' => $stats['stat_izin'],
            'stat_belum' => $stats['stat_belum'],
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

        // Panggil simpan/update ke Model Presensi
        $this->presensiModel->saveOrUpdate([
            'teacher_id' => $teacherId,
            'date' => $date,
            'status' => $status,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'note' => $note,
            'verified_by' => $currentUser['id'] ?? null,
        ]);

        Auth::log('attendance.correct', 'presensi_guru', $teacherId, "Koreksi manual presensi guru ID {$teacherId} tanggal {$date} ({$status})");

        $_SESSION['_flash_success'] = 'Data presensi berhasil dicatat/diperbarui.';
        header('Location: ' . base_url('admin/presensi?date=' . $date));
    }
}
