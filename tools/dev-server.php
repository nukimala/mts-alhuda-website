<?php
/**
 * Router untuk server bawaan PHP (localhost).
 *
 *   php -S 127.0.0.1:8080 -t public tools/dev-server.php
 *
 * Server bawaan PHP tidak membaca .htaccess, jadi berkas ini menirukan
 * aturan rewrite yang dipakai di hosting:
 *   - berkas statis (css/js/assets/uploads) dilayani apa adanya,
 *   - sisanya masuk ke public/index.php.
 *
 * Berkas ini TIDAK dipakai di hosting. Jangan hapus .htaccess karenanya.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uri = rawurldecode($uri);
$file = __DIR__ . '/../public' . $uri;

// Berkas statis yang benar-benar ada: biarkan server yang melayani.
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Tirukan .htaccess: SCRIPT_NAME selalu /index.php supaya base_path() = ''.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

require __DIR__ . '/../public/index.php';
