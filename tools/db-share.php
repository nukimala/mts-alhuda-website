<?php
/**
 * Pengelola akses database untuk tim — CLI saja.
 *
 *   php tools/db-share.php info
 *   php tools/db-share.php list
 *   php tools/db-share.php create --user=budi --role=developer
 *   php tools/db-share.php create --user=sari --role=readonly
 *   php tools/db-share.php create --user=deploy --role=app
 *   php tools/db-share.php create --user=x --role=developer --password=Rahasia#2026
 *   php tools/db-share.php drop --user=budi
 *   php tools/db-share.php test --user=budi
 *   php tools/db-share.php host --allow=192.168.100.0/24
 *
 * Peran yang tersedia:
 *   app        baca + tulis, tanpa DROP/ALTER. Untuk aplikasi produksi.
 *   developer  baca + tulis + ALTER/CREATE/DROP tabel. Untuk tim developer.
 *   readonly   hanya SELECT. Untuk analis, dosen pembimbing, atau demo.
 *   admin      semua hak pada database ini saja (bukan global).
 *
 * Yang TIDAK dilakukan tool ini:
 *   - Tidak pernah memberi hak global (*.*)
 *   - Tidak pernah memberi GRANT OPTION
 *   - Tidak pernah mengaktifkan akses root dari luar localhost
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

function out(string $line = ''): void
{
    echo $line . PHP_EOL;
}

function fail(string $message): void
{
    fwrite(STDERR, PHP_EOL . 'GAGAL: ' . $message . PHP_EOL);
    exit(1);
}

function rule(string $char = '=', int $width = 74): string
{
    return str_repeat($char, $width);
}

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

/**
 * IP LAN mesin ini — yang harus dipakai rekan tim untuk menyambung.
 *
 * Adaptor virtual (Hyper-V, WSL, VMware, VirtualBox, Loopback) dibuang:
 * alamatnya hanya berlaku di dalam mesin ini, tidak bisa dijangkau rekan tim.
 * Hasil diurutkan: Wi-Fi/Ethernet dulu, baru sisanya.
 *
 * @return list<array{ip:string, interface:string}>
 */
function lanAddresses(): array
{
    $result = [];

    // Pola nama adaptor yang tidak bisa dijangkau dari komputer lain.
    $virtual = '/vEthernet|Hyper-V|WSL|VMware|VirtualBox|Loopback|Teredo|Bluetooth|Npcap|Tap-|VPN|ZeroTier|Tailscale/i';

    if (PHP_OS_FAMILY === 'Windows') {
        $raw = @shell_exec('ipconfig');
        if (is_string($raw)) {
            $current = '';
            foreach (preg_split('/\R/', $raw) as $line) {
                if (preg_match('/adapter\s+(.+):/i', $line, $m)) {
                    $current = trim($m[1]);
                }
                if (preg_match('/IPv4 Address[^:]*:\s*([0-9.]+)/i', $line, $m)) {
                    $ip = $m[1];
                    if (strpos($ip, '127.') === 0 || strpos($ip, '169.254.') === 0) {
                        continue;
                    }
                    if (preg_match($virtual, $current)) {
                        continue;
                    }
                    $result[] = ['ip' => $ip, 'interface' => $current];
                }
            }
        }
    } else {
        $raw = @shell_exec("ip -4 -o addr show scope global 2>/dev/null");
        if (is_string($raw)) {
            foreach (preg_split('/\R/', trim($raw)) as $line) {
                if (preg_match('/^\d+:\s+(\S+)\s+inet\s+([0-9.]+)/', $line, $m)) {
                    if (preg_match($virtual, $m[1])) {
                        continue;
                    }
                    $result[] = ['ip' => $m[2], 'interface' => $m[1]];
                }
            }
        }
    }

    // Urutkan: adaptor yang namanya menandakan jaringan nyata lebih dulu.
    usort($result, static function (array $a, array $b): int {
        $score = static function (string $name): int {
            if (preg_match('/Wi-?Fi|Wireless|WLAN/i', $name)) {
                return 0;
            }
            if (preg_match('/Ethernet|LAN/i', $name)) {
                return 1;
            }
            return 2;
        };

        return $score($a['interface']) <=> $score($b['interface']);
    });

    return $result;
}

