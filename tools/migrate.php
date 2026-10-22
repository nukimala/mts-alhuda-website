<?php
/**
 * Migration runner — CLI saja.
 *
 *   php tools/migrate.php status
 *   php tools/migrate.php db:create
 *   php tools/migrate.php up
 *   php tools/migrate.php up --step=1
 *   php tools/migrate.php down --step=1
 *   php tools/migrate.php fresh --force
 *   php tools/migrate.php seed
 *   php tools/migrate.php seed --admin
 *
 * Format file migrasi: satu file berisi bagian "-- @UP" dan "-- @DOWN".
 * Setiap file dijalankan statement demi statement. Kalau satu statement gagal,
 * runner berhenti dan melaporkan posisi persisnya (DDL MariaDB tidak transaksional).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya boleh dijalankan dari CLI.\n");
}

$ROOT = dirname(__DIR__);
require $ROOT . '/app/Support/Database.php';

// Waktu sistem ini WIB. Harus sama dengan time_zone sesi MySQL ('+07:00').
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

const MIGRATION_DIR = 'database/migrations';
const SEEDER_DIR    = 'database/seeders';

function out(string $line = ''): void
{
    echo $line . PHP_EOL;
}

function fail(string $message): void
{
    fwrite(STDERR, PHP_EOL . 'GAGAL: ' . $message . PHP_EOL);
    exit(1);
}

function rule(string $char = '=', int $width = 72): string
{
    return str_repeat($char, $width);
}

/**
 * @return array{command:string, options:array<string,string|bool>}
 */
function parseArgs(array $argv): array
{
    $command = $argv[1] ?? 'help';
    $options = [];

    for ($i = 2; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if (strncmp($arg, '--', 2) !== 0) {
            continue;
        }
        $arg = substr($arg, 2);
        if (strpos($arg, '=') !== false) {
            [$k, $v] = explode('=', $arg, 2);
            $options[$k] = $v;
        } else {
            $options[$arg] = true;
        }
    }

    return ['command' => $command, 'options' => $options];
}

/**
 * Pisahkan isi SQL menjadi statement tunggal.
 * Menghormati string ('...'), identifier ("..." dan `...`), serta komentar.
 *
 * @return list<string>
 */
