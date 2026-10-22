<?php

/**
 * Mengenali tumpukan server yang dipakai di komputer ini.
 *
 * Proyek ini bisa berjalan di XAMPP, Laragon, atau pasangan Apache+MySQL
 * biasa. Ketiganya menyimpan berkas konfigurasi di tempat yang berbeda.
 * Kalau tool CLI menyebut "edit C:\xampp\mysql\bin\my.ini" padahal pengguna
 * memakai Laragon, petunjuknya salah dan orang mencari berkas yang tidak ada.
 *
 * Yang menentukan adalah LOKASI PROYEK, bukan folder mana yang kebetulan ada.
 * Di mesin yang memasang XAMPP dan Laragon sekaligus, menebak dari keberadaan
 * folder akan salah separuh waktu — dan gejalanya membingungkan: skripnya
 * berjalan benar, tetapi menunjuk port dan berkas milik tumpukan yang salah.
 *
 * Kelas ini hanya MENEMUKAN lokasi. Tidak mengubah berkas apa pun.
 */
class ServerLayout
{
    /**
     * Kunci tumpukan: 'laragon' | 'xampp' | 'lainnya'.
     */
    private static function stackKey(?string $projectRoot = null): string
    {
        $root = str_replace('\\', '/', $projectRoot ?? dirname(__DIR__, 2));

        if (stripos($root, '/laragon/') !== false) {
            return 'laragon';
        }

        if (stripos($root, '/xampp/') !== false) {
            return 'xampp';
        }

        // Proyek di tempat lain (mis. D:\proyek): pakai yang terpasang.
        if (is_dir('C:/laragon')) {
            return 'laragon';
        }

        if (is_dir('C:/xampp')) {
            return 'xampp';
        }

        return 'lainnya';
    }

    /**
     * Nama tumpukan untuk ditampilkan ke pengguna.
     */
    public static function name(?string $projectRoot = null): string
    {
        return match (self::stackKey($projectRoot)) {
            'laragon' => 'Laragon',
            'xampp'   => 'XAMPP',
            default   => 'PHP + MySQL mandiri',
        };
    }

    /**
     * Berkas php.ini yang benar-benar dipakai proses ini.
     */
    public static function phpIni(): string
    {
        $loaded = php_ini_loaded_file();

        return $loaded !== false ? $loaded : '(php.ini tidak ditemukan)';
    }

    /**
     * Berkas konfigurasi MySQL/MariaDB, kalau bisa ditemukan.
     * Urutan pencarian mengikuti tumpukan proyek.
     */
    public static function mysqlIni(?string $projectRoot = null): ?string
    {
        $laragon = [
            'C:/laragon/bin/mysql/*/my.ini',
            'C:/laragon/bin/mariadb/*/my.ini',
        ];

        $xampp = [
            'C:/xampp/mysql/bin/my.ini',
        ];

        $lainnya = [
            'C:/ProgramData/MySQL/MySQL Server */my.ini',
            'C:/ProgramData/MariaDB/*/my.ini',
            '/etc/mysql/my.cnf',
            '/etc/my.cnf',
        ];

        return self::firstExisting(
            self::orderFor(self::stackKey($projectRoot), $laragon, $xampp, $lainnya)
        );
    }

    /**
     * Berkas konfigurasi Apache utama.
     */
    public static function apacheConf(?string $projectRoot = null): ?string
    {
        $laragon = [
            'C:/laragon/bin/apache/httpd-*/conf/httpd.conf',
            'C:/laragon/bin/apache2/httpd-*/conf/httpd.conf',
        ];

        $xampp = [
            'C:/xampp/apache/conf/httpd.conf',
        ];

        $lainnya = [
            '/etc/apache2/apache2.conf',
            '/etc/httpd/conf/httpd.conf',
        ];

        return self::firstExisting(
            self::orderFor(self::stackKey($projectRoot), $laragon, $xampp, $lainnya)
        );
    }

    /**
     * Susun daftar kandidat: tumpukan yang cocok didahulukan.
     *
     * @param list<string> $laragon
     * @param list<string> $xampp
     * @param list<string> $lainnya
     * @return list<string>
     */
    private static function orderFor(string $stack, array $laragon, array $xampp, array $lainnya): array
    {
        return match ($stack) {
            'laragon' => array_merge($laragon, $xampp, $lainnya),
            'xampp'   => array_merge($xampp, $laragon, $lainnya),
            default   => array_merge($lainnya, $laragon, $xampp),
        };
    }

    /**
     * @param list<string> $patterns
     */
    private static function firstExisting(array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') === false) {
                if (is_file($pattern)) {
                    return $pattern;
                }
                continue;
            }

            foreach (glob($pattern) ?: [] as $found) {
                if (is_file($found)) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Port web yang dipakai. Yang dicari, berurutan:
     *   1. C:/laragon/usr/laragon.ini  -> ApachePort=...  (kalau ada)
     *   2. httpd.conf tumpukan proyek -> baris `Listen` pertama
     *   3. 80                         -> bawaan Apache
     *
     * Angka 80 adalah tebakan yang benar untuk hampir semua pemasangan.
     * Fungsi ini tidak pernah gagal — paling hanya alamatnya yang meleset.
     */
    public static function webPort(?string $projectRoot = null): int
    {
        $laragonIni = 'C:/laragon/usr/laragon.ini';
        if (is_file($laragonIni) && self::stackKey($projectRoot) === 'laragon') {
            $isi = (string) file_get_contents($laragonIni);
            if (preg_match('/^\s*ApachePort\s*=\s*(\d+)/mi', $isi, $m)) {
                return (int) $m[1];
            }
        }

        $conf = self::apacheConf($projectRoot);
        if ($conf !== null) {
            $isi = (string) file_get_contents($conf);
            // Ambil Listen pertama yang bukan alamat IP lengkap.
            if (preg_match('/^\s*Listen\s+(?:\d+\.\d+\.\d+\.\d+:)?(\d+)/mi', $isi, $m)) {
                return (int) $m[1];
            }
        }

        return 80;
    }

    /**
     * Petunjuk singkat untuk pengguna: di mana mengubah setelan MySQL.
     */
    public static function mysqlHint(?string $projectRoot = null): string
    {
        return self::mysqlIni($projectRoot)
            ?? 'berkas my.ini/my.cnf tidak ditemukan otomatis';
    }
}