/**
 * @return list<array<string,mixed>>
 */
function dbUsers(PDO $pdo): array
{
    return $pdo->query(
        "SELECT User, Host, plugin FROM mysql.user
         WHERE User NOT IN ('root','pma','mariadb.sys','mysql')
         ORDER BY User, Host"
    )->fetchAll();
}

function commandInfo(PDO $pdo): void
{
    $config = Database::config();
    $dbName = (string) $config['database'];
    $port = (int) $config['port'];

    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

    // Apakah server menerima koneksi dari luar localhost?
    $bind = '';
    try {
        $bind = (string) $pdo->query("SELECT @@bind_address")->fetchColumn();
    } catch (PDOException $e) {
        $bind = '?';
    }

    out(rule());
    out('AKSES DATABASE — MTs Al-Huda Gondang');
    out(rule());
    out('  Server    : MariaDB ' . $version);
    out('  Database  : ' . $dbName);
    out('  Port      : ' . $port . ($port !== 3306 ? '   (bukan default 3306 - setelan mesin ini)' : '   (default)'));
    out('  bind_addr : ' . ($bind === '' ? '(semua antarmuka)' : $bind));
    out('');

    out('  ALAMAT UNTUK REKAN TIM');
    out(rule('-'));
    out(sprintf('  %-18s %-30s %s', 'DARI', 'HOST', 'KETERANGAN'));

    out(sprintf('  %-18s %-30s %s', 'mesin ini', '127.0.0.1', 'localhost saja'));

    $lan = lanAddresses();
    if ($lan === []) {
        out('  (tidak ada IP LAN terdeteksi — mesin mungkin tidak terhubung jaringan)');
    }
    foreach ($lan as $entry) {
        out(sprintf('  %-18s %-30s %s', 'jaringan lokal', $entry['ip'], $entry['interface']));
    }

    out('');
    out('  CONTOH PERINTAH DI KOMPUTER REKAN TIM');
    out(rule('-'));
    $ip = $lan[0]['ip'] ?? '<IP-LAN>';

    out('  MySQL CLI:');
    out('    mysql -h ' . $ip . ' -P ' . $port . ' -u <user> -p ' . $dbName);
    out('');
    out('  PHP (PDO):');
    out("    \$pdo = new PDO('mysql:host=" . $ip . ";port=" . $port . ";dbname=" . $dbName . ";charset=utf8mb4', '<user>', '<password>');");
    out('');
    out('  Laragon / HeidiSQL / DBeaver / TablePlus:');
    out('    Host     : ' . $ip);
    out('    Port     : ' . $port);
    out('    Database : ' . $dbName);
    out('    User     : <user>');
    out('');
    out('  DSN untuk .env aplikasi mereka:');
    out('    DB_HOST=' . $ip);
    out('    DB_PORT=' . $port);
    out('    DB_NAME=' . $dbName);
    out('    DB_USER=<user>');
    out('    DB_PASS=<password>');
    out('');

    out('  ATURAN YANG TIDAK BOLEH DILANGGAR');
    out(rule('-'));
    out('  1. root TIDAK boleh dibuka ke jaringan. Tool ini menolak melakukannya.');
    out('  2. Setiap orang punya akun sendiri. Jangan berbagi satu akun.');
    out('  3. Jangan pakai akun developer untuk aplikasi yang jalan. Pakai peran app.');
    out('  4. Port MySQL di mesin ini ' . $port . '. Pastikan cocok — beda port adalah kesalahan paling sering.');
    out('  5. Kredensial tidak masuk repo. Simpan di config/database.local.php atau .env.');
    out('');
}