function splitSql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);

    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $length) ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($ch === "\n") {
                $inLineComment = false;
                $buffer .= $ch;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($ch === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if ($inSingle) {
            $buffer .= $ch;
            if ($ch === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
                continue;
            }
            if ($ch === "'") {
                if ($next === "'") {
                    $buffer .= $next;
                    $i++;
                    continue;
                }
                $inSingle = false;
            }
            continue;
        }

        if ($inDouble) {
            $buffer .= $ch;
            if ($ch === '\\' && $next !== '') {
                $buffer .= $next;
                $i++;
                continue;
            }
            if ($ch === '"') {
                if ($next === '"') {
                    $buffer .= $next;
                    $i++;
                    continue;
                }
                $inDouble = false;
            }
            continue;
        }

        if ($inBacktick) {
            $buffer .= $ch;
            if ($ch === '`') {
                if ($next === '`') {
                    $buffer .= $next;
                    $i++;
                    continue;
                }
                $inBacktick = false;
            }
            continue;
        }

        if ($ch === '-' && $next === '-') {
            $inLineComment = true;
            $i++;
            continue;
        }
        if ($ch === '#') {
            $inLineComment = true;
            continue;
        }
        if ($ch === '/' && $next === '*') {
            $inBlockComment = true;
            $i++;
            continue;
        }
        if ($ch === "'") {
            $inSingle = true;
            $buffer .= $ch;
            continue;
        }
        if ($ch === '"') {
            $inDouble = true;
            $buffer .= $ch;
            continue;
        }
        if ($ch === '`') {
            $inBacktick = true;
            $buffer .= $ch;
            continue;
        }
        if ($ch === ';') {
            $trimmed = trim($buffer);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $trimmed = trim($buffer);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}

/**
 * Ambil bagian @UP saja dari berkas seeder.
 *
 * Seeder tidak punya mekanisme rollback otomatis seperti migrasi. Tapi
 * menuliskan bagian @DOWN di dalamnya tetap berguna sebagai dokumentasi
 * "cara membersihkan data contoh ini". Tanpa fungsi ini, splitSql() akan
 * mengeksekusi bagian @DOWN juga — dan data yang baru dimasukkan langsung
 * terhapus dalam perintah yang sama. Ini pernah terjadi; hasilnya seeder
 * melaporkan sukses padahal tabel tetap kosong.
 *
 * Kalau berkas tidak punya penanda @UP, seluruh isinya dipakai apa adanya.
 */
function seederUpSection(string $raw, string $label): string
{
    if (!preg_match('/^[ \t]*--[ \t]*@UP[ \t]*$/mi', $raw)) {
        return $raw;
    }

    try {
        $sections = parseMigrationFile($label);
    } catch (RuntimeException $e) {
        return $raw;
    }

    return $sections['up'];
}

/**
 * @return array{up:string, down:string}
 */
function parseMigrationFile(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Tidak bisa membaca berkas: ' . $path);
    }

    if (!preg_match_all('/^[ \t]*--[ \t]*@(UP|DOWN)[ \t]*$/mi', $raw, $matches, PREG_OFFSET_CAPTURE)) {
        throw new RuntimeException('Berkas tanpa penanda -- @UP / -- @DOWN: ' . $path);
    }

    $sections = ['up' => '', 'down' => ''];
    $marks = $matches[0];
    $kinds = $matches[1];
    $count = count($marks);

    for ($i = 0; $i < $count; $i++) {
        $start = $marks[$i][1] + strlen($marks[$i][0]);
        $end = ($i + 1 < $count) ? $marks[$i + 1][1] : strlen($raw);
        $key = strtolower($kinds[$i][0]);
        $sections[$key] .= substr($raw, $start, $end - $start);
    }

    return $sections;
}

/**
 * @return list<array{version:string, name:string, file:string, path:string}>
 */
function migrationFiles(string $root): array
{
    $dir = $root . '/' . MIGRATION_DIR;
    if (!is_dir($dir)) {
        return [];
    }

    $files = glob($dir . '/*.sql') ?: [];
    sort($files, SORT_NATURAL);

    $list = [];
    foreach ($files as $path) {
        $base = basename($path, '.sql');
        if (!preg_match('/^(\d+)_(.+)$/', $base, $m)) {
            throw new RuntimeException('Nama berkas migrasi tidak valid: ' . basename($path));
        }
        $list[] = [
            'version' => $m[1],
            'name'    => $m[2],
            'file'    => basename($path),
            'path'    => $path,
        ];
    }

    return $list;
}

/**
 * @param bool $includeDemo ikutkan seeder contoh di database/seeders/demo/
 * @return list<array{file:string, path:string}>
 */
function seederFiles(string $root, bool $includeDemo = false): array
{
    $dir = $root . '/' . SEEDER_DIR;
    if (!is_dir($dir)) {
        return [];
    }

    $files = glob($dir . '/*.sql') ?: [];

    // Seeder contoh dipisah di subfolder `demo/` dan hanya ikut kalau diminta.
    // Alasannya sederhana: `seed` dipakai juga di server sungguhan, dan data
    // contoh di sana bukan sekadar berisik — bisa tertukar dengan data asli.
    if ($includeDemo) {
        $demoDir = $dir . '/demo';
        if (is_dir($demoDir)) {
            $files = array_merge($files, glob($demoDir . '/*.sql') ?: []);
        }
    }

    sort($files, SORT_NATURAL);

    return array_map(
        static fn (string $p): array => ['file' => basename($p), 'path' => $p],
        $files
    );
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

/**
 * @return array<string,int> version => batch
 */
function appliedMigrations(PDO $pdo): array
{
    if (!tableExists($pdo, 'schema_migrations')) {
        return [];
    }

    $rows = $pdo->query('SELECT version, batch FROM schema_migrations')->fetchAll();
    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['version']] = (int) $row['batch'];
    }

    return $applied;
}

/**
 * Jalankan sekumpulan statement pada satu berkas migrasi.
 * Berhenti di statement pertama yang gagal dan laporkan posisinya.
 */
