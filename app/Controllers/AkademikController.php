<?php

require_once __DIR__ . '/../Support/Database.php';

class AkademikController
{
    public function index(): void
    {
        $pdo = Database::connection();

        // 1. Tahun ajaran aktif
        $year = $pdo->query("SELECT * FROM tahun_akademik ORDER BY is_active DESC, name DESC LIMIT 1")->fetch();
        $yearId = $year['id'] ?? 0;

        // 2. Daftar kelas
        $classes = [];
        if ($yearId > 0) {
            $cStmt = $pdo->prepare("SELECT * FROM kelas WHERE academic_year_id = ? ORDER BY level ASC, name ASC");
            $cStmt->execute([$yearId]);
            $classes = $cStmt->fetchAll();
        }

        $selectedClassId = isset($_GET['kelas']) ? (int) $_GET['kelas'] : ($classes[0]['id'] ?? 0);

        // 3. Jadwal pelajaran per kelas
        $schedules = [];
        if ($selectedClassId > 0 && $yearId > 0) {
            $sStmt = $pdo->prepare("SELECT s.*, sub.name as subject_name, sub.code as subject_code, u.name as teacher_name
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

        $data = [
            'title' => 'Program Akademik & Jadwal Pelajaran | MTs Al-Huda Gondang',
            'year' => $year,
            'classes' => $classes,
            'selected_class_id' => $selectedClassId,
            'schedules' => $schedules,
            'settings' => $pdo->query("SELECT setting_key, setting_value FROM pengaturan WHERE is_public = 1")->fetchAll(PDO::FETCH_KEY_PAIR),
        ];

        require_once __DIR__ . '/../Views/akademik/index.php';
    }
}
