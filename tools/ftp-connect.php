<?php
/**
 * Skrip Uji Koneksi dan Sinkronisasi FTP ke cPanel.
 *
 * Pemakaian:
 *   php tools/ftp-connect.php [test|list|upload]
 */

echo "==========================================================================\n";
echo "FTP CONNECTOR — MTs Al-Huda Gondang (cPanel)\n";
echo "==========================================================================\n";

$configFile = __DIR__ . '/../config/ftp.local.php';
$config = [];

if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'host'       => 'ftp.shulkhimaulana.my.id',
        'port'       => 21,
        'username'   => 'admin@shulkhimaulana.my.id',
        'password'   => getenv('FTP_PASSWORD') ?: '',
        'ssl'        => true,
        'passive'    => true,
        'remote_dir' => '/',
        'timeout'    => 20,
    ];
}

$host = $config['host'] ?? 'ftp.shulkhimaulana.my.id';
$port = (int) ($config['port'] ?? 21);
$user = $config['username'] ?? 'admin@shulkhimaulana.my.id';
$pass = $config['password'] ?? '';
$ssl  = (bool) ($config['ssl'] ?? true);
$pasv = (bool) ($config['passive'] ?? true);

echo "Host     : {$host}:{$port}\n";
echo "Username : {$user}\n";
echo "SSL/TLS  : " . ($ssl ? 'Explicit FTPS' : 'Standard FTP') . "\n";
echo "Passive  : " . ($pasv ? 'Yes' : 'No') . "\n";
echo "--------------------------------------------------------------------------\n";

if ($pass === '') {
    echo "\n[INFO] Kata sandi FTP belum diisi!\n";
    echo "Silakan:\n";
    echo "1. Salin 'config/ftp.local.php.example' ke 'config/ftp.local.php'\n";
    echo "2. Masukkan kata sandi FTP Anda di 'config/ftp.local.php'\n";
    echo "   atau kirimkan kata sandi di chat agar konfigurasi langsung dihubungkan.\n\n";

    // Uji jangkauan port server (Ping / Socket handshake)
    echo "Menguji jangkauan port 21...\n";
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($socket) {
        $banner = trim((string) fgets($socket));
        echo "[LULUS] Port 21 terbuka & merespons: {$banner}\n";
        fclose($socket);
    } else {
        echo "[GAGAL] Tidak dapat menjangkau port 21: {$errstr} ({$errno})\n";
    }
    exit(0);
}

// Coba koneksi FTP
$conn = false;
if ($ssl && function_exists('ftp_ssl_connect')) {
    echo "Menghubungkan via FTPS (ftp_ssl_connect)...\n";
    $conn = @ftp_ssl_connect($host, $port, (int) ($config['timeout'] ?? 20));
}

if (!$conn) {
    echo "Mencoba via FTP standar (ftp_connect)...\n";
    $conn = @ftp_connect($host, $port, (int) ($config['timeout'] ?? 20));
}

if (!$conn) {
    echo "[GAGAL] Gagal membuka koneksi ke {$host}:{$port}.\n";
    exit(1);
}

echo "[LULUS] Terhubung ke server FTP.\n";

echo "Melakukan autentikasi untuk {$user}...\n";
$login = @ftp_login($conn, $user, $pass);

if (!$login) {
    echo "[GAGAL] Login ditolak. Username atau password salah.\n";
    ftp_close($conn);
    exit(1);
}

echo "[LULUS] Login berhasil!\n";

if ($pasv) {
    ftp_pasv($conn, true);
    echo "[LULUS] Mode Passive aktif.\n";
}

$pwd = ftp_pwd($conn);
echo "Direktori saat ini: {$pwd}\n";

$rawList = ftp_nlist($conn, '.');
if (is_array($rawList)) {
    echo "Daftar berkas/folder di remote (" . count($rawList) . " item):\n";
    foreach (array_slice($rawList, 0, 15) as $item) {
        echo " - {$item}\n";
    }
    if (count($rawList) > 15) {
        echo " ... dan " . (count($rawList) - 15) . " item lainnya.\n";
    }
}

ftp_close($conn);
echo "\n[SUKSES] Koneksi FTP siap digunakan!\n";