function runStatements(PDO $pdo, array $statements, string $label): int
{
    $executed = 0;
    $total = count($statements);

    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            $pos = $index + 1;
            $preview = preg_replace('/\s+/', ' ', $statement);
            $preview = mb_substr($preview, 0, 160);

            fwrite(STDERR, PHP_EOL . '  Statement ' . $pos . '/' . $total . ' gagal di ' . $label . PHP_EOL);
            fwrite(STDERR, '  Potongan SQL: ' . $preview . PHP_EOL);
            fwrite(STDERR, '  Pesan: ' . $e->getMessage() . PHP_EOL);
            fwrite(STDERR, PHP_EOL . '  ' . $executed . ' statement sebelumnya SUDAH dijalankan.' . PHP_EOL);
            fwrite(STDERR, '  MariaDB tidak mendukung DDL transaksional — periksa manual sebelum mengulang.' . PHP_EOL);

            throw $e;
        }
    }

    return $executed;
}

function commandStatus(PDO $pdo, string $root): void
{
    $files = migrationFiles($root);
    $applied = appliedMigrations($pdo);

    out(rule());
    out('STATUS MIGRASI  —  database: ' . $pdo->query('SELECT DATABASE()')->fetchColumn());
    out(rule());
    out(sprintf('%-6s %-38s %-8s %s', 'VERSI', 'NAMA', 'BATCH', 'STATUS'));
    out(rule('-'));

    $pending = 0;
    foreach ($files as $file) {
        $isApplied = isset($applied[$file['version']]);
        if (!$isApplied) {
            $pending++;
        }
        out(sprintf(
            '%-6s %-38s %-8s %s',
            $file['version'],
            substr($file['name'], 0, 38),
            $isApplied ? (string) $applied[$file['version']] : '-',
            $isApplied ? 'sudah' : 'belum'
        ));
    }

    out(rule('-'));
    out(sprintf('Total %d migrasi — %d sudah, %d belum', count($files), count($applied), $pending));

    if (tableExists($pdo, 'schema_seeders')) {
        $seeders = $pdo->query('SELECT seeder, executed_at FROM schema_seeders ORDER BY seeder')->fetchAll();
        out('');
        out('SEEDER (' . count($seeders) . ' terakhir dijalankan)');
        foreach ($seeders as $row) {
            out('  - ' . $row['seeder'] . '  ' . $row['executed_at']);
        }
    }
}

function commandCreateDatabase(): void
{
    $created = Database::ensureDatabase();
    $config = Database::config();

    out($created
        ? 'Database `' . $config['database'] . '` dibuat (utf8mb4_unicode_ci).'
        : 'Database `' . $config['database'] . '` sudah ada — tidak diubah.');
}

