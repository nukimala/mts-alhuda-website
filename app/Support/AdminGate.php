<?php
/**
 * Penjaga akses panel admin.
 *
 * Semua controller admin memakai ini di baris pertama. Tujuannya satu:
 * aturan akses ditulis SEKALI. Kalau setiap controller mengulang
 * `if (!Auth::check()) header(...)`, cepat atau lambat ada satu yang lupa —
 * dan halaman itu terbuka tanpa login.
 *
 * Dua tingkat:
 *   requireLogin()  — harus punya sesi; must_change_password memaksa ke
 *                     halaman ganti sandi dulu.
 *   can($ability)   — apakah role saat ini boleh melakukan aksi tersebut.
 *
 * Role dan kemampuannya:
 *   admin      : semuanya
 *   operator   : konten, PPDB, pesan
 *   kepala     : lihat saja (tidak menulis)
 *   akreditasi : konten baca + akreditasi
 *   guru/siswa : tidak boleh masuk panel sama sekali
 */

class AdminGate
{
    /** Role yang boleh membuka panel admin. */
    private const PANEL_ROLES = ['admin', 'operator', 'kepala', 'akreditasi'];

    /** Kemampuan tulis per role. */
    private const ABILITIES = [
        'admin'      => ['content.write', 'ppdb.write', 'messages.write', 'users.write', 'settings.write'],
        'operator'   => ['content.write', 'ppdb.write', 'messages.write'],
        'kepala'     => [],
        'akreditasi' => ['content.write'],
    ];

    /**
     * Wajib login. Mengembalikan data pengguna, atau null kalau sudah
     * dialihkan (pemanggil harus `return` saat menerima null).
     *
     * @return array<string,mixed>|null
     */
    public static function requireLogin(): ?array
    {
        if (!Auth::check()) {
            header('Location: ' . base_url('admin/login'));
            return null;
        }

        $user = Auth::user();

        if ($user === null) {
            header('Location: ' . base_url('admin/login'));
            return null;
        }

        // Kata sandi sementara belum diganti: kunci semua halaman lain.
        if ((int) $user['must_change_password'] === 1) {
            header('Location: ' . base_url('admin/ganti-sandi'));
            return null;
        }

        if (!in_array((string) $user['role'], self::PANEL_ROLES, true)) {
            Auth::logout();
            header('Location: ' . base_url('admin/login'));
            return null;
        }

        return $user;
    }

    /**
     * Apakah role yang sedang masuk punya kemampuan ini.
     */
    public static function can(string $ability): bool
    {
        $role = Auth::role();

        if ($role === null) {
            return false;
        }

        return in_array($ability, self::ABILITIES[$role] ?? [], true);
    }

    /**
     * Hentikan request kalau kemampuan tidak dimiliki.
     */
    public static function requireAbility(string $ability): bool
    {
        if (self::can($ability)) {
            return true;
        }

        http_response_code(403);
        $_SESSION['_flash_error'] = 'Akun Anda tidak punya izin untuk tindakan ini.';

        header('Location: ' . base_url('admin'));
        return false;
    }

    /**
     * Data bersama untuk layout admin.
     *
     * @param array<string,mixed> $user
     * @return array<string,mixed>
     */
    public static function shell(array $user, string $title, string $heading): array
    {
        return [
            'title'   => $title,
            'heading' => $heading,
            'user'    => $user,
            'role'    => Auth::displayRole(),
            'flash'   => self::pullFlash('_flash_success'),
            'flashError' => self::pullFlash('_flash_error'),
        ];
    }

    /**
     * Ambil pesan sekali pakai dari sesi, lalu hapus.
     */
    public static function pullFlash(string $key): ?string
    {
        Auth::start();

        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function flashSuccess(string $message): void
    {
        Auth::start();
        $_SESSION['_flash_success'] = $message;
    }

    /**
     * Simpan nilai sekali pakai di kunci bebas.
     *
     * Dipakai untuk hal yang tidak boleh masuk pesan sukses biasa — misalnya
     * sandi sementara yang harus terlihat jelas tapi hanya sekali.
     */
    public static function flashValue(string $key, string $value): void
    {
        Auth::start();
        $_SESSION[$key] = $value;
    }

    public static function flashError(string $message): void
    {
        Auth::start();
        $_SESSION['_flash_error'] = $message;
    }
}
