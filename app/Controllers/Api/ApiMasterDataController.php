<?php
/**
 * API v1 — Master Data Kelas & Mata Pelajaran.
 *
 * Digunakan untuk dropdown dan pemilihan data master di aplikasi mobile.
 */

class ApiMasterDataController
{
    private function requireUser(): ?array
    {
        $user = ApiAuth::authenticate();
        if ($user === null) {
            ApiResponse::unauthorized('Silakan login terlebih dahulu.');
            return null;
        }
        return $user;
    }

    private function requireStaff(): ?array
    {
        $user = $this->requireUser();
        if ($user === null) {
            return null;
        }
        $role = strtolower((string) ($user['role'] ?? ''));
        if (!in_array($role, ['admin', 'operator', 'kepala', 'staf', 'staff'], true)) {
            ApiResponse::forbidden('Hanya admin/operator yang boleh mengubah data master.');
            return null;
        }
        return $user;
    }

    /**
     * GET /api/v1/kelas
     */
    public function kelas(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT k.id, k.name, k.nama_kelas, k.level, k.tingkat, k.room, k.ruangan,
                    k.capacity, k.homeroom_teacher_id, k.wali_kelas_id,
                    y.name AS tahun_ajaran,
                    COALESCE(g.nama_lengkap, pu.name) AS wali_kelas_nama
             FROM kelas k
             LEFT JOIN tahun_akademik y ON y.id = k.academic_year_id
             LEFT JOIN guru g ON g.id = COALESCE(k.wali_kelas_id, k.homeroom_teacher_id)
             LEFT JOIN pengguna pu ON pu.id = g.user_id
             WHERE k.is_active = 1 AND k.deleted_at IS NULL
             ORDER BY k.level ASC, k.name ASC"
        );
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $name = (string) ($r['name'] ?? $r['nama_kelas'] ?? '');
            $level = (int) ($r['level'] ?? $r['tingkat'] ?? 7);
            $items[] = [
                'id'           => (int) $r['id'],
                'name'         => $name,
                'nama_kelas'   => $name,
                'level'        => $level,
                'tingkat'      => $level,
                'room'         => (string) ($r['room'] ?? $r['ruangan'] ?? ''),
                'wali_kelas'   => $r['wali_kelas_nama'] ?: null,
                'tahun_ajaran' => (string) ($r['tahun_ajaran'] ?? '2026/2027'),
            ];
        }

        ApiResponse::ok($items);
    }

    /**
     * POST /api/v1/kelas
     */
    public function storeKelas(array $params = []): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $input = ApiResponse::input();
        $nama = trim((string) ($input['name'] ?? $input['nama_kelas'] ?? $input['nama'] ?? ''));
        $tingkat = (int) ($input['level'] ?? $input['tingkat'] ?? 7);
        $ruangan = trim((string) ($input['room'] ?? $input['ruangan'] ?? ''));
        $waliNama = trim((string) ($input['wali_kelas'] ?? ''));

        if ($nama === '') {
            ApiResponse::validation(['nama_kelas' => 'Nama kelas wajib diisi.']);
            return;
        }
        if ($tingkat < 1 || $tingkat > 12) {
            $tingkat = 7;
        }

        $pdo = Database::connection();
        $yearId = (int) $pdo->query("SELECT id FROM tahun_akademik WHERE is_active = 1 LIMIT 1")->fetchColumn();
        if ($yearId <= 0) {
            $yearId = (int) $pdo->query("SELECT id FROM tahun_akademik ORDER BY id DESC LIMIT 1")->fetchColumn();
        }
        if ($yearId <= 0) {
            ApiResponse::error('NO_ACADEMIC_YEAR', 'Belum ada tahun akademik di database.', 409);
            return;
        }

        $waliId = null;
        if ($waliNama !== '') {
            $gStmt = $pdo->prepare("SELECT id FROM guru WHERE nama_lengkap = ? AND deleted_at IS NULL LIMIT 1");
            $gStmt->execute([$waliNama]);
            $foundWali = $gStmt->fetchColumn();
            if ($foundWali !== false) {
                $waliId = (int) $foundWali;
            }
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO kelas (academic_year_id, name, nama_kelas, level, tingkat, homeroom_teacher_id, wali_kelas_id, room, ruangan, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
            );
            $stmt->execute([
                $yearId,
                $nama,
                $nama,
                $tingkat,
                $tingkat,
                $waliId,
                $waliId,
                $ruangan !== '' ? $ruangan : null,
                $ruangan !== '' ? $ruangan : 'Ruang Kelas',
            ]);
            $newId = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            ApiResponse::error('DUPLICATE_OR_DB_ERROR', 'Kelas gagal disimpan: nama kelas mungkin sudah ada.', 409);
            return;
        }

        ApiResponse::created([
            'id'           => $newId,
            'name'         => $nama,
            'nama_kelas'   => $nama,
            'level'        => $tingkat,
            'tingkat'      => $tingkat,
            'room'         => $ruangan,
            'wali_kelas'   => $waliNama !== '' ? $waliNama : null,
            'tahun_ajaran' => '2026/2027',
        ]);
    }

    /**
     * PUT /api/v1/kelas/{id}
     */
    public function updateKelas(array $params): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('Kelas tidak ditemukan.');
            return;
        }

        $input = ApiResponse::input();
        $nama = trim((string) ($input['name'] ?? $input['nama_kelas'] ?? $input['nama'] ?? ''));
        $tingkat = (int) ($input['level'] ?? $input['tingkat'] ?? 7);
        $ruangan = trim((string) ($input['room'] ?? $input['ruangan'] ?? ''));
        $waliNama = trim((string) ($input['wali_kelas'] ?? ''));

        if ($nama === '') {
            ApiResponse::validation(['nama_kelas' => 'Nama kelas wajib diisi.']);
            return;
        }

        $pdo = Database::connection();
        $waliId = null;
        if ($waliNama !== '') {
            $gStmt = $pdo->prepare("SELECT id FROM guru WHERE nama_lengkap = ? AND deleted_at IS NULL LIMIT 1");
            $gStmt->execute([$waliNama]);
            $foundWali = $gStmt->fetchColumn();
            if ($foundWali !== false) {
                $waliId = (int) $foundWali;
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE kelas
             SET name = ?, nama_kelas = ?, level = ?, tingkat = ?, homeroom_teacher_id = ?, wali_kelas_id = ?,
                 room = COALESCE(NULLIF(?, ''), room), ruangan = COALESCE(NULLIF(?, ''), ruangan), updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$nama, $nama, $tingkat, $tingkat, $waliId, $waliId, $ruangan, $ruangan, $id]);

        ApiResponse::ok([
            'id'         => $id,
            'name'       => $nama,
            'nama_kelas' => $nama,
            'level'      => $tingkat,
            'tingkat'    => $tingkat,
            'wali_kelas' => $waliNama !== '' ? $waliNama : null,
        ]);
    }

    /**
     * DELETE /api/v1/kelas/{id}
     */
    public function deleteKelas(array $params): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('Kelas tidak ditemukan.');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE kelas SET is_active = 0, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        ApiResponse::ok(['deleted' => true, 'id' => $id]);
    }

    /**
     * GET /api/v1/mata-pelajaran
     */
    public function mataPelajaran(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT id, code, kode_mapel, name, nama_mapel, group_name, kelompok
             FROM mata_pelajaran
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY sort_order ASC, name ASC"
        );
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $name = (string) ($r['name'] ?? $r['nama_mapel'] ?? '');
            $code = (string) ($r['code'] ?? $r['kode_mapel'] ?? '');
            $group = (string) ($r['group_name'] ?? $r['kelompok'] ?? 'umum');
            $items[] = [
                'id'         => (int) $r['id'],
                'code'       => $code,
                'kode_mapel' => $code,
                'name'       => $name,
                'nama_mapel' => $name,
                'group'      => $group,
                'kelompok'   => $group,
            ];
        }

        ApiResponse::ok($items);
    }

    /**
     * POST /api/v1/mata-pelajaran
     */
    public function storeMapel(array $params = []): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $input = ApiResponse::input();
        $name = trim((string) ($input['name'] ?? $input['nama_mapel'] ?? $input['nama'] ?? ''));
        $code = strtoupper(trim((string) ($input['code'] ?? $input['kode_mapel'] ?? $input['kode'] ?? '')));
        $rawGroup = strtolower(trim((string) ($input['group'] ?? $input['kelompok'] ?? 'umum')));
        $group = str_contains($rawGroup, 'pai') || str_contains($rawGroup, 'agama') ? 'agama' : 'umum';

        if ($name === '' || $code === '') {
            ApiResponse::validation([
                'nama' => $name === '' ? 'Nama mapel wajib diisi.' : null,
                'kode' => $code === '' ? 'Kode mapel wajib diisi.' : null,
            ]);
            return;
        }

        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO mata_pelajaran (code, kode_mapel, name, nama_mapel, group_name, kelompok, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())"
            );
            $stmt->execute([$code, $code, $name, $name, $group, $group]);
            $newId = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            ApiResponse::error('DUPLICATE_OR_DB_ERROR', 'Mapel gagal disimpan: kode mapel mungkin sudah ada.', 409);
            return;
        }

        ApiResponse::created([
            'id'         => $newId,
            'code'       => $code,
            'kode_mapel' => $code,
            'name'       => $name,
            'nama_mapel' => $name,
            'group'      => $group,
            'kelompok'   => $group,
        ]);
    }

    /**
     * PUT /api/v1/mata-pelajaran/{id}
     */
    public function updateMapel(array $params): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('Mata pelajaran tidak ditemukan.');
            return;
        }

        $input = ApiResponse::input();
        $name = trim((string) ($input['name'] ?? $input['nama_mapel'] ?? $input['nama'] ?? ''));
        $code = strtoupper(trim((string) ($input['code'] ?? $input['kode_mapel'] ?? $input['kode'] ?? '')));
        $rawGroup = strtolower(trim((string) ($input['group'] ?? $input['kelompok'] ?? 'umum')));
        $group = str_contains($rawGroup, 'pai') || str_contains($rawGroup, 'agama') ? 'agama' : 'umum';

        if ($name === '' || $code === '') {
            ApiResponse::validation(['nama' => 'Nama dan kode mapel wajib diisi.']);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "UPDATE mata_pelajaran
             SET code = ?, kode_mapel = ?, name = ?, nama_mapel = ?, group_name = ?, kelompok = ?, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$code, $code, $name, $name, $group, $group, $id]);

        ApiResponse::ok([
            'id'         => $id,
            'code'       => $code,
            'kode_mapel' => $code,
            'name'       => $name,
            'nama_mapel' => $name,
            'group'      => $group,
            'kelompok'   => $group,
        ]);
    }

    /**
     * DELETE /api/v1/mata-pelajaran/{id}
     */
    public function deleteMapel(array $params): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('Mata pelajaran tidak ditemukan.');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare("UPDATE mata_pelajaran SET is_active = 0, deleted_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        ApiResponse::ok(['deleted' => true, 'id' => $id]);
    }

    /**
     * POST /api/v1/admin/seed-test-accounts
     * Membuat akun guru_test (akses semua kelas & mapel) + 1 akun siswa mudah diketik per kelas.
     */
    public function seedTestAccounts(array $params = []): void
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $pwHash = password_hash('123', PASSWORD_DEFAULT);
        $created = [];

        // 1. Akun guru_test (akses semua kelas & mapel)
        $uStmt = $pdo->prepare("SELECT id FROM pengguna WHERE username = ? LIMIT 1");
        $uStmt->execute(['guru_test']);
        $guruUserId = (int) $uStmt->fetchColumn();

        if ($guruUserId <= 0) {
            $insU = $pdo->prepare(
                "INSERT INTO pengguna (username, name, nama, email, password_hash, role, role_label, is_active, created_at, updated_at)
                 VALUES ('guru_test', 'Guru Testing', 'Guru Testing', 'guru_test@mts-alhuda.sch.id', ?, 'guru', 'Guru', 1, NOW(), NOW())"
            );
            $insU->execute([$pwHash]);
            $guruUserId = (int) $pdo->lastInsertId();
        } else {
            $pdo->prepare("UPDATE pengguna SET password_hash = ?, is_active = 1, deleted_at = NULL WHERE id = ?")->execute([$pwHash, $guruUserId]);
        }

        $gStmt = $pdo->prepare("SELECT id FROM guru WHERE user_id = ? LIMIT 1");
        $gStmt->execute([$guruUserId]);
        $guruId = (int) $gStmt->fetchColumn();
        if ($guruId <= 0) {
            $insG = $pdo->prepare(
                "INSERT INTO guru (user_id, nip, nuptk, nama_lengkap, gender, jenis_kelamin, subject_specialty, bidang_studi, address, alamat, is_active, created_at, updated_at)
                 VALUES (?, '199900010001', '199900010001', 'Guru Testing', 'L', 'L', 'Semua Mata Pelajaran', 'Semua Mata Pelajaran', 'MTs Al-Huda Gondang', 'MTs Al-Huda Gondang', 1, NOW(), NOW())"
            );
            $insG->execute([$guruUserId]);
        }
        $created[] = ['role' => 'guru', 'username' => 'guru_test', 'password' => '123', 'kelas' => 'Semua Kelas & Mapel'];

        // 2. 1 akun siswa mudah diketik untuk setiap kelas aktif
        $kelasRows = $pdo->query(
            "SELECT id, COALESCE(name, nama_kelas) AS nama
             FROM kelas
             WHERE is_active = 1 AND deleted_at IS NULL
             ORDER BY level ASC, name ASC"
        )->fetchAll();

        foreach ($kelasRows as $idx => $k) {
            $kelasId = (int) $k['id'];
            $kelasNama = trim((string) $k['nama']);
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $kelasNama));
            $username = 'siswa' . $slug;
            $email = $username . '@siswa.mts-alhuda.sch.id';
            $nama = 'Siswa Kelas ' . $kelasNama;
            $nis = '9900' . str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);
            $nisn = '00990000' . str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT);

            $uStmt->execute([$username]);
            $sUserId = (int) $uStmt->fetchColumn();
            if ($sUserId <= 0) {
                $insU = $pdo->prepare(
                    "INSERT INTO pengguna (username, name, nama, email, password_hash, role, role_label, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 'siswa', 'Siswa', 1, NOW(), NOW())"
                );
                $insU->execute([$username, $nama, $nama, $email, $pwHash]);
                $sUserId = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare("UPDATE pengguna SET password_hash = ?, is_active = 1, deleted_at = NULL WHERE id = ?")->execute([$pwHash, $sUserId]);
            }

            $sStmt = $pdo->prepare("SELECT id FROM siswa WHERE user_id = ? LIMIT 1");
            $sStmt->execute([$sUserId]);
            $sId = (int) $sStmt->fetchColumn();
            if ($sId <= 0) {
                $insS = $pdo->prepare(
                    "INSERT INTO siswa (user_id, class_id, kelas_id, nis, nisn, nama_lengkap, gender, jenis_kelamin, address, alamat, guardian_name, nama_wali, is_active, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'L', 'L', 'Gondang, Nganjuk', 'Gondang, Nganjuk', 'Wali Murid', 'Wali Murid', 1, 'aktif', NOW(), NOW())"
                );
                $insS->execute([$sUserId, $kelasId, $kelasId, $nis, $nisn, $nama]);
            } else {
                $pdo->prepare("UPDATE siswa SET class_id = ?, kelas_id = ?, is_active = 1, deleted_at = NULL WHERE id = ?")->execute([$kelasId, $kelasId, $sId]);
            }

            $created[] = ['role' => 'siswa', 'username' => $username, 'password' => '123', 'kelas' => $kelasNama];
        }

        ApiResponse::ok(['accounts' => $created]);
    }
}
