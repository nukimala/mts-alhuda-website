<?php
/**
 * Pemeriksa integritas database — CLI saja.
 *
 *   php tools/db-check.php
 *   php tools/db-check.php --verbose
 *
 * Menjalankan pemeriksaan otomatis terhadap skema yang sudah terbentuk:
 *   1. Inventaris tabel
 *   2. Engine & charset setiap tabel
 *   3. Foreign key terdaftar dan valid
 *   4. Unique index pada kolom kunci
 *   5. Kolom generated (penjaga duplikat)
 *   6. Uji tulis: insert sah diterima, insert melanggar ditolak
 *   7. Uji baca: query utama memakai index, bukan full scan
 *
 * Kode keluar 0 = semua lulus, 1 = ada yang gagal.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya boleh dijalankan dari CLI.\n");
}

$ROOT = dirname(__DIR__);
require $ROOT . '/app/Support/Database.php';

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

$verbose = in_array('--verbose', $argv, true);

$pass = 0;
$fail = 0;
$skip = 0;

function out(string $line = ''): void
{
    echo $line . PHP_EOL;
}

function rule(string $char = '=', int $width = 74): string
{
    return str_repeat($char, $width);
}

function section(string $title): void
{
    out('');
    out(rule());
    out($title);
    out(rule());
}

function ok(string $message): void
{
    global $pass;
    $pass++;
    out('  [LULUS] ' . $message);
}

function bad(string $message): void
{
    global $fail;
    $fail++;
    out('  [GAGAL] ' . $message);
}

function skipped(string $message): void
{
    global $skip;
    $skip++;
    out('  [LEWAT] ' . $message);
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);

    return (int) $stmt->fetchColumn() > 0;
}

try {
    $pdo = Database::connection();
} catch (PDOException $e) {
    fwrite(STDERR, 'Tidak bisa terhubung: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$config = Database::config();
$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$serverVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

out(rule());
out('DB-CHECK — MTs Al-Huda Gondang');
out(rule());
out('  Server   : ' . $serverVersion);
out('  Database : ' . $dbName);
out('  Host     : ' . $config['host'] . ':' . $config['port']);
out('  Waktu    : ' . $pdo->query('SELECT NOW()')->fetchColumn());

// ---------------------------------------------------------------------------
section('1. INVENTARIS TABEL');
// ---------------------------------------------------------------------------

$tables = $pdo->query(
    'SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_COLLATION AS collation,
            TABLE_ROWS AS rows_est
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = "BASE TABLE"
     ORDER BY TABLE_NAME'
)->fetchAll();

if ($tables === []) {
    bad('Tidak ada tabel sama sekali. Jalankan `php tools/migrate.php up`.');
} else {
    out(sprintf('%-30s %-8s %-22s %s', 'TABEL', 'ENGINE', 'COLLATION', 'BARIS'));
    out(rule('-'));
    foreach ($tables as $t) {
        out(sprintf(
            '%-30s %-8s %-22s %s',
            $t['name'],
            $t['engine'] ?? '?',
            $t['collation'] ?? '?',
            $t['rows_est']
        ));
    }
    out(rule('-'));
    out('Total: ' . count($tables) . ' tabel');
    ok(count($tables) . ' tabel ditemukan');
}

// ---------------------------------------------------------------------------
section('2. ENGINE & CHARSET');
// ---------------------------------------------------------------------------

$wrongEngine = [];
$wrongCollation = [];

foreach ($tables as $t) {
    if (strcasecmp((string) $t['engine'], 'InnoDB') !== 0) {
        $wrongEngine[] = $t['name'] . ' (' . $t['engine'] . ')';
    }
    if ((string) $t['collation'] !== 'utf8mb4_unicode_ci') {
        $wrongCollation[] = $t['name'] . ' (' . $t['collation'] . ')';
    }
}

$wrongEngine === []
    ? ok('Semua tabel ENGINE=InnoDB')
    : bad('Engine bukan InnoDB: ' . implode(', ', $wrongEngine));

$wrongCollation === []
    ? ok('Semua tabel COLLATE=utf8mb4_unicode_ci')
    : bad('Collation tidak sesuai: ' . implode(', ', $wrongCollation));

// Kolom yang masih memakai charset non-utf8mb4
$badColumns = $pdo->query(
    'SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_SET_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND CHARACTER_SET_NAME IS NOT NULL
       AND CHARACTER_SET_NAME <> "utf8mb4"'
)->fetchAll();

$badColumns === []
    ? ok('Tidak ada kolom teks dengan charset selain utf8mb4')
    : bad('Kolom non-utf8mb4: ' . count($badColumns) . ' buah');

// ---------------------------------------------------------------------------
section('3. FOREIGN KEY');
// ---------------------------------------------------------------------------

$fks = $pdo->query(
    'SELECT kcu.TABLE_NAME AS tabel, kcu.COLUMN_NAME AS kolom,
            kcu.REFERENCED_TABLE_NAME AS ref_tabel, kcu.REFERENCED_COLUMN_NAME AS ref_kolom,
            rc.DELETE_RULE AS on_delete
     FROM information_schema.KEY_COLUMN_USAGE kcu
     JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
       ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
      AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
     WHERE kcu.TABLE_SCHEMA = DATABASE()
       AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
     ORDER BY kcu.TABLE_NAME, kcu.COLUMN_NAME'
)->fetchAll();

if ($fks === []) {
    skipped('Belum ada foreign key (wajar pada Batch 0)');
} else {
    out(sprintf('%-26s %-22s %-24s %s', 'TABEL', 'KOLOM', 'MENGACU KE', 'ON DELETE'));
    out(rule('-'));
    foreach ($fks as $fk) {
        out(sprintf(
            '%-26s %-22s %-24s %s',
            $fk['tabel'],
            $fk['kolom'],
            $fk['ref_tabel'] . '.' . $fk['ref_kolom'],
            $fk['on_delete']
        ));
    }
    out(rule('-'));
    out('Total: ' . count($fks) . ' foreign key');
    ok(count($fks) . ' foreign key terdaftar di information_schema');
}

// ---------------------------------------------------------------------------
section('4. UNIQUE INDEX');
// ---------------------------------------------------------------------------

$uniques = $pdo->query(
    'SELECT TABLE_NAME AS tabel, INDEX_NAME AS idx,
            GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS kolom
     FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND NON_UNIQUE = 0
       AND INDEX_NAME <> "PRIMARY"
     GROUP BY TABLE_NAME, INDEX_NAME
     ORDER BY TABLE_NAME, INDEX_NAME'
)->fetchAll();

if ($uniques === []) {
    skipped('Belum ada unique index tambahan');
} else {
    out(sprintf('%-26s %-34s %s', 'TABEL', 'INDEX', 'KOLOM'));
    out(rule('-'));
    foreach ($uniques as $u) {
        out(sprintf('%-26s %-34s %s', $u['tabel'], $u['idx'], $u['kolom']));
    }
    out(rule('-'));
    out('Total: ' . count($uniques) . ' unique index');
    ok(count($uniques) . ' unique index aktif');
}

// ---------------------------------------------------------------------------
section('5. KOLOM GENERATED (PENJAGA DUPLIKAT)');
// ---------------------------------------------------------------------------

$generated = $pdo->query(
    'SELECT TABLE_NAME AS tabel, COLUMN_NAME AS kolom, GENERATION_EXPRESSION AS ekspresi
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND GENERATION_EXPRESSION IS NOT NULL
       AND GENERATION_EXPRESSION <> ""
     ORDER BY TABLE_NAME, COLUMN_NAME'
)->fetchAll();

if ($generated === []) {
    skipped('Belum ada generated column (dipakai mulai Batch 3)');
} else {
    out(sprintf('%-26s %-22s %s', 'TABEL', 'KOLOM', 'EKSPRESI'));
    out(rule('-'));
    foreach ($generated as $g) {
        out(sprintf('%-26s %-22s %s', $g['tabel'], $g['kolom'], $g['ekspresi']));
    }
    out(rule('-'));
    ok(count($generated) . ' generated column terpasang');
}

// ---------------------------------------------------------------------------
section('6. UJI TULIS');
// ---------------------------------------------------------------------------

/**
 * Coba jalankan statement yang HARUS gagal.
 */
