<?php

/**
 * Zona waktu aplikasi.
 *
 * PHP di mesin ini default ke Europe/Berlin, sementara MySQL menyimpan WIB.
 * Tanpa ini, nilai waktu yang dibuat PHP (mis. locked_until) akan meleset
 * 7 jam dari nilai yang dibuat MySQL (mis. NOW()), dan perbandingan di SQL
 * jadi salah tanpa terlihat.
 *
 * Semua waktu di sistem ini WIB. Tidak ada pengecualian.
 */
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Jakarta');

if (!function_exists('base_path')) {
    function base_path(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($dir === '.' || $dir === '/') {
            $dir = '';
        }

        if (substr($dir, -7) === '/public') {
            $dir = substr($dir, 0, -7);
        } elseif ($dir === 'public') {
            $dir = '';
        }

        return $dir;
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return base_path() . ($path !== '' ? '/' . $path : '');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}

if (!function_exists('current_route')) {
    function current_route(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri = rawurldecode($uri);

        $base = base_path();
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        $route = trim($uri, '/');

        if (strpos($route, 'public/') === 0) {
            $route = substr($route, 7);
        } elseif ($route === 'public') {
            $route = '';
        }

        return $route;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return base_url($path);
    }
}

if (!function_exists('e')) {
    /**
     * Escape nilai sebelum dicetak di HTML.
     *
     * Semua data dari database atau input pengguna wajib lewat sini.
     * Tanpa ini, judul berita berisi <script> akan ikut dijalankan browser.
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