function commandList(PDO $pdo): void
{
    $config = Database::config();
    $dbName = (string) $config['database'];

    $users = dbUsers($pdo);

    out(rule());
    out('AKUN DATABASE NON-ROOT');
    out(rule());

    if ($users === []) {
        out('  Belum ada. Buat dengan: php tools/db-share.php create --user=nama --role=developer');
        out('');
        return;
    }

    out(sprintf('%-20s %-20s %-22s %s', 'USER', 'HOST', 'PLUGIN', 'HAK PADA ' . strtoupper($dbName)));
    out(rule('-'));

    foreach ($users as $u) {
        $grants = [];
        try {
            $stmt = $pdo->query('SHOW GRANTS FOR ' . $pdo->quote((string) $u['User']) . '@' . $pdo->quote((string) $u['Host']));
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $g) {
                if (stripos((string) $g, $dbName) !== false) {
                    $grants[] = preg_replace('/^GRANT\s+/i', '', (string) $g);
                    $grants[count($grants) - 1] = preg_replace('/\s+ON\s+`?' . preg_quote($dbName, '/') . '`?\.\*\s+TO.*$/i', '', $grants[count($grants) - 1]);
                }
            }
        } catch (PDOException $e) {
            $grants[] = '?';
        }

        out(sprintf(
            '%-20s %-20s %-22s %s',
            $u['User'],
            $u['Host'],
            $u['plugin'] ?: '-',
            $grants === [] ? '(tidak ada hak di database ini)' : implode(' + ', $grants)
        ));
    }

    out(rule('-'));
    out('Total: ' . count($users) . ' akun');
    out('');
}

function roleGrants(string $role, string $dbName): array
{
    $db = '`' . str_replace('`', '', $dbName) . '`';

    return match ($role) {
        'readonly' => [
            'SELECT',
        ],
        'app' => [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE',
            'CREATE TEMPORARY TABLES', 'LOCK TABLES',
            'EXECUTE',
        ],
        'developer' => [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE',
            'CREATE', 'ALTER', 'INDEX', 'DROP',
            'CREATE TEMPORARY TABLES', 'LOCK TABLES',
            'CREATE VIEW', 'SHOW VIEW',
            'CREATE ROUTINE', 'ALTER ROUTINE', 'EXECUTE',
            'REFERENCES',
        ],
        'admin' => [
            'ALL PRIVILEGES',
        ],
        default => [],
    };
}

function commandCreate(PDO $pdo, array $options): void
{
    $config = Database::config();
    $dbName = (string) $config['database'];

    $user = (string) ($options['user'] ?? '');
    $role = (string) ($options['role'] ?? 'developer');
    $host = (string) ($options['host'] ?? '%');
    $password = isset($options['password']) ? (string) $options['password'] : null;

    if ($user === '') {
        fail('--user wajib diisi. Contoh: php tools/db-share.php create --user=budi --role=developer');
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $user)) {
        fail('Nama user hanya boleh huruf, angka, garis bawah, 3-32 karakter.');
    }

    if ($user === 'root' || $user === 'pma') {
        fail('Nama itu dipakai sistem. Pilih nama lain.');
    }

    $grants = roleGrants($role, $dbName);
    if ($grants === []) {
        fail('Peran tidak dikenal: ' . $role . '. Pilihan: readonly, app, developer, admin.');
    }

    if ($password === null) {
        // 24 karakter acak, mudah dibaca ulang tapi tetap kuat.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 24; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
    }

    if (strlen($password) < 12) {
        fail('Kata sandi minimal 12 karakter.');
    }

    // Cek apakah akun sudah ada.
    $exists = $pdo->prepare('SELECT COUNT(*) FROM mysql.user WHERE User = ? AND Host = ?');
    $exists->execute([$user, $host]);
    if ((int) $exists->fetchColumn() > 0) {
        fail('Akun ' . $user . '@' . $host . ' sudah ada. Hapus dulu: php tools/db-share.php drop --user=' . $user);
    }

    $quotedUser = $pdo->quote($user) . '@' . $pdo->quote($host);
    $quotedPass = $pdo->quote($password);

    out(rule());
    out('MEMBUAT AKUN DATABASE');
    out(rule());
    out('  User     : ' . $user);
    out('  Host     : ' . $host);
    out('  Peran    : ' . $role);
    out('  Database : ' . $dbName);
    out('');

    $pdo->exec('CREATE USER ' . $quotedUser . ' IDENTIFIED BY ' . $quotedPass);

    foreach ($grants as $grant) {
        $pdo->exec('GRANT ' . $grant . ' ON `' . str_replace('`', '', $dbName) . '`.* TO ' . $quotedUser);
    }

    $pdo->exec('FLUSH PRIVILEGES');

    out('  [ok] akun dibuat');
    out('  [ok] ' . count($grants) . ' hak diberikan, hanya pada database ' . $dbName);
    out('  [ok] tidak ada hak global, tidak ada GRANT OPTION');
    out('');

    $lan = lanAddresses();
    $ip = $lan[0]['ip'] ?? '<IP-LAN>';
    $port = (int) $config['port'];

    out(rule());
    out('SERAHKAN INI KE REKAN TIM');
    out(rule());
    out('  Host     : ' . $ip);
    out('  Port     : ' . $port);
    out('  Database : ' . $dbName);
    out('  User     : ' . $user);
    out('  Password : ' . $password);
    out('');
    out('  Perintah uji:');
    out('    mysql -h ' . $ip . ' -P ' . $port . ' -u ' . $user . ' -p ' . $dbName);
    out('');

    if ($role === 'developer' || $role === 'admin') {
        out('  PERINGATAN: peran ini bisa DROP tabel.');
        out('  Jangan dipakai di komputer yang menyambung ke database berisi data nyata.');
        out('');
    }

    out('  Kata sandi ini hanya ditampilkan SEKARANG.');
    out('  Catat, lalu kirim lewat jalur pribadi — bukan grup chat.');
    out('');
}