function expectRejected(PDO $pdo, string $sql, array $params, string $label): void
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        bad($label . ' — seharusnya DITOLAK, tapi berhasil masuk');
    } catch (PDOException $e) {
        ok($label . ' — ditolak (' . substr($e->getMessage(), 0, 60) . '…)');
    }
}

// --- settings -------------------------------------------------------------
if (tableExists($pdo, 'settings')) {
    $probeKey = '__dbcheck_' . bin2hex(random_bytes(4));

    $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value, value_type, group_name)
         VALUES (?, ?, "string", "sistem")'
    )->execute([$probeKey, 'nilai-uji']);

    $read = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $read->execute([$probeKey]);
    $value = $read->fetchColumn();

    $value === 'nilai-uji'
        ? ok('settings: insert lalu baca kembali berhasil')
        : bad('settings: nilai terbaca tidak sesuai (' . var_export($value, true) . ')');

    expectRejected(
        $pdo,
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)',
        [$probeKey, 'duplikat'],
        'settings: setting_key duplikat'
    );

    $pdo->prepare('DELETE FROM settings WHERE setting_key = ?')->execute([$probeKey]);
} else {
    skipped('settings: tabel belum ada');
}

// --- users ----------------------------------------------------------------
if (tableExists($pdo, 'users')) {
    $probeEmail = 'dbcheck_' . bin2hex(random_bytes(4)) . '@uji.local';

    $pdo->prepare(
        'INSERT INTO users (name, email, username, password_hash, role)
         VALUES (?, ?, ?, ?, "operator")'
    )->execute(['Uji DB Check', $probeEmail, 'dbcheck_' . bin2hex(random_bytes(3)), password_hash('uji', PASSWORD_DEFAULT)]);

    $probeId = (int) $pdo->lastInsertId();

    $read = $pdo->prepare('SELECT name, role, is_active, must_change_password FROM users WHERE id = ?');
    $read->execute([$probeId]);
    $row = $read->fetch();

    ($row && $row['name'] === 'Uji DB Check' && $row['role'] === 'operator')
        ? ok('users: insert lalu baca kembali berhasil')
        : bad('users: data terbaca tidak sesuai');

    ($row && (int) $row['must_change_password'] === 1)
        ? ok('users: must_change_password default = 1')
        : bad('users: must_change_password default salah');

    expectRejected(
        $pdo,
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "operator")',
        ['Duplikat', $probeEmail, password_hash('uji2', PASSWORD_DEFAULT)],
        'users: email duplikat'
    );

    expectRejected(
        $pdo,
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "raja")',
        ['Role Salah', 'role_' . bin2hex(random_bytes(4)) . '@uji.local', password_hash('uji3', PASSWORD_DEFAULT)],
        'users: role di luar ENUM'
    );

    // --- teachers FK ---
    if (tableExists($pdo, 'teachers')) {
        $pdo->prepare('INSERT INTO teachers (user_id, nip) VALUES (?, ?)')
            ->execute([$probeId, 'DBCHECK-' . bin2hex(random_bytes(3))]);
        ok('teachers: insert dengan user_id valid berhasil');

        expectRejected(
            $pdo,
            'INSERT INTO teachers (user_id, nip) VALUES (?, ?)',
            [999999999, 'DBCHECK-X'],
            'teachers: user_id tidak ada (FK)'
        );

        $pdo->prepare('DELETE FROM teachers WHERE user_id = ?')->execute([$probeId]);
    }

    // --- students FK ---
    if (tableExists($pdo, 'students')) {
        $pdo->prepare('INSERT INTO students (user_id, nis) VALUES (?, ?)')
            ->execute([$probeId, 'DBCHECK-' . bin2hex(random_bytes(3))]);
        ok('students: insert dengan user_id valid berhasil');

        expectRejected(
            $pdo,
            'INSERT INTO students (user_id, nis) VALUES (?, ?)',
            [999999999, 'DBCHECK-Y'],
            'students: user_id tidak ada (FK)'
        );

        $pdo->prepare('DELETE FROM students WHERE user_id = ?')->execute([$probeId]);
    }

    // --- api_tokens ---
    if (tableExists($pdo, 'api_tokens')) {
        $hash = hash('sha256', random_bytes(32));
        $pdo->prepare(
            'INSERT INTO api_tokens (user_id, access_token_hash, refresh_token_hash, access_expires_at, refresh_expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), DATE_ADD(NOW(), INTERVAL 30 DAY))'
        )->execute([$probeId, $hash, hash('sha256', random_bytes(32))]);

        $start = microtime(true);
        $find = $pdo->prepare('SELECT user_id FROM api_tokens WHERE access_token_hash = ?');
        $find->execute([$hash]);
        $elapsed = (microtime(true) - $start) * 1000;

        $found = $find->fetchColumn();
        ((int) $found === $probeId)
            ? ok(sprintf('api_tokens: lookup token berdasarkan hash — %.2f ms', $elapsed))
            : bad('api_tokens: token tidak ditemukan berdasarkan hash');

        expectRejected(
            $pdo,
            'INSERT INTO api_tokens (user_id, access_token_hash, refresh_token_hash, access_expires_at, refresh_expires_at)
             VALUES (?, ?, ?, NOW(), NOW())',
            [$probeId, $hash, hash('sha256', random_bytes(32))],
            'api_tokens: access_token_hash duplikat'
        );

        $pdo->prepare('DELETE FROM api_tokens WHERE user_id = ?')->execute([$probeId]);
    }

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$probeId]);
    ok('users: data uji dibersihkan');
} else {
    skipped('users: tabel belum ada');
}

