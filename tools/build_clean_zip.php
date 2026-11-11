<?php
/**
 * Buat berkas ZIP yang kompatibel dengan Linux (menggunakan '/' sebagai pemisah direktori).
 */

$root = realpath(__DIR__ . '/..');
$zipFile = $root . '/deploy_cpanel_mts_alhuda.zip';

if (file_exists($zipFile)) {
    unlink($zipFile);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Gagal membuat zip\n");
}

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

$rootLen = strlen($root);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$count = 0;
foreach ($iterator as $item) {
    $path = $item->getRealPath();
    $relative = str_replace('\\', '/', substr($path, $rootLen + 1));

    // Cek exclude
    $skip = false;
    foreach ($exclude as $exc) {
        if ($relative === $exc || str_starts_with($relative, $exc . '/')) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    if ($item->isDir()) {
        $zip->addEmptyDir($relative);
    } else {
        $zip->addFile($path, $relative);
        $count++;
    }
}

$zip->close();
echo "Paket ZIP bersih berhasil dibuat: {$zipFile}\n";
echo "Jumlah berkas: {$count} (Ukuran: " . round(filesize($zipFile) / (1024*1024), 2) . " MB)\n";
