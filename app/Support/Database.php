<?php
/**
 * Pembungkus koneksi PDO.
 *
 * Tanpa ORM. Semua query ditulis eksplisit sebagai prepared statement.
 * Koneksi tunggal (singleton) per request.
 *
 * ---------------------------------------------------------------------------
 * Kenapa ada logika port cadangan
 * ---------------------------------------------------------------------------
 * Port bawaan MySQL/MariaDB adalah 3306. Tapi XAMPP di beberapa mesin memakai
 * 3307, dan Laragon kadang 3308. Kalau repo di-clone lalu dijalankan apa
 * adanya di mesin yang port-nya berbeda, hasilnya pesan "tidak bisa
 * menyambung ke database" — padahal servernya hidup, hanya beda pintu.
 *
 * Jadi: kalau port TIDAK diatur sendiri oleh pengguna (masih default), dan
 * koneksi gagal di tingkat jaringan, koneksi dicoba ke beberapa port umum
 * dan yang berhasil dipakai. Hasilnya di-cache.
 *
 * Kalau port DIATUR (lewat config/database.local.php atau env DB_PORT),
 * tidak ada percobaan cadangan sama sekali. Port yang salah harus terlihat
 * sebagai salah, bukan ditutupi.
 *
 * Penting: yang memicu percobaan cadangan hanya kegagalan JARINGAN
 * ("tidak ada server di port itu"). Galat lain — database tidak ada,
 * sandi salah, akses ditolak — dilempar apa adanya. Kalau tidak dibedakan,
 * "sandi root salah" akan dilaporkan sebagai "MariaDB mati", dan orang
 * mencari masalah di tempat yang salah.
 */

class Database
{
    private static ?PDO $pdo = null;

    /** Port yang benar-benar dipakai setelah resolusi. */
    private static ?int $resolvedPort = null;

    /** Port yang dicoba saat port masih default. 3306 lebih dulu: itu bawaan. */
    private const FALLBACK_PORTS = [3306, 3307, 3308, 3309];

    /** Kode galat driver MySQL untuk "tidak bisa menyambung". */
    private const CONNECT_ERROR_CODES = [2002, 2003, 2005, 2006];

    /** @return array<string,mixed> */
    public static function config(): array
    {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/../../config/database.php';
        }

        return $config;
    }

    /**
     * Port yang sedang dipakai. Berguna untuk pesan bantuan di CLI.
     */
    public static function resolvedPort(): int
    {
        return self::$resolvedPort ?? (int) self::config()['port'];
    }

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        self::$pdo = self::open((string) self::config()['database']);

        return self::$pdo;
    }

    /**
     * Koneksi tanpa memilih database — dipakai saat membuat database
     * dan saat memeriksa akun server.
     */
    public static function serverConnection(): PDO
    {
        return self::open(null);
    }

    /**
     * Buka koneksi, dengan percobaan port cadangan bila perlu.
     */
    private static function open(?string $database): PDO
    {
        $config = self::config();
        $port = self::resolvedPort();

        try {
            $pdo = self::makePdo(array_merge($config, ['port' => $port]), $database);
            self::$resolvedPort = $port;

            return $pdo;
        } catch (PDOException $e) {
            if (!self::isConnectFailure($e) || empty($config['port_is_default'])) {
                throw $e;
            }
        }

        // Sampai di sini: port default tidak menjawab. Cari port lain.
        $alternative = self::probeFallbackPorts($port);

        if ($alternative === null) {
            throw new PDOException(
                'Tidak ada server MySQL/MariaDB di port ' . implode(', ', self::candidatePorts($port)) . '. '
                . 'Pastikan MySQL/MariaDB sudah dijalankan (di XAMPP: tombol Start pada MySQL).',
                2002
            );
        }

        $pdo = self::makePdo(array_merge($config, ['port' => $alternative]), $database);
        self::$resolvedPort = $alternative;

        error_log(sprintf(
            '[db] Port %d tidak menjawab, memakai %d. Untuk menghilangkan percobaan ini, tulis port di config/database.local.php.',
            $port,
            $alternative
        ));

        return $pdo;
    }

    /**
     * @return list<int>
     */
    private static function candidatePorts(int $current): array
    {
        return array_values(array_unique(array_merge([$current], self::FALLBACK_PORTS)));
    }

    /**
     * Coba port lain satu per satu, tanpa memilih database.
     * Yang diuji hanya "ada server yang menerima koneksi di port ini".
     */
    private static function probeFallbackPorts(int $current): ?int
    {
        $config = self::config();

        foreach (self::candidatePorts($current) as $candidate) {
            if ($candidate === $current) {
                continue;
            }

            try {
                self::makePdo(array_merge($config, ['port' => $candidate]), null, 3);

                return $candidate;
            } catch (PDOException $e) {
                continue;
            }
        }

        return null;
    }

    private static function isConnectFailure(PDOException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if (in_array($driverCode, self::CONNECT_ERROR_CODES, true)) {
            return true;
        }

        $sqlState = (string) $e->getCode();

        return strncmp($sqlState, '08', 2) === 0 || $sqlState === '2002' || $sqlState === '2003';
    }

    /**
     * @param array<string,mixed> $config
     * @param int|null $timeout detik; null = pakai default PDO
     */
    public static function makePdo(array $config, ?string $database, ?int $timeout = null): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $config['host'],
            (int) $config['port'],
            $config['charset'] ?? 'utf8mb4'
        );

        if ($database !== null && $database !== '') {
            $dsn .= ';dbname=' . $database;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        if ($timeout !== null) {
            $options[PDO::ATTR_TIMEOUT] = $timeout;
        }

        $pdo = new PDO($dsn, (string) $config['username'], (string) $config['password'], $options);

        // Mode ketat. Tanpa ini MariaDB menerima nilai ENUM di luar daftar
        // dan menyimpannya sebagai string kosong — kerusakan data yang senyap.
        $pdo->exec(
            "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
        );

        // Semua DATETIME disimpan dalam WIB. Jam server jadi acuan, bukan jam perangkat.
        $pdo->exec("SET time_zone = '" . str_replace("'", '', (string) ($config['timezone'] ?? '+07:00')) . "'");
        $pdo->exec("SET NAMES " . ($config['charset'] ?? 'utf8mb4') . " COLLATE " . ($config['collation'] ?? 'utf8mb4_unicode_ci'));

        return $pdo;
    }

    /**
     * Buat database kalau belum ada.
     */
    public static function ensureDatabase(): bool
    {
        $config = self::config();
        $name = (string) $config['database'];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Nama database tidak valid: ' . $name);
        }

        $pdo = self::serverConnection();
        $exists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'
        );
        $exists->execute([$name]);

        if ((int) $exists->fetchColumn() > 0) {
            return false;
        }

        $pdo->exec(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $name
        ));

        return true;
    }

    public static function reset(): void
    {
        self::$pdo = null;
        self::$resolvedPort = null;
    }
}