function commandUp(PDO $pdo, string $root, int $step = 0): void
{
    $files = migrationFiles($root);
    $applied = appliedMigrations($pdo);

    $pending = array_values(array_filter(
        $files,
        static fn (array $f): bool => !isset($applied[$f['version']])
    ));

    if ($pending === []) {
        out('Tidak ada migrasi yang perlu dijalankan. Semua sudah diterapkan.');
        return;
    }

    if ($step > 0) {
        $pending = array_slice($pending, 0, $step);
    }

    $batch = tableExists($pdo, 'schema_migrations')
        ? (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations')->fetchColumn() + 1
        : 1;

    out(rule());
    out('MENJALANKAN ' . count($pending) . ' MIGRASI  (batch ' . $batch . ')');
    out(rule());

    foreach ($pending as $file) {
        $sections = parseMigrationFile($file['path']);
        $statements = splitSql($sections['up']);

        if ($statements === []) {
            out('  [lewat] ' . $file['file'] . ' — tidak ada statement');
            continue;
        }

        out('  [jalan] ' . $file['file'] . ' — ' . count($statements) . ' statement');

        try {
            $count = runStatements($pdo, $statements, $file['file']);
        } catch (PDOException $e) {
            fail('Migrasi ' . $file['file'] . ' berhenti. Database dalam keadaan TIDAK LENGKAP.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO schema_migrations (version, name, batch, statements) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$file['version'], $file['name'], $batch, $count]);
    }

    out(rule('-'));
    out('Selesai. ' . count($pending) . ' migrasi diterapkan pada batch ' . $batch . '.');
}

function commandDown(PDO $pdo, string $root, int $step = 1): void
{
    if (!tableExists($pdo, 'schema_migrations')) {
        out('Belum ada migrasi yang diterapkan.');
        return;
    }

    $batches = $pdo->query('SELECT DISTINCT batch FROM schema_migrations ORDER BY batch DESC')
        ->fetchAll(PDO::FETCH_COLUMN);

    if ($batches === []) {
        out('Belum ada migrasi yang diterapkan.');
        return;
    }

    $targets = array_slice($batches, 0, max(1, $step));
    $files = migrationFiles($root);
    $byVersion = [];
    foreach ($files as $file) {
        $byVersion[$file['version']] = $file;
    }

    out(rule());
    out('ROLLBACK batch: ' . implode(', ', $targets));
    out(rule());

    foreach ($targets as $batch) {
        $stmt = $pdo->prepare('SELECT version FROM schema_migrations WHERE batch = ? ORDER BY version DESC');
        $stmt->execute([$batch]);
        $versions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($versions as $version) {
            if (!isset($byVersion[$version])) {
                out('  [lewat] versi ' . $version . ' — berkas tidak ditemukan');
                continue;
            }

            $file = $byVersion[$version];
            $sections = parseMigrationFile($file['path']);
            $statements = splitSql($sections['down']);

            if ($statements === []) {
                out('  [lewat] ' . $file['file'] . ' — tidak punya bagian @DOWN');
                continue;
            }

            out('  [balik] ' . $file['file'] . ' — ' . count($statements) . ' statement');
            runStatements($pdo, $statements, $file['file']);

            // Migrasi 001 ikut menghapus tabel schema_migrations.
            // Kalau tabelnya sudah tidak ada, tidak ada baris yang perlu dihapus.
            if (tableExists($pdo, 'schema_migrations')) {
                $del = $pdo->prepare('DELETE FROM schema_migrations WHERE version = ?');
                $del->execute([$version]);
            }
        }
    }

    out(rule('-'));
    out('Rollback selesai.');
}

function commandFresh(PDO $pdo, string $root, bool $force, string $appEnv): void
{
    if ($appEnv === 'production' && !$force) {
        fail('fresh DITOLAK: APP_ENV=production. Ini akan menghapus seluruh data. Tambahkan --force kalau benar-benar yakin.');
    }

    $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

    out(rule());
    out('FRESH — mengosongkan database `' . $dbName . '`');
    out(rule());

    $tables = $pdo->query(
        'SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = "BASE TABLE"'
    )->fetchAll(PDO::FETCH_COLUMN);

    if ($tables !== []) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '', $table) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        out('  ' . count($tables) . ' tabel dihapus.');
    } else {
        out('  Database sudah kosong.');
    }

    out('');
    commandUp($pdo, $root, 0);
}

function commandSeed(PDO $pdo, string $root, array $options = []): void
{
    $withDemo = isset($options['demo']);
    $seeders = seederFiles($root, $withDemo);

    if ($seeders === []) {
        out('Tidak ada berkas seeder di ' . SEEDER_DIR . '.');
        return;
    }

    out(rule());
    out('MENJALANKAN ' . count($seeders) . ' SEEDER' . ($withDemo ? ' (termasuk data contoh)' : ''));
    out(rule());

    foreach ($seeders as $seeder) {
        $raw = (string) file_get_contents($seeder['path']);
        $upOnly = seederUpSection($raw, $seeder['path']);
        $statements = splitSql($upOnly);

        out('  [seed] ' . $seeder['file'] . ' — ' . count($statements) . ' statement');
        runStatements($pdo, $statements, $seeder['file']);

        $stmt = $pdo->prepare(
            'INSERT INTO schema_seeders (seeder, checksum) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE checksum = VALUES(checksum), executed_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$seeder['file'], hash('sha256', $raw)]);
    }

    out(rule('-'));
    out('Seeder selesai.');

    if (!$withDemo) {
        out('');
        out('  Data contoh tidak dijalankan. Untuk mengisinya:');
        out('    php tools/migrate.php seed --demo');
    }
}

function commandSeedAdmin(PDO $pdo, array $options): void
{
    if (!tableExists($pdo, 'users')) {
        fail('Tabel `users` belum ada. Jalankan `php tools/migrate.php up` dulu.');
    }

    $email = (string) ($options['email'] ?? 'admin@mts-alhuda.sch.id');
    $username = (string) ($options['username'] ?? 'admin');
    $name = (string) ($options['name'] ?? 'Administrator Madrasah');
    $password = isset($options['password']) ? (string) $options['password'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fail('Email tidak valid: ' . $email);
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
    $check->execute([$email, $username]);
    if ($check->fetchColumn() !== false) {
        fail('Akun dengan email atau username itu sudah ada. Tidak ada yang diubah.');
    }

    if ($password === null) {
        out('');
        out('Membuat akun admin:');
        out('  Nama     : ' . $name);
        out('  Email    : ' . $email);
        out('  Username : ' . $username);
        out('');
        out('Masukkan kata sandi (minimal 12 karakter).');
        out('CATATAN: di terminal Windows ketikan akan terlihat di layar.');
        echo '  Kata sandi: ';
        $password = trim((string) fgets(STDIN));
    }

    if (strlen($password) < 12) {
        fail('Kata sandi minimal 12 karakter. Dibatalkan, tidak ada yang ditulis.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, username, password_hash, role, role_label,
                            is_active, must_change_password, created_at, updated_at)
         VALUES (?, ?, ?, ?, "admin", "Super Admin", 1, 1, NOW(), NOW())'
    );
    $stmt->execute([$name, $email, $username, $hash]);

    out('');
    out('Akun admin dibuat (id ' . $pdo->lastInsertId() . ').');
    out('Pengguna WAJIB mengganti kata sandi saat login pertama.');
}

function commandHelp(): void
{
    out(rule());
    out('MIGRATION RUNNER — MTs Al-Huda Gondang');
    out(rule());
    out('  php tools/migrate.php status              Daftar migrasi + status');
    out('  php tools/migrate.php db:create           Buat database kalau belum ada');
    out('  php tools/migrate.php up                  Jalankan semua migrasi yang belum');
    out('  php tools/migrate.php up --step=1         Jalankan 1 migrasi saja');
    out('  php tools/migrate.php down --step=1       Rollback 1 batch terakhir');
    out('  php tools/migrate.php fresh --force       Kosongkan + jalankan ulang (DEV)');
    out('  php tools/migrate.php seed                Jalankan seeder inti (pengaturan)');
    out('  php tools/migrate.php seed --demo         Seeder inti + data contoh');
    out('  php tools/migrate.php seed --admin        Buat akun admin pertama');
    out('');
    out('Opsi seed --admin: --email= --username= --name= --password=');
}

// ---------------------------------------------------------------------------

$args = parseArgs($argv);
$command = $args['command'];
$options = $args['options'];

$config = Database::config();
$appEnv = (string) ($config['app_env'] ?? 'local');

try {
    switch ($command) {
        case 'help':
        case '--help':
        case '-h':
            commandHelp();
            break;

        case 'db:create':
            commandCreateDatabase();
            break;

        case 'status':
            commandStatus(Database::connection(), $ROOT);
            break;

        case 'up':
            if (!Database::ensureDatabase()) {
                // database sudah ada, lanjut
            }
            $step = isset($options['step']) ? (int) $options['step'] : 0;
            commandUp(Database::connection(), $ROOT, $step);
            break;

        case 'down':
            $step = isset($options['step']) ? (int) $options['step'] : 1;
            commandDown(Database::connection(), $ROOT, $step);
            break;

        case 'fresh':
            commandFresh(
                Database::connection(),
                $ROOT,
                isset($options['force']),
                $appEnv
            );
            break;

        case 'seed':
            $pdo = Database::connection();
            if (isset($options['admin'])) {
                commandSeedAdmin($pdo, $options);
            } else {
                commandSeed($pdo, $ROOT, $options);
            }
            break;

        default:
            fail('Perintah tidak dikenal: ' . $command . PHP_EOL . 'Jalankan tanpa argumen untuk melihat bantuan.');
    }
} catch (PDOException $e) {
    fail('Kesalahan database: ' . $e->getMessage());
}