// ---------------------------------------------------------------------------
section('7. UJI BACA (EXPLAIN)');
// ---------------------------------------------------------------------------

/**
 * @param array<string,mixed> $params
 */
function explainQuery(PDO $pdo, string $sql, array $params, string $label, bool $verbose): void
{
    // EXPLAIN tidak menerima parameter binding dengan benar di MariaDB 10.4 —
    // hasilnya kolom kosong. Placeholder diganti literal yang sudah di-quote.
    $index = 0;
    $literalSql = preg_replace_callback(
        '/\?/',
        static function () use (&$index, $params, $pdo): string {
            $value = $params[$index] ?? null;
            $index++;
            return $value === null ? 'NULL' : $pdo->quote((string) $value);
        },
        $sql
    );

    try {
        $rows = $pdo->query('EXPLAIN ' . $literalSql)->fetchAll();
    } catch (PDOException $e) {
        skipped($label . ' — ' . $e->getMessage());
        return;
    }

    if ($rows === []) {
        skipped($label . ' — EXPLAIN tidak mengembalikan baris');
        return;
    }

    $extra = (string) ($rows[0]['Extra'] ?? '');

    // Pada tabel kosong MariaDB memotong optimasi dan melaporkan
    // "Impossible WHERE noticed after reading const tables".
    // Itu bukan tanda index tidak dipakai — EXPLAIN belum bisa dinilai.
    if (($rows[0]['table'] ?? null) === null || stripos($extra, 'Impossible WHERE') !== false) {
        skipped($label . ' — tabel masih kosong, EXPLAIN belum bisa dinilai (ulangi setelah ada data)');
        return;
    }

    $type = $rows[0]['type'] ?? '?';
    $key = $rows[0]['key'] ?? null;
    $rowsExamined = $rows[0]['rows'] ?? '?';

    if ($verbose) {
        out('    type=' . $type . '  key=' . ($key ?? 'NULL') . '  rows=' . $rowsExamined);
    }

    if ($key !== null && $key !== '') {
        ok($label . ' — memakai index `' . $key . '` (type=' . $type . ')');
    } elseif ($type === 'ALL') {
        bad($label . ' — FULL SCAN (type=ALL, ' . $rowsExamined . ' baris)');
    } elseif ($type === 'const' || $type === 'system') {
        ok($label . ' — type=' . $type . ' (satu baris, tanpa index perlu)');
    } else {
        bad($label . ' — type=' . $type . ', tidak memakai index');
    }
}

