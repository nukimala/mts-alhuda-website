<?php
/**
 * Token CSRF untuk semua form yang mengubah data.
 */

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Auth::start();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Field siap pakai di dalam form.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        Auth::start();

        $session = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($session) || $session === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($session, $token);
    }

    public static function rotate(): void
    {
        Auth::start();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
