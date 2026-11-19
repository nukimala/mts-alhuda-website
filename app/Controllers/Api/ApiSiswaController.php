<?php
/**
 * API v1 — Manajemen Data Siswa.
 *
 * Mendukung:
 * - GET    /api/v1/siswa        (Daftar siswa, filter q, kelas_id, is_active)
 * - GET    /api/v1/siswa/{id}   (Detail siswa)
 * - POST   /api/v1/siswa        (Tambah siswa baru)
 * - PUT    /api/v1/siswa/{id}   (Ubah data siswa)
 * - DELETE /api/v1/siswa/{id}   (Hapus data siswa)
 */

class ApiSiswaController
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

    /**
     * GET /api/v1/siswa
     */
    public function index(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $pdo = Database::connection();
        $where = ['s.deleted_at IS NULL'];
        $args = [];

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(s.nama_lengkap LIKE ? OR s.nis LIKE ? OR s.nisn LIKE ?)';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        $classId = filter_input(INPUT_GET, 'kelas_id', FILTER_VALIDATE_INT)
            ?: filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
        if ($classId !== null && $classId !== false && $classId > 0) {
            $where[] = '(s.class_id = ? OR s.kelas_id = ?)';
            $args[] = $classId;
            $args[] = $classId;
        }

        if (isset($_GET['is_active'])) {
            $active = (int) $_GET['is_active'];
            $where[] = 's.is_active = ?';
            $args[] = $active;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT s.id, s.user_id, s.class_id, s.kelas_id, s.nis, s.nisn, s.nama_lengkap,
                       s.gender, s.jenis_kelamin, s.birth_place, s.tempat_lahir,
                       s.birth_date, s.tanggal_lahir, s.guardian_name, s.nama_wali,
                       s.guardian_phone, s.address, s.alamat, s.foto_url, s.status, s.is_active,
                       c.name AS class_name, c.nama_kelas, c.level AS class_level,
                       u.email, u.username
                FROM siswa s
                LEFT JOIN kelas c ON (c.id = s.class_id OR c.id = s.kelas_id)
                LEFT JOIN pengguna u ON u.id = s.user_id
                WHERE {$whereSql}
                ORDER BY s.is_active DESC, c.level ASC, c.name ASC, s.nama_lengkap ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $items[] = $this->formatRow($r);
        }

        ApiResponse::ok($items);
    }

    /**
     * GET /api/v1/siswa/{id}
     */
    public function detail(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('ID siswa tidak valid.');
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT s.id, s.user_id, s.class_id, s.kelas_id, s.nis, s.nisn, s.nama_lengkap,
                    s.gender, s.jenis_kelamin, s.birth_place, s.tempat_lahir,
                    s.birth_date, s.tanggal_lahir, s.guardian_name, s.nama_wali,
                    s.guardian_phone, s.address, s.alamat, s.foto_url, s.status, s.is_active,
                    c.name AS class_name, c.nama_kelas, c.level AS class_level,
                    u.email, u.username
             FROM siswa s
             LEFT JOIN kelas c ON (c.id = s.class_id OR c.id = s.kelas_id)
             LEFT JOIN pengguna u ON u.id = s.user_id
             WHERE s.id = ? AND s.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            ApiResponse::notFound('Siswa tidak ditemukan.');
            return;
        }

        ApiResponse::ok($this->formatRow($row));
    }

    /**
     * POST /api/v1/siswa
     */
    public function store(array $params = []): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $input = ApiResponse::input();
        $nama = ApiResponse::str($input, 'nama_lengkap') ?: ApiResponse::str($input, 'nama');
        $nis = ApiResponse::str($input, 'nis');
        $nisn = ApiResponse::str($input, 'nisn');
        $namaWali = ApiResponse::str($input, 'nama_wali') ?: ApiResponse::str($input, 'guardian_name');
        $guardianPhone = ApiResponse::str($input, 'no_hp_wali') ?: ApiResponse::str($input, 'guardian_phone');
        $gender = in_array(strtoupper(ApiResponse::str($input, 'gender', 'L')), ['L', 'P'], true) ? strtoupper(ApiResponse::str($input, 'gender', 'L')) : 'L';
        if (stripos(ApiResponse::str($input, 'jenis_kelamin'), 'perempuan') !== false) {
            $gender = 'P';
        }
        $tempatLahir = ApiResponse::str($input, 'tempat_lahir') ?: ApiResponse::str($input, 'birth_place', 'Nganjuk');
        $tanggalLahir = ApiResponse::str($input, 'tanggal_lahir') ?: ApiResponse::str($input, 'birth_date');
        $alamat = ApiResponse::str($input, 'alamat') ?: ApiResponse::str($input, 'address');
        $isActive = isset($input['is_active']) ? ((int) $input['is_active'] === 1 || $input['is_active'] === true ? 1 : 0) : 1;

        if ($nama === '') {
            ApiResponse::validation(['nama_lengkap' => 'Nama lengkap wajib diisi.']);
            return;
        }

        $pdo = Database::connection();

        // Cari class_id berdasarkan kelas_id atau string nama kelas (misal: "VIII A")
        $classId = !empty($input['kelas_id']) ? (int) $input['kelas_id'] : (!empty($input['class_id']) ? (int) $input['class_id'] : 0);
        if ($classId <= 0 && !empty($input['kelas'])) {
            $kelasName = trim((string) $input['kelas']);
            $kStmt = $pdo->prepare("SELECT id FROM kelas WHERE (name = ? OR nama_kelas = ?) AND is_active = 1 LIMIT 1");
            $kStmt->execute([$kelasName, $kelasName]);
            $foundClassId = $kStmt->fetchColumn();
            if ($foundClassId !== false) {
                $classId = (int) $foundClassId;
            }
        }

        // Jika tetap belum ada, fallback ke kelas aktif pertama
        if ($classId <= 0) {
            $firstClass = $pdo->query("SELECT id FROM kelas WHERE is_active = 1 ORDER BY level ASC, id ASC LIMIT 1")->fetchColumn();
            if ($firstClass !== false) {
                $classId = (int) $firstClass;
            } else {
                ApiResponse::validation(['kelas_id' => 'Kelas belum terdaftar. Mohon buat data kelas terlebih dahulu.']);
                return;
            }
        }

        // Format tanggal lahir (bisa "dd/mm/yyyy" atau "yyyy-mm-dd")
        $cleanBirthDate = null;
        if ($tanggalLahir !== '') {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $tanggalLahir, $m)) {
                $cleanBirthDate = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
                $cleanBirthDate = $tanggalLahir;
            }
        }

        if ($nis === '') {
            $nis = 'S' . date('Y') . sprintf('%04d', rand(1000, 9999));
        }

        $pdo->beginTransaction();

        try {
            // Buat akun pengguna role 'siswa'
            $username = 'siswa_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nis));
            // Cek keunikan username
            $uCheck = $pdo->prepare("SELECT COUNT(*) FROM pengguna WHERE username = ?");
            $uCheck->execute([$username]);
            if ((int) $uCheck->fetchColumn() > 0) {
                $username .= '_' . rand(100, 999);
            }
            $email = $username . '@madrasahku.local';

            $pStmt = $pdo->prepare(
                "INSERT INTO pengguna (name, nama, email, username, password_hash, role, is_active, must_change_password, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'siswa', 1, 0, NOW(), NOW())"
            );
            $pStmt->execute([
                $nama,
                $nama,
                $email,
                $username,
                password_hash('123', PASSWORD_BCRYPT),
            ]);
            $userId = (int) $pdo->lastInsertId();

            $sStmt = $pdo->prepare(
                "INSERT INTO siswa (user_id, class_id, kelas_id, nis, nisn, nama_lengkap, gender, jenis_kelamin, birth_place, tempat_lahir, birth_date, tanggal_lahir, guardian_name, nama_wali, guardian_phone, address, alamat, status, is_active, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktif', ?, NOW(), NOW())"
            );
            $sStmt->execute([
                $userId,
                $classId,
                $classId,
                $nis,
                $nisn !== '' ? $nisn : null,
                $nama,
                $gender,
                $gender,
                $tempatLahir,
                $tempatLahir,
                $cleanBirthDate,
                $cleanBirthDate,
                $namaWali !== '' ? $namaWali : null,
                $namaWali !== '' ? $namaWali : null,
                $guardianPhone !== '' ? $guardianPhone : null,
                $alamat !== '' ? $alamat : null,
                $alamat !== '' ? $alamat : null,
                $isActive,
            ]);

            $newId = (int) $pdo->lastInsertId();
            $pdo->commit();

            AuditLog::write((int) $user['id'], 'siswa.create', 'siswa', $newId, "Tambah data siswa {$nama} ({$nis})", RateLimiter::clientIp());

            $fetchStmt = $pdo->prepare(
                "SELECT s.id, s.user_id, s.class_id, s.kelas_id, s.nis, s.nisn, s.nama_lengkap,
                        s.gender, s.jenis_kelamin, s.birth_place, s.tempat_lahir,
                        s.birth_date, s.tanggal_lahir, s.guardian_name, s.nama_wali,
                        s.guardian_phone, s.address, s.alamat, s.foto_url, s.status, s.is_active,
                        c.name AS class_name, c.nama_kelas, c.level AS class_level,
                        u.email, u.username
                 FROM siswa s
                 LEFT JOIN kelas c ON (c.id = s.class_id OR c.id = s.kelas_id)
                 LEFT JOIN pengguna u ON u.id = s.user_id
                 WHERE s.id = ? LIMIT 1"
            );
            $fetchStmt->execute([$newId]);
            $createdRow = $fetchStmt->fetch();

            ApiResponse::created($this->formatRow($createdRow));
        } catch (Throwable $e) {
            $pdo->rollBack();
            ApiResponse::error('SERVER_ERROR', 'Gagal menambahkan siswa: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v1/siswa/{id}
     */
    public function update(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('ID siswa tidak valid.');
            return;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id, user_id, class_id FROM siswa WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $check->execute([$id]);
        $existing = $check->fetch();

        if ($existing === false) {
            ApiResponse::notFound('Siswa tidak ditemukan.');
            return;
        }

        $input = ApiResponse::input();
        $nama = ApiResponse::str($input, 'nama_lengkap') ?: ApiResponse::str($input, 'nama');
        $nis = ApiResponse::str($input, 'nis');
        $nisn = ApiResponse::str($input, 'nisn');
        $namaWali = ApiResponse::str($input, 'nama_wali') ?: ApiResponse::str($input, 'guardian_name');
        $guardianPhone = ApiResponse::str($input, 'no_hp_wali') ?: ApiResponse::str($input, 'guardian_phone');
        $gender = in_array(strtoupper(ApiResponse::str($input, 'gender', 'L')), ['L', 'P'], true) ? strtoupper(ApiResponse::str($input, 'gender', 'L')) : 'L';
        if (stripos(ApiResponse::str($input, 'jenis_kelamin'), 'perempuan') !== false) {
            $gender = 'P';
        }
        $tempatLahir = ApiResponse::str($input, 'tempat_lahir') ?: ApiResponse::str($input, 'birth_place', 'Nganjuk');
        $tanggalLahir = ApiResponse::str($input, 'tanggal_lahir') ?: ApiResponse::str($input, 'birth_date');
        $alamat = ApiResponse::str($input, 'alamat') ?: ApiResponse::str($input, 'address');
        $isActive = isset($input['is_active']) ? ((int) $input['is_active'] === 1 || $input['is_active'] === true ? 1 : 0) : 1;

        if ($nama === '') {
            ApiResponse::validation(['nama_lengkap' => 'Nama lengkap wajib diisi.']);
            return;
        }

        $classId = !empty($input['kelas_id']) ? (int) $input['kelas_id'] : (!empty($input['class_id']) ? (int) $input['class_id'] : 0);
        if ($classId <= 0 && !empty($input['kelas'])) {
            $kelasName = trim((string) $input['kelas']);
            $kStmt = $pdo->prepare("SELECT id FROM kelas WHERE (name = ? OR nama_kelas = ?) AND is_active = 1 LIMIT 1");
            $kStmt->execute([$kelasName, $kelasName]);
            $foundClassId = $kStmt->fetchColumn();
            if ($foundClassId !== false) {
                $classId = (int) $foundClassId;
            }
        }
        if ($classId <= 0) {
            $classId = (int) $existing['class_id'];
        }

        $cleanBirthDate = null;
        if ($tanggalLahir !== '') {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $tanggalLahir, $m)) {
                $cleanBirthDate = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalLahir)) {
                $cleanBirthDate = $tanggalLahir;
            }
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "UPDATE siswa
                 SET nama_lengkap = ?,
                     class_id = ?,
                     kelas_id = ?,
                     nis = ?,
                     nisn = ?,
                     gender = ?,
                     jenis_kelamin = ?,
                     birth_place = ?,
                     tempat_lahir = ?,
                     birth_date = ?,
                     tanggal_lahir = ?,
                     guardian_name = ?,
                     nama_wali = ?,
                     guardian_phone = ?,
                     address = ?,
                     alamat = ?,
                     is_active = ?,
                     updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([
                $nama,
                $classId,
                $classId,
                $nis !== '' ? $nis : null,
                $nisn !== '' ? $nisn : null,
                $gender,
                $gender,
                $tempatLahir,
                $tempatLahir,
                $cleanBirthDate,
                $cleanBirthDate,
                $namaWali !== '' ? $namaWali : null,
                $namaWali !== '' ? $namaWali : null,
                $guardianPhone !== '' ? $guardianPhone : null,
                $alamat !== '' ? $alamat : null,
                $alamat !== '' ? $alamat : null,
                $isActive,
                $id,
            ]);

            $userId = (int) ($existing['user_id'] ?? 0);
            if ($userId > 0) {
                $pdo->prepare("UPDATE pengguna SET name = ?, nama = ?, is_active = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$nama, $nama, $isActive, $userId]);
            }

            $pdo->commit();

            AuditLog::write((int) $user['id'], 'siswa.update', 'siswa', $id, "Update data siswa ID {$id} ({$nama})", RateLimiter::clientIp());

            $fetchStmt = $pdo->prepare(
                "SELECT s.id, s.user_id, s.class_id, s.kelas_id, s.nis, s.nisn, s.nama_lengkap,
                        s.gender, s.jenis_kelamin, s.birth_place, s.tempat_lahir,
                        s.birth_date, s.tanggal_lahir, s.guardian_name, s.nama_wali,
                        s.guardian_phone, s.address, s.alamat, s.foto_url, s.status, s.is_active,
                        c.name AS class_name, c.nama_kelas, c.level AS class_level,
                        u.email, u.username
                 FROM siswa s
                 LEFT JOIN kelas c ON (c.id = s.class_id OR c.id = s.kelas_id)
                 LEFT JOIN pengguna u ON u.id = s.user_id
                 WHERE s.id = ? LIMIT 1"
            );
            $fetchStmt->execute([$id]);
            $updatedRow = $fetchStmt->fetch();

            ApiResponse::ok($this->formatRow($updatedRow));
        } catch (Throwable $e) {
            $pdo->rollBack();
            ApiResponse::error('SERVER_ERROR', 'Gagal memperbarui siswa: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/siswa/{id}
     */
    public function delete(array $params): void
    {
        $user = $this->requireUser();
        if ($user === null) {
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            ApiResponse::notFound('ID siswa tidak valid.');
            return;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare("SELECT id, user_id, nama_lengkap FROM siswa WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $check->execute([$id]);
        $row = $check->fetch();

        if ($row === false) {
            ApiResponse::notFound('Siswa tidak ditemukan.');
            return;
        }

        $del = $pdo->prepare("UPDATE siswa SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
        $del->execute([$id]);

        if (!empty($row['user_id'])) {
            $pdo->prepare("UPDATE pengguna SET is_active = 0, deleted_at = NOW() WHERE id = ?")->execute([(int) $row['user_id']]);
        }

        AuditLog::write((int) $user['id'], 'siswa.delete', 'siswa', $id, "Hapus data siswa ID {$id} ({$row['nama_lengkap']})", RateLimiter::clientIp());

        ApiResponse::ok(['message' => 'Data siswa berhasil dihapus.']);
    }

    private function formatRow(array $r): array
    {
        $isActive = (int) $r['is_active'] === 1;
        $genderCode = $r['gender'] ?? $r['jenis_kelamin'] ?? 'L';
        $genderLabel = ($genderCode === 'P') ? 'Perempuan' : 'Laki-laki';
        $className = (string) ($r['class_name'] ?? $r['nama_kelas'] ?? '');
        $birthDate = ApiFormat::dateOnly($r['tanggal_lahir'] ?? $r['birth_date']);

        // Format tanggal lahir juga sebagai dd/mm/yyyy jika dibutuhkan oleh form
        $formattedDate = '';
        if ($birthDate !== null && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $birthDate, $m)) {
            $formattedDate = "{$m[3]}/{$m[2]}/{$m[1]}";
        }

        return [
            'id'             => (int) $r['id'],
            'user_id'        => $r['user_id'] !== null ? (int) $r['user_id'] : null,
            'class_id'       => $r['class_id'] !== null ? (int) $r['class_id'] : null,
            'kelas_id'       => $r['class_id'] !== null ? (int) $r['class_id'] : null,
            'nama'           => (string) $r['nama_lengkap'],
            'nama_lengkap'   => (string) $r['nama_lengkap'],
            'nis'            => (string) ($r['nis'] ?? ''),
            'nisn'           => (string) ($r['nisn'] ?? ''),
            'kelas'          => $className,
            'nama_kelas'     => $className,
            'gender'         => $genderCode,
            'jenis_kelamin'  => $genderLabel,
            'tempat_lahir'   => (string) ($r['tempat_lahir'] ?? $r['birth_place'] ?? ''),
            'birth_place'    => (string) ($r['tempat_lahir'] ?? $r['birth_place'] ?? ''),
            'tanggal_lahir'  => $formattedDate !== '' ? $formattedDate : ($birthDate ?? ''),
            'birth_date'     => $birthDate,
            'nama_wali'      => (string) ($r['nama_wali'] ?? $r['guardian_name'] ?? ''),
            'guardian_name'  => (string) ($r['nama_wali'] ?? $r['guardian_name'] ?? ''),
            'no_hp_wali'     => (string) ($r['guardian_phone'] ?? ''),
            'guardian_phone' => (string) ($r['guardian_phone'] ?? ''),
            'alamat'         => (string) ($r['alamat'] ?? $r['address'] ?? ''),
            'address'        => (string) ($r['address'] ?? $r['alamat'] ?? ''),
            'foto_url'       => ApiFormat::mediaUrl($r['foto_url'] ?? null),
            'status'         => (string) ($r['status'] ?? 'aktif'),
            'is_active'      => $isActive ? 1 : 0,
            'aktif'          => $isActive,
        ];
    }
}
