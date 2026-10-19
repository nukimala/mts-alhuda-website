<?php

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
