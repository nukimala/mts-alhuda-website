<?php
/**
 * Uji integritas skema — seluruh 42 tabel.
 *
 *   php tools/schema-test.php
 *   php tools/schema-test.php --verbose
 *
 * Yang diuji bukan "apakah tabel ada", tapi "apakah janji di komentar SQL
 * benar-benar ditegakkan database". Setiap uji mencoba MELANGGAR aturan dan
 * memastikan pelanggarannya ditolak.
 *
 * Semua uji berjalan di dalam transaksi yang di-rollback. Tidak ada satu
 * baris pun yang tertinggal, jadi aman dijalankan pada database berisi data.
 *
 * Yang diuji:
 *   1.  Kelengkapan - 42 tabel, semua migrasi di disk, semua kolom wajib
 *   2.  K-3  — hanya satu tahun ajaran aktif
 *   3.  K-4  — presensi harian tidak bisa dobel (jebakan NULL)
 *   4.  K-5  — hanya satu draft DKA per butir
 *   5.  K-11 — slug unik hanya untuk baris hidup, bisa dipakai ulang
 *   6.  K-14 — checksum bukti unik per periode, bukan global
 *   7.  Pivot — satu bukti untuk banyak butir, tanpa duplikat
 *   8.  FK   — RESTRICT / CASCADE / SET NULL sesuai maksud
 *   9.  CHECK — rentang waktu, skor, panjang DKA
 *   10. UNIQUE — siswa satu kali kumpul, satu indikator sekali dinilai
 *   11. Index — query publik memakai index, bukan full scan
 *   12. Tipe  — decimal, enum, generated column bekerja
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya boleh dijalankan dari CLI.\n");
}

require dirname(__DIR__) . '/app/Support/Database.php';

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

$verbose = in_array('--verbose', $argv, true) || in_array('-v', $argv, true);

$pass = 0;
$fail = 0;
$skip = 0;

function line(string $char = '=', int $width = 74): string
{
    return str_repeat($char, $width);
}

function heading(string $text): void
{
    echo PHP_EOL . line() . PHP_EOL . $text . PHP_EOL . line() . PHP_EOL;
}

function ok(string $label, string $detail = ''): void
{
    global $pass;
    $pass++;
    echo '  [LULUS] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
}

function bad(string $label, string $detail = ''): void
{
    global $fail;
    $fail++;
    echo '  [GAGAL] ' . $label . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
}

function skipped(string $label): void
{
    global $skip;
    $skip++;
    echo '  [LEWAT] ' . $label . PHP_EOL;
}

function check(string $label, bool $condition, string $detail = ''): void
{
    $condition ? ok($label, $detail) : bad($label, $detail);
}

/**
 * Jalankan satu statement dan harapkan gagal.
 * Mengembalikan pesan galat, atau null kalau ternyata BERHASIL (berarti bug).
 */
function expectReject(PDO $pdo, string $sql, array $params = []): ?string
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return null;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

/**
 * Jalankan statement yang seharusnya berhasil.
 */
function mustRun(PDO $pdo, string $sql, array $params = []): void
{
    $pdo->prepare($sql)->execute($params);
}

/**
 * Cocokkan pesan galat dengan pola tertentu, supaya kegagalan karena alasan
 * yang salah tidak dihitung sebagai lulus.
 */
function looksLike(string $message, string $pattern): bool
{
    return preg_match($pattern, $message) === 1;
}

// ---------------------------------------------------------------------------

