<?php
/**
 * Bentuk data yang dikirim ke klien.
 *
 * Tiga hal yang mudah salah kalau ditulis ulang di setiap controller:
 *
 *   1. WAKTU. Database menyimpan DATETIME tanpa zona. Klien di perangkat
 *      berbeda zona akan menafsirkannya sendiri kalau yang dikirim
 *      "2026-10-01 09:30:00". Jadi semua waktu keluar sebagai ISO 8601
 *      dengan offset: "2026-10-01T09:30:00+07:00". Tanggal murni (kolom DATE)
 *      tetap keluar sebagai "2026-10-01" — menambahkan jam 00:00 ke tanggal
 *      lahir hanya menimbulkan pertanyaan yang tidak perlu.
 *
 *   2. GAMBAR. Database menyimpan path relatif terhadap folder public/
 *      (mis. "uploads/posts/abc.jpg"). Aplikasi mobile tidak punya cara
 *      menebak host-nya, jadi URL-nya dilengkapkan di server.
 *
 *   3. HALAMAN. per_page dibatasi 100. Tanpa batas, satu permintaan bisa
 *      menarik seluruh tabel dan menghabiskan memori — baik server maupun
 *      perangkat.
 */

final class ApiFormat
{
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 100;

    /**
     * DATETIME MySQL → ISO 8601 dengan offset zona aplikasi.
     */
    public static function iso(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value, new DateTimeZone(date_default_timezone_get()));
        } catch (Exception $e) {
            return null;
        }

        return $date->format('c');
    }

    /**
     * Kolom DATE → "Y-m-d". Jamnya dibuang karena memang tidak ada.
     */
    public static function dateOnly(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value, new DateTimeZone(date_default_timezone_get())))
                ->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Path media → URL absolut.
     *
     * URL yang sudah lengkap (http/https) dibiarkan apa adanya, supaya
     * lampiran yang ditautkan ke layanan luar tidak dirusak.
     */
    public static function mediaUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    /**
     * Alamat halaman web untuk sebuah path. Dipakai untuk kolom `url` supaya
     * aplikasi bisa membuka halaman yang sama dengan versi web kalau perlu.
     */
    public static function webUrl(string $path = ''): string
    {
        return self::baseUrl() . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Skema + host + prefix folder proyek.
     *
     * HTTP_HOST dipakai apa adanya karena di balik Apache proyek ini bisa
     * diakses lewat localhost, IP LAN, atau domain .test — dan ketiganya
     * harus menghasilkan URL yang bisa dibuka dari perangkat yang meminta.
     */
    public static function baseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        return ($https ? 'https' : 'http') . '://' . $host . base_path();
    }

    /**
     * Parameter halaman dari query string.
     *
     * @return array{page:int, per_page:int, offset:int}
     */
    public static function paginate(): array
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        $perPage = filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT);

        $page = ($page === null || $page === false || $page < 1) ? 1 : $page;
        $perPage = ($perPage === null || $perPage === false || $perPage < 1)
            ? self::DEFAULT_PER_PAGE
            : min($perPage, self::MAX_PER_PAGE);

        return [
            'page'     => $page,
            'per_page' => $perPage,
            'offset'   => ($page - 1) * $perPage,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function meta(int $total, int $page, int $perPage): array
    {
        return [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
            'has_next'    => $page * $perPage < $total,
        ];
    }

    /**
     * Nama hari. 1 = Senin ... 7 = Minggu, sesuai konvensi kolom day_of_week.
     */
    public static function dayName(int $day): ?string
    {
        $names = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return $names[$day] ?? null;
    }

    /**
     * "07:30:00" → "07:30". Detik pada jadwal selalu nol dan hanya menambah
     * kebisingan di layar.
     */
    public static function time(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return substr($value, 0, 5);
    }
}
