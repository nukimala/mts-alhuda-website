<?php

require_once __DIR__ . '/../Models/Akademik.php';

class AkademikController
{
    private Akademik $akademikModel;

    public function __construct()
    {
        $this->akademikModel = new Akademik();
    }

    public function index(): void
    {
        // 1. Ambil tahun ajaran aktif melalui Model
        $year = $this->akademikModel->getActiveYear();
        $yearId = $year['id'] ?? 0;

        // 2. Ambil daftar kelas untuk tahun aktif melalui Model
        $classes = $yearId > 0 ? $this->akademikModel->getClassesByYear($yearId) : [];
        $selectedClassId = isset($_GET['kelas']) ? (int) $_GET['kelas'] : ($classes[0]['id'] ?? 0);

        // 3. Ambil jadwal pelajaran berdasarkan kelas terpilih melalui Model
        $schedules = ($selectedClassId > 0 && $yearId > 0)
            ? $this->akademikModel->getSchedulesByClass($selectedClassId, $yearId)
            : [];

        // 4. Siapkan data untuk dikirim ke View
        $data = [
            'title' => 'Program Akademik & Jadwal Pelajaran | MTs Al-Huda Gondang',
            'year' => $year,
            'classes' => $classes,
            'selected_class_id' => $selectedClassId,
            'schedules' => $schedules,
            'settings' => $this->akademikModel->getPublicSettings(),
        ];

        require_once __DIR__ . '/../Views/akademik/index.php';
    }
}
