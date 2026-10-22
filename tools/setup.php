<?php
/**
 * Persiapan satu perintah untuk anggota tim baru.
 *
 *   php tools/setup.php
 *   php tools/setup.php --no-demo
 *   php tools/setup.php --admin-email=guru@mts-alhuda.sch.id --admin-password=Rahasia#2026
 *
 * Yang dikerjakan, berurutan:
 *   1. Periksa PHP dan ekstensi yang wajib ada
 *   2. Cari server MySQL/MariaDB (port default 3306, cadangan 3307/3308/3309)
 *   3. Buat database kalau belum ada
 *   4. Jalankan semua migrasi
 *   5. Jalankan seeder (pengaturan + data contoh)
 *   6. Buat akun admin pertama kalau belum ada
 *   7. Tampilkan alamat yang harus dibuka
 *
 * Aman dijalankan berulang. Migrasi yang sudah jalan dilewati, seeder
 * bersifat idempoten, dan akun admin tidak dibuat dua kali.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya boleh dijalankan dari CLI.\n");
}

$ROOT = dirname(__DIR__);
require $ROOT . '/app/Support/Database.php';
require $ROOT . '/app/Support/ServerLayout.php';

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

/**
 * @return array<string,string|bool>
 */
function parseOptions(array $argv): array
{
    $options = [];
    foreach ($argv as $arg) {
        if (strncmp((string) $arg, '--', 2) !== 0) {
            continue;
        }
        $arg = substr((string) $arg, 2);
        if (strpos($arg, '=') !== false) {
            [$k, $v] = explode('=', $arg, 2);
            $options[$k] = $v;
        } else {
            $options[$arg] = true;
        }
    }

    return $options;
}

function out(string $line = ''): void
{
    echo $line . PHP_EOL;
}

function line(string $char = '=', int $width = 74): string
{
    return str_repeat($char, $width);
}

function heading(string $text): void
{
    out('');
    out(line());
    out($text);
    out(line());
}

function step(string $text): void
{
    out('  > ' . $text);
}

function good(string $text): void
{
    out('  [ok] ' . $text);
}

function warn(string $text): void
{
    out('  [perhatian] ' . $text);
}

function abort(string $text): void
{
    out('');
    out('  BERHENTI: ' . $text);
    out('');
    exit(1);
}

/**
 * Jalankan skrip PHP lain dan kembalikan kode keluarnya.
 */
function runPhp(string $script, array $args = []): int
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);

    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg((string) $arg);
    }

    $output = [];
    $code = 0;
    exec($command . ' 2>&1', $output, $code);

    foreach ($output as $baris) {
        out($baris === '' ? '' : '    ' . $baris);
    }

    return $code;
}

// ---------------------------------------------------------------------------

$options = parseOptions($argv);
$config = Database::config();
$dbName = (string) $config['database'];

heading('PERSIAPAN SISTEM — MTs Al-Huda Gondang');
out('  Folder proyek : ' . $ROOT);
out('  Tumpukan      : ' . ServerLayout::name($ROOT));
out('  Database      : ' . $dbName);
out('  Pengguna DB   : ' . $config['username'] . ' @ ' . $config['host']);

// Saat pindah dari XAMPP ke Laragon (atau sebaliknya), port bisa berubah.
// Kalau database.local.php masih menyebut port lama, koneksi akan gagal
// dengan pesan yang tidak menyebut sebabnya. Peringatkan di depan.
if (is_file($ROOT . '/config/database.local.php') && empty($config['port_is_default'])) {
    out('');
    out('  Catatan: config/database.local.php ada dan mengunci port ' . (int) $config['port'] . '.');
    out('           Kalau MySQL di tumpukan ini ada di port lain, ubah atau hapus berkas itu.');
}

// --- 1. PHP & ekstensi -----------------------------------------------------

heading('1. Memeriksa PHP');

$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$phpOk
    ? good('PHP ' . PHP_VERSION)
    : abort('PHP ' . PHP_VERSION . ' terlalu lama. Minimal 8.0. Kode ini memakai `match` dan `mixed`.');

$required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'fileinfo', 'curl'];
$missingExt = [];

foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missingExt[] = $ext;
    }
}

if ($missingExt !== []) {
    abort(
        'Ekstensi PHP belum aktif: ' . implode(', ', $missingExt) . PHP_EOL
        . '  Buka ' . ServerLayout::phpIni() . PHP_EOL
        . '  Hapus tanda ; di depan baris extension=' . $missingExt[0] . PHP_EOL
        . '  lalu restart Apache.'
    );
}

good('Semua ekstensi wajib aktif (' . count($required) . ')');

// --- 2. Cari server --------------------------------------------------------

heading('2. Mencari server MySQL/MariaDB');

try {
    $pdo = Database::serverConnection();
} catch (PDOException $e) {
    abort(
        'Tidak bisa menyambung ke server database.' . PHP_EOL . PHP_EOL
        . '  Yang sudah dicoba: port ' . implode(', ', array_unique(array_merge(
            [(int) $config['port']],
            [3306, 3307, 3308, 3309]
        ))) . PHP_EOL . PHP_EOL
        . '  Periksa berurutan:' . PHP_EOL
        . '    1. Apakah MySQL/MariaDB sudah Start? (' . ServerLayout::name($ROOT) . ')' . PHP_EOL
        . '    2. Port MySQL Anda berapa? Lihat ' . ServerLayout::mysqlHint($ROOT) . PHP_EOL
        . '    3. Kalau bukan 3306, tulis di config/database.local.php:' . PHP_EOL
        . "         return ['port' => 3307];" . PHP_EOL
        . '    4. Kalau root Anda bersandi, tambahkan juga:' . PHP_EOL
        . "         return ['port' => 3306, 'password' => 'sandi-anda'];" . PHP_EOL
        . '    5. Baru pindah dari XAMPP/Laragon? Hapus config/database.local.php' . PHP_EOL
        . '       lalu jalankan ulang skrip ini — port lama di situ akan menolak port baru.' . PHP_EOL . PHP_EOL
        . '  Pesan asli: ' . $e->getMessage()
    );
}