if (tableExists($pdo, 'users')) {
    // Tabel kosong membuat MariaDB memotong optimasi, sehingga EXPLAIN tidak
    // bisa dinilai. Isi data sementara di dalam transaksi, lalu batalkan.
    //
    // Komposisi role dibuat tidak merata: kalau semua baris cocok dengan
    // predikat, full scan memang pilihan yang benar dan bukan tanda index rusak.
    $seededForExplain = false;
    $existing = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($existing < 200) {
        $pdo->beginTransaction();
        $hash = password_hash('uji-explain', PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            'INSERT INTO users (name, email, username, password_hash, role, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $plan = ['siswa' => 300, 'guru' => 80, 'operator' => 20];
        $n = 0;
        foreach ($plan as $role => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $n++;
                $insert->execute([
                    'Uji ' . ucfirst($role) . ' ' . $i,
                    'explain' . $n . '@uji.local',
                    'explain' . $n,
                    $hash,
                    $role,
                    ($n % 10 === 0) ? 0 : 1,
                ]);
            }
        }

        $seededForExplain = true;
        ok('users: 400 baris sementara (300 siswa / 80 guru / 20 operator) di dalam transaksi untuk uji EXPLAIN');
    }

    explainQuery(
        $pdo,
        'SELECT id, name, role FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
        ['explain301@uji.local'],
        'users: login berdasarkan email',
        $verbose
    );

    explainQuery(
        $pdo,
        'SELECT id FROM users WHERE username = ? LIMIT 1',
        ['explain301'],
        'users: login berdasarkan username',
        $verbose
    );

    explainQuery(
        $pdo,
        'SELECT id, name FROM users WHERE role = ? AND is_active = 1',
        ['guru'],
        'users: daftar pengguna per role',
        $verbose
    );

    if ($seededForExplain) {
        $pdo->rollBack();
        ok('users: data sementara dibatalkan (rollback), tidak ada yang tertinggal');
    }
} else {
    skipped('users: tabel belum ada');
}

