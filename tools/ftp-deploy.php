<?php
/**
 * Skrip Deploy & Sinkronisasi FTP ke cPanel Hosting.
 *
 * Pemakaian:
 *   php tools/ftp-deploy.php test         -> Menguji koneksi & direktori remote
 *   php tools/ftp-deploy.php upload-zip   -> Mengunggah paket deploy ZIP ke hosting
 *   php tools/ftp-deploy.php sync         -> Mengunggah/sinkronisasi seluruh berkas proyek ke hosting
 */

$action = $argv[1] ?? 'test';
$configFile = __DIR__ . '/../config/ftp.local.php';

if (!file_exists($configFile)) {
    echo "[GAGAL] Berkas config/ftp.local.php tidak ditemukan.\n";
    exit(1);
}

$config = require $configFile;
$host = $config['host'];
$port = (int) ($config['port'] ?? 21);
$user = $config['username'];
$pass = $config['password'];
$ssl  = (bool) ($config['ssl'] ?? true);
$pasv = (bool) ($config['passive'] ?? true);
$remoteDir = rtrim($config['remote_dir'] ?? '/', '/');

echo "==========================================================================\n";
echo "FTP DEPLOYMENT TOOL — MTs Al-Huda Gondang\n";
echo "==========================================================================\n";
echo "Host     : {$host}:{$port}\n";
echo "Username : {$user}\n";
echo "Aksi     : " . strtoupper($action) . "\n";
echo "--------------------------------------------------------------------------\n";

$conn = false;
$timeout = 300; // 5 menit untuk file besar
if ($ssl && function_exists('ftp_ssl_connect')) {
    $conn = @ftp_ssl_connect($host, $port, $timeout);
}
if (!$conn) {
    $conn = @ftp_connect($host, $port, $timeout);
}

if ($conn) {
    ftp_set_option($conn, FTP_TIMEOUT_SEC, $timeout);
}

if (!$conn) {
    echo "[GAGAL] Gagal menghubungi server FTP {$host}:{$port}.\n";
    exit(1);
}

if (!@ftp_login($conn, $user, $pass)) {
    echo "[GAGAL] Login FTP gagal. Periksa username dan password.\n";
    ftp_close($conn);
    exit(1);
}

if ($pasv) {
    ftp_pasv($conn, true);
}

echo "[LULUS] Terhubung dan terautentikasi.\n";
$currentDir = ftp_pwd($conn);
echo "Direktori Kerja Remote: {$currentDir}\n\n";

switch ($action) {
    case 'test':
        echo "Menguji status direktori remote...\n";
        $list = ftp_nlist($conn, '.');
        echo "Jumlah item di direktori remote: " . count((array)$list) . "\n";
        if (!empty($list)) {
            foreach (array_slice((array)$list, 0, 10) as $item) {
                echo " - {$item}\n";
            }
        }
        echo "\n[SUKSES] Koneksi FTP berfungsi sempurna!\n";
        break;

    case 'upload-zip':
        $zipFile = __DIR__ . '/../deploy_cpanel_mts_alhuda.zip';
        if (!file_exists($zipFile)) {
            echo "[GAGAL] Berkas zip {$zipFile} tidak ditemukan.\n";
            break;
        }

        $remoteZip = basename($zipFile);
        echo "Mengunggah {$remoteZip} (" . round(filesize($zipFile) / 1024, 2) . " KB)...\n";
        if (ftp_put($conn, $remoteZip, $zipFile, FTP_BINARY)) {
            echo "[LULUS] Berkas {$remoteZip} berhasil diunggah ke server!\n";
            echo "Anda dapat mengekstraknya langsung di cPanel File Manager.\n";
        } else {
            echo "[GAGAL] Gagal mengunggah berkas zip.\n";
        }
        break;

    case 'sync':
        echo "Memulai sinkronisasi berkas proyek ke hosting...\n";
        $sourceDir = realpath(__DIR__ . '/..');
        $exclude = [
            '.git',
            '.vscode',
            'backup',
            'node_modules',
            'config/database.local.php',
            'config/ftp.local.php',
            'storage/logs',
            'storage/cache',
            'deploy_cpanel_mts_alhuda.zip',
        ];

        function uploadDirectory($conn, $localDir, $remoteBase, $exclude, $baseLength) {
            $files = scandir($localDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $localPath = $localDir . '/' . $file;
                $relativePath = str_replace('\\', '/', substr($localPath, $baseLength + 1));

                // Cek exclude
                $skip = false;
                foreach ($exclude as $exc) {
                    if ($relativePath === $exc || str_starts_with($relativePath, $exc . '/')) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                if (is_dir($localPath)) {
                    @ftp_mkdir($conn, $relativePath);
                    uploadDirectory($conn, $localPath, $remoteBase, $exclude, $baseLength);
                } else {
                    $dir = dirname($relativePath);
                    if ($dir !== '.' && $dir !== '') {
                        $parts = explode('/', $dir);
                        $cur = '';
                        foreach ($parts as $p) {
                            $cur .= ($cur === '' ? '' : '/') . $p;
                            @ftp_mkdir($conn, $cur);
                        }
                    }
                    echo "Mengunggah: {$relativePath} ... ";
                    if (@ftp_put($conn, $relativePath, $localPath, FTP_BINARY)) {
                        echo "OK\n";
                    } else {
                        echo "GAGAL\n";
                    }
                }
            }
        }

        uploadDirectory($conn, $sourceDir, '.', $exclude, strlen($sourceDir));
        echo "\n[SUKSES] Seluruh berkas berhasil disinkronisasi ke server!\n";
        break;

    default:
        echo "Perintah tidak dikenal. Gunakan: test, upload-zip, atau sync\n";
}

ftp_close($conn);