function commandDrop(PDO $pdo, array $options): void
{
    $user = (string) ($options['user'] ?? '');
    $host = (string) ($options['host'] ?? '%');

    if ($user === '') {
        fail('--user wajib diisi.');
    }

    if (in_array($user, ['root', 'pma', 'mariadb.sys', 'mysql'], true)) {
        fail('Akun sistem tidak boleh dihapus lewat tool ini.');
    }

    $exists = $pdo->prepare('SELECT COUNT(*) FROM mysql.user WHERE User = ? AND Host = ?');
    $exists->execute([$user, $host]);

    if ((int) $exists->fetchColumn() === 0) {
        fail('Akun ' . $user . '@' . $host . ' tidak ada.');
    }

    $quotedUser = $pdo->quote($user) . '@' . $pdo->quote($host);

    out('Menghapus ' . $user . '@' . $host . ' ...');
    $pdo->exec('DROP USER ' . $quotedUser);
    $pdo->exec('FLUSH PRIVILEGES');
    out('  [ok] akun dihapus. Database dan isinya TIDAK tersentuh.');
    out('');
}

function commandTest(PDO $pdo, array $options): void
{
    $config = Database::config();
    $dbName = (string) $config['database'];

    $user = (string) ($options['user'] ?? '');
    if ($user === '') {
        fail('--user wajib diisi.');
    }

    $password = isset($options['password']) ? (string) $options['password'] : null;
    if ($password === null) {
        echo '  Kata sandi untuk ' . $user . ': ';
        $password = trim((string) fgets(STDIN));
    }

    $lan = lanAddresses();
    $ip = (string) ($options['host'] ?? ($lan[0]['ip'] ?? '127.0.0.1'));
    $port = (int) $config['port'];

    out(rule());
    out('UJI KONEKSI SEBAGAI ' . $user . ' DARI ' . $ip . ':' . $port);
    out(rule());

    try {
        $test = Database::makePdo(
            array_merge($config, ['host' => $ip, 'port' => $port]),
            $dbName
        );
    } catch (PDOException $e) {
        fail('Tidak bisa menyambung: ' . $e->getMessage());
    }

    // Siapa saya?
    $who = $test->query('SELECT CURRENT_USER() AS u')->fetchColumn();
    out('  [ok] tersambung sebagai ' . $who);

    // Bisa baca?
    try {
        $n = (int) $test->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn();
        out('  [ok] bisa membaca skema — ' . $n . ' tabel terlihat');
    } catch (PDOException $e) {
        out('  [gagal] baca skema: ' . $e->getMessage());
    }

    // Bisa tulis?
    try {
        $test->exec('CREATE TEMPORARY TABLE _uji_akses (id INT)');
        out('  [ok] bisa membuat tabel sementara (hak tulis)');
    } catch (PDOException $e) {
        out('  [info] tidak bisa membuat tabel sementara — kemungkinan peran readonly');
    }

    // Bisa DROP? Ini yang membedakan developer dari app.
    try {
        $test->exec('CREATE TABLE _uji_drop (id INT)');
        $test->exec('DROP TABLE _uji_drop');
        out('  [ok] bisa CREATE + DROP tabel — peran developer atau admin');
    } catch (PDOException $e) {
        out('  [info] tidak bisa DROP tabel — peran app atau readonly');
    }

    out('');
}