$version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
$port = Database::resolvedPort();

good('Server menjawab di port ' . $port . ' — ' . $version);

if ($port !== (int) $config['port']) {
    warn(
        'Port di konfigurasi ' . $config['port'] . ', tetapi server ada di ' . $port . '.' . PHP_EOL
        . '    Supaya tidak ada percobaan port setiap request, buat config/database.local.php:' . PHP_EOL
        . "      <?php return ['port' => " . $port . '];'
    );
}

// --- 3. Database -----------------------------------------------------------

heading('3. Menyiapkan database');

try {
    $created = Database::ensureDatabase();
    $created
        ? good('Database `' . $dbName . '` dibuat')
        : good('Database `' . $dbName . '` sudah ada');
} catch (PDOException $e) {
    abort(
        'Gagal membuat database: ' . $e->getMessage() . PHP_EOL
        . '  Kalau akun DB Anda tidak punya hak CREATE DATABASE (umum di hosting),' . PHP_EOL
        . '  buat database-nya lewat phpMyAdmin lalu jalankan ulang skrip ini.'
    );
}

// --- 4. Migrasi ------------------------------------------------------------

heading('4. Menjalankan migrasi');

$migrate = $ROOT . '/tools/migrate.php';

if (runPhp($migrate, ['up']) !== 0) {
    abort('Migrasi gagal. Baca pesan di atas — jangan diulang sebelum penyebabnya jelas.');
}

good('Skema siap');

// --- 5. Seeder -------------------------------------------------------------

heading('5. Mengisi data awal');

$seedArgs = isset($options['no-demo']) ? ['seed'] : ['seed', '--demo'];

if (runPhp($migrate, $seedArgs) !== 0) {
    abort('Seeder gagal. Baca pesan di atas.');
}

good(isset($options['no-demo']) ? 'Pengaturan diisi, data contoh dilewati' : 'Pengaturan dan data contoh terisi');

// --- 6. Akun admin ---------------------------------------------------------

heading('6. Akun admin');

$pdo = Database::connection();
$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

if ($adminCount > 0) {
    good('Sudah ada ' . $adminCount . ' akun admin — tidak dibuat ulang');
    out('');
    out('  Kalau lupa kata sandinya, buat admin baru:');
    out('    php tools/migrate.php seed --admin');
} else {
    out('  Belum ada akun admin. Buat sekarang.');
    out('');

    $adminArgs = ['seed', '--admin'];

    foreach (['admin-email' => 'email', 'admin-username' => 'username',
              'admin-name' => 'name', 'admin-password' => 'password'] as $opt => $flag) {
        if (isset($options[$opt])) {
            $adminArgs[] = '--' . $flag . '=' . $options[$opt];
        }
    }

    if (runPhp($migrate, $adminArgs) !== 0) {
        abort('Gagal membuat akun admin.');
    }

    good('Akun admin dibuat');
}

// --- 7. Alamat -------------------------------------------------------------

heading('7. Selesai — buka alamat ini');

$folder = basename($ROOT);
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Port web ditebak dari konfigurasi tumpukan yang terpasang (Laragon/XAMPP),
// lalu 80 sebagai bawaan. Tidak pernah gagal — paling hanya alamatnya meleset.
$webPort = ServerLayout::webPort($ROOT);

$portPart = ($webPort === 80) ? '' : ':' . $webPort;
$base = 'http://' . $host . $portPart . '/' . $folder;

// Laragon memasang virtual host otomatis (<folder>.test) yang mengarah
// langsung ke folder proyek. Tetapi domain itu baru ada di berkas hosts
// setelah Laragon dijalankan sekali lewat GUI. Mencetak alamat yang belum
// bisa dibuka lebih buruk daripada tidak mencetaknya — jadi diperiksa dulu.
if (ServerLayout::name($ROOT) === 'Laragon') {
    $domain = $folder . '.test';

    if (gethostbyname($domain) !== $domain) {
        out('  Website       : http://' . $domain . '/');
        out('  Panel admin   : http://' . $domain . '/admin/login');
        out('  API           : http://' . $domain . '/api/v1/health');
        out('');
        out('  Atau lewat localhost:');
    } else {
        out('  Catatan: domain ' . $domain . ' belum aktif.');
        out('           Jalankan Laragon sekali lewat GUI (tombol Start) supaya');
        out('           domain itu ditambahkan ke hosts. Sementara pakai localhost.');
        out('');
    }
}

out('  Website       : ' . $base . '/');
out('  Panel admin   : ' . $base . '/admin/login');
out('  API           : ' . $base . '/api/v1/health');
out('');

$totalTables = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
)->fetchColumn();

out('  Tabel         : ' . $totalTables);
out('  Migrasi       : ' . (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn());
out('  Pengguna      : ' . (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
out('');
out('  Periksa sekali lagi dengan:');
out('    php tools/db-check.php');
out('    php tools/schema-test.php');
out('');