if (tableExists($pdo, 'settings')) {
    explainQuery(
        $pdo,
        'SELECT setting_value FROM settings WHERE setting_key = ?',
        ['site_name'],
        'settings: baca satu pengaturan',
        $verbose
    );

    explainQuery(
        $pdo,
        'SELECT setting_key, setting_value FROM settings WHERE group_name = ? AND is_public = 1',
        ['umum'],
        'settings: baca grup pengaturan publik',
        $verbose
    );
} else {
    skipped('settings: tabel belum ada');
}

if (tableExists($pdo, 'posts')) {
    // Sama seperti blok users: pada tabel kecil MariaDB memilih full scan
    // karena memang lebih murah, dan itu bukan tanda index rusak. Isi data
    // sementara di dalam transaksi, periksa, lalu batalkan.
    $seededPosts = false;
    $existingPosts = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();

    if ($existingPosts < 200) {
        $pdo->beginTransaction();

        $insertPost = $pdo->prepare(
            "INSERT INTO posts (title, slug, content, status, published_at)
             VALUES (?, ?, 'isi uji', ?, ?)"
        );

        $base = strtotime('2026-01-01 00:00:00');

        for ($i = 1; $i <= 400; $i++) {
            // Satu dari empat berstatus draf, supaya predikat status tidak
            // mencakup seluruh baris — kalau semua cocok, full scan memang benar.
            $status = ($i % 4 === 0) ? 'draft' : 'published';

            $insertPost->execute([
                'Uji ' . $i,
                'uji-explain-' . $i,
                $status,
                date('Y-m-d H:i:s', $base + ($i * 3600)),
            ]);
        }

        $seededPosts = true;
        ok('posts: 400 baris sementara (300 terbit / 100 draf) di dalam transaksi untuk uji EXPLAIN');
    }

    explainQuery(
        $pdo,
        'SELECT id, title, slug FROM posts WHERE status = ? ORDER BY published_at DESC LIMIT 5',
        ['published'],
        'posts: berita terbit terbaru',
        $verbose
    );

    if ($seededPosts) {
        $pdo->rollBack();
        ok('posts: data sementara dibatalkan (rollback), tidak ada yang tertinggal');
    }
} else {
    skipped('posts: tabel belum ada');
}

// ---------------------------------------------------------------------------
section('RINGKASAN');
// ---------------------------------------------------------------------------

out('  Lulus  : ' . $pass);
out('  Gagal  : ' . $fail);
out('  Dilewati: ' . $skip);
out('');

if ($fail === 0) {
    out('  VERDICT: SKEMA SEHAT');
    out('');
    exit(0);
}

out('  VERDICT: ADA ' . $fail . ' MASALAH — periksa bagian [GAGAL] di atas');
out('');
exit(1);
