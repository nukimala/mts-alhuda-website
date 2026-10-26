<?php
/**
 * Pemformat tampilan.
 *
 * Semua tanggal di sistem ini WIB dan ditampilkan dalam bahasa Indonesia.
 * Sebelumnya setiap view menulis ulang array nama bulan — dan satu view
 * lupa, sehingga bulan tampil dalam bahasa Inggris di satu tempat saja.
 * Sekarang satu sumber.
 */

class Format
{
    private const BULAN = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    private const HARI = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];

    /**
     * "18 Oktober 2026". String kosong kalau tanggalnya kosong/tidak valid.
     */
    public static function date(?string $value): string
    {
        $ts = self::timestamp($value);

        if ($ts === null) {
            return '';
        }

        return date('j', $ts) . ' ' . self::BULAN[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }

    /**
     * "18 Okt 2026" — untuk kolom tabel yang sempit.
     */
    public static function dateShort(?string $value): string
    {
        $ts = self::timestamp($value);

        if ($ts === null) {
            return '';
        }

        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }

    /**
     * "18 Oktober 2026, 14.32 WIB".
     */
    public static function dateTime(?string $value): string
    {
        $ts = self::timestamp($value);

        if ($ts === null) {
            return '';
        }

        return self::date($value) . ', ' . date('H.i', $ts) . ' WIB';
    }

    /**
     * "Senin, 18 Oktober 2026".
     */
    public static function dateLong(?string $value): string
    {
        $ts = self::timestamp($value);

        if ($ts === null) {
            return '';
        }

        $hari = self::HARI[date('l', $ts)] ?? date('l', $ts);

        return $hari . ', ' . self::date($value);
    }

    /**
     * Jarak waktu yang mudah dibaca: "3 menit lalu", "2 hari lalu".
     */
    public static function ago(?string $value): string
    {
        $ts = self::timestamp($value);

        if ($ts === null) {
            return '';
        }

        $diff = time() - $ts;

        if ($diff < 0) {
            return self::dateShort($value);
        }

        if ($diff < 60) {
            return 'baru saja';
        }

        if ($diff < 3600) {
            return (int) floor($diff / 60) . ' menit lalu';
        }

        if ($diff < 86400) {
            return (int) floor($diff / 3600) . ' jam lalu';
        }

        if ($diff < 2592000) {
            return (int) floor($diff / 86400) . ' hari lalu';
        }

        return self::dateShort($value);
    }

    /**
     * Ukuran berkas: "1,4 MB".
     */
    public static function bytes(?int $bytes): string
    {
        $bytes = (int) $bytes;

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1, ',', '.') . ' ' . $units[$power];
    }

    /**
     * Angka dengan pemisah ribuan gaya Indonesia.
     */
    public static function number(int|float|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    /**
     * Potong teks tanpa memutus kata, tambahkan elipsis.
     */
    public static function excerpt(?string $text, int $limit = 140): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');

        if ($text === '' || mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " ,.;:") . '…';
    }

    /**
     * Ubah judul jadi slug URL: huruf kecil, angka, tanda hubung.
     */
    public static function slug(string $text): string
    {
        $text = mb_strtolower(trim($text));

        // Buang tanda baca di tepi kata, bukan di tengah: "Qur'an" -> "qur-an".
        $text = preg_replace("/[^a-z0-9]+/u", '-', $text) ?? '';

        return trim($text, '-');
    }

    /**
     * Timestamp dari string database, atau null kalau kosong.
     */
    private static function timestamp(?string $value): ?int
    {
        if ($value === null || trim($value) === '' || strpos($value, '0000-00-00') === 0) {
            return null;
        }

        $ts = strtotime($value);

        return $ts === false ? null : $ts;
    }
}