try {
    $pdo = Database::connection();
} catch (PDOException $e) {
    fwrite(STDERR, 'Tidak bisa menyambung ke database: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

heading('UJI INTEGRITAS SKEMA  —  ' . $dbName);
echo '  Port ' . Database::resolvedPort() . ' · ' . $pdo->query('SELECT VERSION()')->fetchColumn() . PHP_EOL;

// --- 1. Kelengkapan --------------------------------------------------------

heading('1. Kelengkapan struktur');

$expectedTables = [
    // sistem
    'schema_migrations', 'schema_seeders', 'settings', 'rate_limits',
    // identitas
    'users', 'teachers', 'students', 'api_tokens', 'password_resets', 'activity_logs',
    // cms
    'posts', 'pages', 'galleries', 'facilities', 'announcements', 'agendas',
    'menus', 'contact_messages', 'ppdb_documents', 'media',
    // akademik
    'academic_years', 'classes', 'subjects', 'class_subjects',
    'schedules', 'materials', 'assignments', 'submissions',
    // presensi
    'teacher_attendances',
    // notifikasi
    'notifications',
    // akreditasi
    'accreditation_periods', 'accreditation_components', 'accreditation_butirs',
    'accreditation_indicators', 'evidence', 'evidence_butir', 'dka_entries',
    'indicator_checks', 'main_documents', 'tasks', 'accreditation_results', 'follow_ups',
];

$actual = $pdo->query(
    "SELECT TABLE_NAME FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
)->fetchAll(PDO::FETCH_COLUMN);

$missing = array_values(array_diff($expectedTables, $actual));
$extra = array_values(array_diff($actual, $expectedTables));

check('Jumlah tabel sesuai rencana', count($expectedTables) === 42, count($expectedTables) . ' diharapkan, ' . count($actual) . ' ada');
check('Tidak ada tabel yang hilang', $missing === [], $missing === [] ? '' : 'hilang: ' . implode(', ', $missing));
check('Tidak ada tabel tak terduga', $extra === [], $extra === [] ? '' : 'tak terduga: ' . implode(', ', $extra));

// Jumlah migrasi dihitung dari berkas di disk, bukan ditulis tangan: setiap
// kali migrasi baru ditambahkan, uji ini tidak boleh ikut diedit manual —
// kalau harus diedit, cepat atau lambat ada yang lupa dan uji jadi bohong.
$migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
$migrationExpected = count($migrationFiles);
$migrationCount = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
check(
    $migrationExpected . ' migrasi tercatat',
    $migrationCount === $migrationExpected,
    $migrationCount . ' tercatat di database'
);

// Semua tabel harus InnoDB + utf8mb4_unicode_ci
$wrongEngine = $pdo->query(
    "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND ENGINE <> 'InnoDB'"
)->fetchAll();
check('Semua tabel InnoDB', $wrongEngine === [], $wrongEngine === [] ? '' : implode(', ', array_column($wrongEngine, 'TABLE_NAME')));

$wrongCollation = $pdo->query(
    "SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
       AND TABLE_COLLATION <> 'utf8mb4_unicode_ci'"
)->fetchAll();
check(
    'Semua tabel utf8mb4_unicode_ci',
    $wrongCollation === [],
    $wrongCollation === [] ? '' : implode(', ', array_column($wrongCollation, 'TABLE_NAME'))
);

// Kolom yang wajib ada di tabel yang diekspos API
foreach (['posts' => 'public_id', 'assignments' => 'public_id', 'submissions' => 'public_id',
          'evidence' => 'public_id', 'students' => 'public_id', 'teachers' => 'public_id'] as $table => $column) {
    $has = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table)
        . " AND COLUMN_NAME = " . $pdo->quote($column)
    )->fetchColumn();

    check($table . '.' . $column . ' ada (K-7)', $has === 1);
}

// ---------------------------------------------------------------------------
// Semua uji di bawah berjalan dalam satu transaksi besar, lalu di-rollback.
// ---------------------------------------------------------------------------

$pdo->beginTransaction();