function commandHost(PDO $pdo, array $options): void
{
    $allow = (string) ($options['allow'] ?? '');
    if ($allow === '') {
        fail('--allow wajib diisi. Contoh: --allow=192.168.100.0/24');
    }

    $config = Database::config();
    $dbName = (string) $config['database'];

    out(rule());
    out('CARA MEMBUKA AKSES KE SATU JARINGAN SAJA');
    out(rule());
    out('  Tidak ada perintah otomatis di sini. Yang perlu dilakukan manual:');
    out('');
    out('  1. Buat akun untuk tiap orang, dengan host dibatasi ke subnet:');
    out('       php tools/db-share.php create --user=budi --role=developer --host=' . $allow);
    out('');
    out('  2. Pastikan bind-address di my.ini tidak mengunci ke 127.0.0.1.');
    out('     Berkas: ' . ServerLayout::mysqlHint());
    out('     Cari baris bind-address. Kalau ada dan bernilai 127.0.0.1, ubah jadi:');
    out('       bind-address=0.0.0.0');
    out('     Lalu restart MySQL/MariaDB.');
    out('');
    out('  3. Pastikan firewall Windows mengizinkan port ' . (int) $config['port'] . ' masuk.');
    out('     Jalankan PowerShell sebagai Administrator:');
    out('       New-NetFirewallRule -DisplayName "MariaDB ' . (int) $config['port'] . ' LAN" -Direction Inbound `');
    out('         -Protocol TCP -LocalPort ' . (int) $config['port'] . ' -Action Allow -Profile Private');
    out('');
    out('  4. Uji dari komputer rekan tim:');
    out('       mysql -h <IP-LAN> -P ' . (int) $config['port'] . ' -u <user> -p ' . $dbName);
    out('');
    out('  Catatan: akun yang dibuat tanpa --host otomatis memakai host \'%\',');
    out('  artinya bisa dari mana saja. Untuk jaringan lokal, membatasi ke subnet');
    out('  lebih aman.');
    out('');
}

// ---------------------------------------------------------------------------

$command = $argv[1] ?? 'info';
$options = parseOptions(array_slice($argv, 1));

try {
    // Perintah yang butuh koneksi ke database aplikasi
    $needsDb = ['list', 'create', 'drop', 'test'];

    if (in_array($command, $needsDb, true)) {
        try {
            $pdo = Database::connection();
        } catch (PDOException $e) {
            fail('Tidak bisa menyambung ke database: ' . $e->getMessage()
                . PHP_EOL . 'Pastikan MariaDB hidup dan port di config/database.local.php benar.');
        }
    } else {
        // info dan host hanya perlu koneksi server (tanpa database)
        try {
            $pdo = Database::serverConnection();
        } catch (PDOException $e) {
            fail('Tidak bisa menyambung ke server: ' . $e->getMessage());
        }
    }

    switch ($command) {
        case 'info':
            commandInfo($pdo);
            break;

        case 'list':
            commandList($pdo);
            break;

        case 'create':
            commandCreate($pdo, $options);
            break;

        case 'drop':
            commandDrop($pdo, $options);
            break;

        case 'test':
            commandTest($pdo, $options);
            break;

        case 'host':
            commandHost($pdo, $options);
            break;

        default:
            out('Perintah: info | list | create | drop | test | host');
            out('');
            out('  php tools/db-share.php info                       Ringkasan alamat & cara sambung');
            out('  php tools/db-share.php list                       Daftar akun non-root');
            out('  php tools/db-share.php create --user=x --role=y   Buat akun');
            out('  php tools/db-share.php drop --user=x              Hapus akun');
            out('  php tools/db-share.php test --user=x              Uji koneksi dari IP LAN');
            out('  php tools/db-share.php host --allow=IP/24         Petunjuk batasi jaringan');
            break;
    }
} catch (PDOException $e) {
    fail('Kesalahan database: ' . $e->getMessage());
}