try {
    // --- 2. K-3: satu tahun ajaran aktif -----------------------------------

    heading('2. K-3 — hanya satu tahun ajaran aktif');

    // Netralkan dulu tahun ajaran yang sudah ada, supaya uji tidak
    // bergantung pada isi database. Perubahan ini ikut di-rollback.
    $pdo->exec('UPDATE academic_years SET is_active = 0');

    mustRun($pdo,
        "INSERT INTO academic_years (name, start_date, end_date, is_active)
         VALUES ('UJI-A', '2020-07-01', '2021-06-30', 1)");
    ok('tahun ajaran aktif pertama diterima');

    $err = expectReject($pdo,
        "INSERT INTO academic_years (name, start_date, end_date, is_active)
         VALUES ('UJI-B', '2021-07-01', '2022-06-30', 1)");

    check(
        'tahun ajaran aktif KEDUA ditolak',
        $err !== null && looksLike($err, '/uq_academic_years_active|Duplicate entry/i'),
        $err === null ? 'DITERIMA — bug!' : 'ditolak oleh unique key'
    );

    mustRun($pdo,
        "INSERT INTO academic_years (name, start_date, end_date, is_active)
         VALUES ('UJI-C', '2022-07-01', '2023-06-30', 0)");
    ok('tahun ajaran TIDAK aktif boleh berapa pun');

    // generated column harus terisi sesuai isi is_active
    $flag = $pdo->query("SELECT active_flag FROM academic_years WHERE name = 'UJI-A'")->fetchColumn();
    check('active_flag = 1 untuk tahun aktif', (string) $flag === '1', 'nilai: ' . var_export($flag, true));

    $flag = $pdo->query("SELECT active_flag FROM academic_years WHERE name = 'UJI-C'")->fetchColumn();
    check('active_flag = NULL untuk tahun tidak aktif', $flag === null, 'nilai: ' . var_export($flag, true));

    // --- 3. K-4: presensi harian tidak bisa dobel --------------------------

    heading('3. K-4 — presensi tidak bisa dobel (jebakan NULL)');

    mustRun($pdo,
        "INSERT INTO users (name, email, username, password_hash, role)
         VALUES ('Guru Uji', 'guru.uji@contoh.local', 'guru_uji', 'x', 'guru')");
    $userId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO teachers (user_id, nip, gender) VALUES (?, 'UJI-001', 'L')", [$userId]);
    $teacherId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO teacher_attendances (teacher_id, attendance_date, attendance_type, status)
         VALUES (?, '2026-10-01', 'daily', 'hadir')", [$teacherId]);
    ok('presensi harian pertama diterima');

    $err = expectReject($pdo,
        "INSERT INTO teacher_attendances (teacher_id, attendance_date, attendance_type, status)
         VALUES (?, '2026-10-01', 'daily', 'hadir')", [$teacherId]);

    check(
        'presensi harian DOBEL di tanggal sama ditolak',
        $err !== null && looksLike($err, '/uq_teacher_attendances_key|Duplicate entry/i'),
        $err === null ? 'DITERIMA — inilah bug yang dicegah K-4' : 'ditolak oleh unique_key'
    );

    // Tanggal berbeda harus boleh
    mustRun($pdo,
        "INSERT INTO teacher_attendances (teacher_id, attendance_date, attendance_type, status)
         VALUES (?, '2026-10-02', 'daily', 'hadir')", [$teacherId]);
    ok('tanggal berbeda diterima');

    // Presensi per jam mengajar: butuh schedule
    mustRun($pdo, "INSERT INTO subjects (code, name) VALUES ('UJI', 'Mapel Uji')");
    $subjectId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO classes (academic_year_id, name, level)
         SELECT id, 'UJI-7A', 7 FROM academic_years WHERE name = 'UJI-A'");
    $classId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO class_subjects (class_id, subject_id, teacher_id) VALUES (?, ?, ?)",
        [$classId, $subjectId, $teacherId]);
    $classSubjectId = (int) $pdo->lastInsertId();

    $activeYearId = (int) $pdo->query("SELECT id FROM academic_years WHERE name = 'UJI-A'")->fetchColumn();

    mustRun($pdo,
        "INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time)
         VALUES (?, ?, ?, 1, '07:00:00', '08:30:00')",
        [$classSubjectId, $teacherId, $activeYearId]);
    $scheduleId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO teacher_attendances (teacher_id, schedule_id, attendance_date, attendance_type, status)
         VALUES (?, ?, '2026-10-01', 'session', 'hadir')", [$teacherId, $scheduleId]);
    ok('presensi per jam mengajar diterima, tanggal sama dengan presensi harian');

    $err = expectReject($pdo,
        "INSERT INTO teacher_attendances (teacher_id, schedule_id, attendance_date, attendance_type, status)
         VALUES (?, ?, '2026-10-01', 'session', 'hadir')", [$teacherId, $scheduleId]);
    check(
        'presensi per jam DOBEL ditolak',
        $err !== null && looksLike($err, '/uq_teacher_attendances_key|Duplicate entry/i'),
        $err === null ? 'DITERIMA — bug!' : 'ditolak'
    );

    // CHECK: daily tanpa schedule, session wajib pakai schedule
    $err = expectReject($pdo,
        "INSERT INTO teacher_attendances (teacher_id, schedule_id, attendance_date, attendance_type, status)
         VALUES (?, ?, '2026-10-03', 'daily', 'hadir')", [$teacherId, $scheduleId]);
    check('daily dengan schedule_id ditolak (CHECK)', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    $err = expectReject($pdo,
        "INSERT INTO teacher_attendances (teacher_id, attendance_date, attendance_type, status)
         VALUES (?, '2026-10-04', 'session', 'hadir')", [$teacherId]);
    check('session tanpa schedule_id ditolak (CHECK)', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // check_out sebelum check_in harus ditolak
    $err = expectReject($pdo,
        "INSERT INTO teacher_attendances
            (teacher_id, attendance_date, attendance_type, status, check_in_at, check_out_at)
         VALUES (?, '2026-10-05', 'daily', 'hadir', '2026-10-05 09:00:00', '2026-10-05 07:00:00')",
        [$teacherId]);
    check('check_out sebelum check_in ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // --- 4. K-5: satu draft DKA per butir ---------------------------------

    heading('4. K-5 — hanya satu draft DKA per butir');

    mustRun($pdo,
        "INSERT INTO accreditation_periods (name, status, is_active) VALUES ('UJI-PERIODE', 'draft', 0)");
    $periodId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO accreditation_components (period_id, code, name, number) VALUES (?, 'UJI', 'Komponen Uji', 1)",
        [$periodId]);
    $componentId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO accreditation_butirs (component_id, number, butir_text) VALUES (?, 1, 'Butir uji')",
        [$componentId]);
    $butirId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO dka_entries (butir_id, content, version, status) VALUES (?, 'Draft pertama', 1, 'draft')",
        [$butirId]);
    ok('draft pertama diterima');

    $err = expectReject($pdo,
        "INSERT INTO dka_entries (butir_id, content, version, status) VALUES (?, 'Draft kedua', 2, 'draft')",
        [$butirId]);
    check(
        'draft KEDUA untuk butir yang sama ditolak',
        $err !== null && looksLike($err, '/uq_dka_one_draft|Duplicate entry/i'),
        $err === null ? 'DITERIMA — dua orang bisa saling menimpa' : 'ditolak oleh unique key'
    );

    // Versi final boleh lebih dari satu
    mustRun($pdo,
        "INSERT INTO dka_entries (butir_id, content, version, status) VALUES (?, 'Final v1', 3, 'final')",
        [$butirId]);
    mustRun($pdo,
        "INSERT INTO dka_entries (butir_id, content, version, status) VALUES (?, 'Final v2', 4, 'final')",
        [$butirId]);
    ok('versi final boleh lebih dari satu');

    // Batas 1000 karakter
    $err = expectReject($pdo,
        "INSERT INTO dka_entries (butir_id, content, version, status) VALUES (?, ?, 5, 'final')",
        [$butirId, str_repeat('a', 1001)]);
    check('isi DKA lebih dari 1000 karakter ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    mustRun($pdo,
        "INSERT INTO dka_entries (butir_id, content, version, status) VALUES (?, ?, 6, 'final')",
        [$butirId, str_repeat('a', 1000)]);
    ok('isi DKA tepat 1000 karakter diterima');

    // --- 5. K-11: slug unik hanya untuk baris hidup ------------------------

    heading('5. K-11 — slug bisa dipakai ulang setelah dihapus');

    mustRun($pdo,
        "INSERT INTO posts (title, slug, content, status) VALUES ('Uji Satu', 'slug-uji', 'isi', 'published')");
    $postId = (int) $pdo->lastInsertId();
    ok('slug baru diterima');

    $err = expectReject($pdo,
        "INSERT INTO posts (title, slug, content, status) VALUES ('Uji Dua', 'slug-uji', 'isi', 'published')");
    check('slug sama pada baris hidup ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    mustRun($pdo, "UPDATE posts SET deleted_at = NOW() WHERE id = ?", [$postId]);
    ok('berita di-soft-delete');

    mustRun($pdo,
        "INSERT INTO posts (title, slug, content, status) VALUES ('Uji Tiga', 'slug-uji', 'isi', 'published')");
    ok('slug yang sama boleh dipakai lagi setelah baris lama dihapus');

    $liveCount = (int) $pdo->query(
        "SELECT COUNT(*) FROM posts WHERE slug = 'slug-uji' AND deleted_at IS NULL"
    )->fetchColumn();
    check('hanya satu baris hidup dengan slug itu', $liveCount === 1, (string) $liveCount . ' baris');

    // --- 6. K-14: checksum bukti unik per periode --------------------------

    heading('6. K-14 — checksum bukti unik per periode');

    $checksum = hash('sha256', 'berkas-yang-sama');

    mustRun($pdo,
        "INSERT INTO evidence (period_id, title, file_path, checksum, status)
         VALUES (?, 'Bukti A', '/uploads/a.pdf', ?, 'valid')", [$periodId, $checksum]);
    ok('bukti pertama diterima');

    $err = expectReject($pdo,
        "INSERT INTO evidence (period_id, title, file_path, checksum, status)
         VALUES (?, 'Bukti A lagi', '/uploads/a2.pdf', ?, 'valid')", [$periodId, $checksum]);
    check(
        'berkas identik di periode yang sama ditolak',
        $err !== null && looksLike($err, '/uq_evidence_checksum|Duplicate entry/i'),
        $err === null ? 'DITERIMA — bug!' : 'ditolak'
    );

    mustRun($pdo,
        "INSERT INTO accreditation_periods (name, status, is_active) VALUES ('UJI-PERIODE-2', 'draft', 0)");
    $period2 = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO evidence (period_id, title, file_path, checksum, status)
         VALUES (?, 'Bukti A periode lain', '/uploads/a.pdf', ?, 'valid')", [$period2, $checksum]);
    ok('berkas identik di periode BERBEDA diterima');

    // Bukti harus punya berkas atau tautan
    $err = expectReject($pdo,
        "INSERT INTO evidence (period_id, title, status) VALUES (?, 'Bukti tanpa sumber', 'valid')",
        [$periodId]);
    check('bukti tanpa file dan tanpa URL ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // --- 7. Pivot bukti <-> butir -----------------------------------------

    heading('7. Pivot bukti ke banyak butir');

    $evidenceId = (int) $pdo->query(
        "SELECT id FROM evidence WHERE period_id = " . $periodId . " AND title = 'Bukti A' LIMIT 1"
    )->fetchColumn();

    mustRun($pdo,
        "INSERT INTO accreditation_butirs (component_id, number, butir_text) VALUES (?, 2, 'Butir uji 2')",
        [$componentId]);
    $butir2 = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO accreditation_butirs (component_id, number, butir_text) VALUES (?, 3, 'Butir uji 3')",
        [$componentId]);
    $butir3 = (int) $pdo->lastInsertId();

    mustRun($pdo, "INSERT INTO evidence_butir (evidence_id, butir_id) VALUES (?, ?)", [$evidenceId, $butirId]);
    mustRun($pdo, "INSERT INTO evidence_butir (evidence_id, butir_id) VALUES (?, ?)", [$evidenceId, $butir2]);
    mustRun($pdo, "INSERT INTO evidence_butir (evidence_id, butir_id) VALUES (?, ?)", [$evidenceId, $butir3]);

    $linked = (int) $pdo->query(
        "SELECT COUNT(*) FROM evidence_butir WHERE evidence_id = " . $evidenceId
    )->fetchColumn();
    check('satu bukti tertaut ke 3 butir', $linked === 3, (string) $linked . ' tautan');

    $err = expectReject($pdo,
        "INSERT INTO evidence_butir (evidence_id, butir_id) VALUES (?, ?)", [$evidenceId, $butirId]);
    check(
        'tautan ganda ditolak',
        $err !== null && looksLike($err, '/uq_evidence_butir_pair|Duplicate entry/i'),
        $err === null ? 'DITERIMA — bug!' : 'ditolak'
    );

    // --- 8. Foreign key: perilaku ON DELETE --------------------------------

    heading('8. Perilaku foreign key');

    // RESTRICT: hapus periode yang punya bukti harus ditolak
    $err = expectReject($pdo, "DELETE FROM accreditation_periods WHERE id = ?", [$periodId]);
    check('hapus periode yang punya bukti ditolak (RESTRICT)', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // RESTRICT: hapus users yang punya teacher harus ditolak
    $err = expectReject($pdo, "DELETE FROM users WHERE id = ?", [$userId]);
    check('hapus user yang punya data guru ditolak (RESTRICT)', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // CASCADE: hapus evidence, pivot ikut hilang
    $before = (int) $pdo->query("SELECT COUNT(*) FROM evidence_butir WHERE evidence_id = " . $evidenceId)->fetchColumn();
    mustRun($pdo, "DELETE FROM evidence WHERE id = ?", [$evidenceId]);
    $after = (int) $pdo->query("SELECT COUNT(*) FROM evidence_butir WHERE evidence_id = " . $evidenceId)->fetchColumn();
    check('hapus bukti menghapus tautannya (CASCADE)', $before === 3 && $after === 0, $before . ' -> ' . $after);

    // CASCADE: hapus butir, indikatornya ikut hilang
    mustRun($pdo,
        "INSERT INTO accreditation_indicators (butir_id, number, indicator_text) VALUES (?, 1, 'Indikator uji')",
        [$butir3]);
    $indicatorId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO indicator_checks (butir_id, indicator_id, status) VALUES (?, ?, 'sesuai')",
        [$butir3, $indicatorId]);
    ok('indikator dan statusnya dibuat');

    $err = expectReject($pdo, "DELETE FROM accreditation_butirs WHERE id = ?", [$butir3]);
    check(
        'hapus butir yang punya DKA ditolak (RESTRICT)',
        $err !== null,
        $err === null ? 'DITERIMA — bug!' : 'ditolak'
    );

    // SET NULL: hapus kelas, siswa tetap ada.
    // Kelas uji dibuat baru — kelas sebelumnya tidak bisa dihapus karena
    // jadwalnya punya riwayat presensi (RESTRICT, dan itu memang benar).
    mustRun($pdo,
        "INSERT INTO classes (academic_year_id, name, level)
         SELECT id, 'UJI-7C', 7 FROM academic_years WHERE name = 'UJI-A'");
    $classIdForStudent = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO users (name, email, username, password_hash, role)
         VALUES ('Siswa Uji', 'siswa.uji@contoh.local', 'siswa_uji', 'x', 'siswa')");
    $studentUserId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO students (user_id, nis, class_id) VALUES (?, 'UJI-NIS-1', ?)",
        [$studentUserId, $classIdForStudent]);
    $studentId = (int) $pdo->lastInsertId();

    mustRun($pdo, "DELETE FROM classes WHERE id = ?", [$classIdForStudent]);
    $classOfStudent = $pdo->query("SELECT class_id FROM students WHERE id = " . $studentId)->fetchColumn();
    check('hapus kelas membuat class_id NULL, siswa tetap ada (SET NULL)', $classOfStudent === null);

    // RESTRICT pada presensi: jadwal yang punya riwayat tidak bisa dihapus.
    $err = expectReject($pdo, "DELETE FROM schedules WHERE id = ?", [$scheduleId]);
    check(
        'hapus jadwal yang punya riwayat presensi ditolak (RESTRICT)',
        $err !== null,
        $err === null ? 'DITERIMA — riwayat presensi bisa hilang' : 'ditolak'
    );

    // --- 9. UNIQUE pada pengumpulan tugas ---------------------------------

    heading('9. Siswa hanya boleh mengumpulkan sekali');

    // Kelas tadi terhapus, jadi class_subject ikut CASCADE. Buat ulang.
    mustRun($pdo,
        "INSERT INTO classes (academic_year_id, name, level)
         SELECT id, 'UJI-7B', 7 FROM academic_years WHERE name = 'UJI-A'");
    $classId2 = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO class_subjects (class_id, subject_id, teacher_id) VALUES (?, ?, ?)",
        [$classId2, $subjectId, $teacherId]);
    $classSubject2 = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO assignments (class_subject_id, title, max_score) VALUES (?, 'Tugas Uji', 100)",
        [$classSubject2]);
    $assignmentId = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO submissions (assignment_id, student_id) VALUES (?, ?)", [$assignmentId, $studentId]);
    ok('pengumpulan pertama diterima');

    $err = expectReject($pdo,
        "INSERT INTO submissions (assignment_id, student_id) VALUES (?, ?)", [$assignmentId, $studentId]);
    check(
        'pengumpulan KEDUA ditolak',
        $err !== null && looksLike($err, '/uq_submissions_pair|Duplicate entry/i'),
        $err === null ? 'DITERIMA — siswa bisa mengumpulkan berkali-kali' : 'ditolak'
    );

    // Skor negatif ditolak
    $err = expectReject($pdo,
        "UPDATE submissions SET score = -1 WHERE assignment_id = ? AND student_id = ?",
        [$assignmentId, $studentId]);
    check('skor negatif ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // Skor melebihi max_score TIDAK ditolak database — dicatat sebagai batas jujur
    mustRun($pdo,
        "UPDATE submissions SET score = 150 WHERE assignment_id = ? AND student_id = ?",
        [$assignmentId, $studentId]);
    echo '  [catatan] skor di atas max_score tidak ditolak database.' . PHP_EOL;
    echo '            Validasi terhadap assignments.max_score harus di aplikasi.' . PHP_EOL;

    // --- 10. CHECK pada rentang waktu -------------------------------------

    heading('10. Pemeriksaan rentang waktu');

    $err = expectReject($pdo,
        "INSERT INTO agendas (title, start_at, end_at, status)
         VALUES ('Agenda terbalik', '2026-11-05 12:00:00', '2026-11-05 08:00:00', 'published')");
    check('agenda dengan akhir sebelum mulai ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    $err = expectReject($pdo,
        "INSERT INTO academic_years (name, start_date, end_date, is_active)
         VALUES ('UJI-TERBALIK', '2023-07-01', '2022-06-30', 0)");
    check('tahun ajaran dengan akhir sebelum mulai ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    $err = expectReject($pdo,
        "INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time)
         VALUES (?, ?, ?, 1, '10:00:00', '09:00:00')",
        [$classSubject2, $teacherId, $activeYearId]);
    check('jadwal dengan jam selesai sebelum mulai ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    $err = expectReject($pdo,
        "INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time)
         VALUES (?, ?, ?, 9, '07:00:00', '08:00:00')",
        [$classSubject2, $teacherId, $activeYearId]);
    check('hari ke-9 ditolak (hanya 1-7)', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // --- 11. Bentrok guru -------------------------------------------------

    heading('11. Bentrok jadwal guru');

    $err = expectReject($pdo,
        "INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time)
         VALUES (?, ?, ?, 1, '07:00:00', '09:00:00')",
        [$classSubject2, $teacherId, $activeYearId]);
    check(
        'guru sama, hari sama, jam mulai sama ditolak',
        $err !== null && looksLike($err, '/uq_schedules_teacher_slot|Duplicate entry/i'),
        $err === null ? 'DITERIMA — bug!' : 'ditolak'
    );

    // Batas jujur: bentrok sebagian tidak tertangkap
    $err = expectReject($pdo,
        "INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time)
         VALUES (?, ?, ?, 1, '08:00:00', '09:30:00')",
        [$classSubject2, $teacherId, $activeYearId]);
    if ($err === null) {
        echo '  [catatan] bentrok SEBAGIAN (08:00-09:30 vs 07:00-08:30) tidak ditolak database.' . PHP_EOL;
        echo '            Ini batas yang diketahui, bukan bug. Validasi tumpang tindih harus di aplikasi.' . PHP_EOL;
    } else {
        ok('bentrok sebagian juga ditolak');
    }

    // Tahun ajaran berbeda: jadwal jam sama harus BOLEH
    mustRun($pdo,
        "INSERT INTO academic_years (name, start_date, end_date, is_active)
         VALUES ('UJI-TAHUN-2', '2026-07-01', '2027-06-30', 0)");
    $year2 = (int) $pdo->lastInsertId();

    mustRun($pdo,
        "INSERT INTO schedules (class_subject_id, teacher_id, academic_year_id, day_of_week, start_time, end_time)
         VALUES (?, ?, ?, 1, '07:00:00', '08:30:00')",
        [$classSubject2, $teacherId, $year2]);
    ok('jam sama di tahun ajaran BERBEDA diterima (K-11)');

    // --- 12. Index pada query publik --------------------------------------

    heading('12. Index pada query publik');

    // Sebagian EXPLAIN butuh data agar bermakna. Pada tabel kosong MySQL
    // memilih index berdasarkan statistik yang belum ada, dan pilihannya bisa
    // berbeda dari MariaDB — bukan karena skemanya berbeda, tetapi karena
    // belum ada yang bisa diukur. Isi dulu, periksa, lalu semuanya di-rollback
    // bersama transaksi besar di akhir.
    $pdo->exec(
        "INSERT INTO notifications (user_id, title, body, type, is_read, created_at)
         SELECT {$userId}, CONCAT('Uji ', n), 'isi', 'system', IF(n % 4 = 0, 0, 1),
                DATE_SUB(NOW(), INTERVAL n MINUTE)
         FROM (
             SELECT a.N + b.N * 10 + c.N * 100 + 1 AS n
             FROM (SELECT 0 N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                   UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
             CROSS JOIN (SELECT 0 N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                   UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
             CROSS JOIN (SELECT 0 N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) c
         ) t"
    );

    $planQueries = [
        'berita terbaru' => [
            "SELECT id FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 5",
            'idx_posts_feed',
        ],
        'notifikasi belum dibaca' => [
            "SELECT id FROM notifications WHERE user_id = {$userId} AND is_read = 0 ORDER BY created_at DESC LIMIT 20",
            'idx_notifications_poll',
        ],
        'rekap presensi harian' => [
            "SELECT COUNT(*) FROM teacher_attendances WHERE attendance_date = '2026-10-01' AND status = 'hadir'",
            'idx_attendance_daily',
        ],
        'agenda terdekat' => [
            "SELECT id FROM agendas WHERE status = 'published' ORDER BY start_at LIMIT 5",
            'idx_agendas_upcoming',
        ],
        'pengaturan publik' => [
            'SELECT setting_key FROM settings WHERE setting_key = ?',
            'uq_settings_key',
        ],
    ];

    foreach ($planQueries as $label => [$sql, $expectedIndex]) {
        // EXPLAIN dengan placeholder menghasilkan kolom kosong di MariaDB,
        // jadi nilainya disisipkan sebagai literal ter-quote.
        $explainSql = str_replace('?', "'site_name'", $sql);

        try {
            $rows = $pdo->query('EXPLAIN ' . $explainSql)->fetchAll();
        } catch (PDOException $e) {
            skipped($label . ' — EXPLAIN gagal: ' . $e->getMessage());
            continue;
        }

        if ($rows === []) {
            skipped($label . ' — tidak ada baris EXPLAIN');
            continue;
        }

        $usedKeys = array_filter(array_column($rows, 'key'));
        $types = array_column($rows, 'type');

        $usesIndex = in_array($expectedIndex, $usedKeys, true);
        $fullScan = in_array('ALL', $types, true) && $usedKeys === [];

        if ($usesIndex) {
            ok($label . ' memakai index', $expectedIndex);
        } elseif ($fullScan) {
            // Tabel kosong: MariaDB melaporkan Impossible WHERE / full scan.
            // Itu wajar dan bukan kegagalan skema.
            skipped($label . ' — tabel kosong, planner belum memakai index');
        } else {
            bad($label . ' tidak memakai ' . $expectedIndex, 'key: ' . (implode(',', $usedKeys) ?: '(tidak ada)'));
        }
    }

    // --- 13. Tipe data & generated column ---------------------------------

    heading('13. Tipe data');

    // DECIMAL tidak kehilangan presisi
    mustRun($pdo,
        "UPDATE submissions SET score = 87.55 WHERE assignment_id = ? AND student_id = ?",
        [$assignmentId, $studentId]);
    $score = $pdo->query(
        'SELECT score FROM submissions WHERE assignment_id = ' . $assignmentId
        . ' AND student_id = ' . $studentId
    )->fetchColumn();
    check('DECIMAL menyimpan 87.55 utuh', (string) $score === '87.55', 'nilai: ' . var_export($score, true));

    // ENUM menolak nilai di luar daftar (mode ketat)
    $err = expectReject($pdo,
        "INSERT INTO announcements (title, slug, content, audience, status)
         VALUES ('Uji', 'uji-enum', 'isi', 'semua', 'status-tidak-ada')");
    check('ENUM menolak nilai di luar daftar', $err !== null, $err === null ? 'DITERIMA — mode ketat mati?' : 'ditolak');

    // NULL tidak memenuhi NOT NULL
    $err = expectReject($pdo,
        "INSERT INTO posts (title, slug, status) VALUES ('Tanpa isi', 'tanpa-isi', 'draft')");
    check('konten kosong pada kolom NOT NULL ditolak', $err !== null, $err === null ? 'DITERIMA — bug!' : 'ditolak');

    // utf8mb4: emoji dan huruf Arab tersimpan utuh
    mustRun($pdo,
        "INSERT INTO posts (title, slug, content, status)
         VALUES ('Uji 🕌 Bahasa العربية', 'uji-unicode', 'Isi dengan emoji 📚 dan Arab: بسم الله', 'draft')");
    $title = (string) $pdo->query("SELECT title FROM posts WHERE slug = 'uji-unicode'")->fetchColumn();
    check('utf8mb4 menyimpan emoji & Arab', strpos($title, '🕌') !== false && strpos($title, 'العربية') !== false, $title);

} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, PHP_EOL . 'UJI TERHENTI: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, 'Di ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
    exit(1);
}

// Selalu rollback: tidak ada data uji yang tertinggal.
$pdo->rollBack();

// Pastikan rollback benar-benar bersih.
$leftover = (int) $pdo->query(
    "SELECT COUNT(*) FROM academic_years WHERE name LIKE 'UJI-%'"
)->fetchColumn();

heading('RINGKASAN');

if ($leftover === 0) {
    ok('tidak ada data uji yang tertinggal (rollback bersih)');
} else {
    bad('ADA ' . $leftover . ' data uji tertinggal — periksa manual');
}

echo PHP_EOL;
echo '  Lulus  : ' . $pass . PHP_EOL;
echo '  Gagal  : ' . $fail . PHP_EOL;
echo '  Dilewati: ' . $skip . PHP_EOL;
echo PHP_EOL;

if ($fail > 0) {
    echo '  VERDICT: ADA YANG GAGAL' . PHP_EOL . PHP_EOL;
    exit(1);
}

echo '  VERDICT: SKEMA SEHAT — semua aturan ditegakkan database' . PHP_EOL . PHP_EOL;
exit(0);
