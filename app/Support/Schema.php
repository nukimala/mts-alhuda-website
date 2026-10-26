<?php
/**
 * Pemeriksa bentuk skema.
 *
 * Kenapa ini ada: kode panel admin harus tetap jalan di database yang
 * migrasinya belum lengkap. Di mesin pengembang semua migrasi jalan; di
 * hosting, migrasi baru bisa diterapkan belakangan. Tanpa pemeriksaan ini,
 * satu kolom yang belum ada membuat seluruh halaman admin gagal 500 —
 * padahal hanya satu fitur kecil yang belum siap.
 *
 * Hasil pemeriksaan di-cache per request. Satu kali SHOW COLUMNS per tabel,
 * bukan per field.
 */

class Schema
{
    /** @var array<string,array<string,bool>> */
    private static array $columns = [];

    /** @var array<string,bool> */
    private static array $tables = [];

    /**
     * Apakah tabel ini ada.
     */
    public static function hasTable(string $table): bool
    {
        if (array_key_exists($table, self::$tables)) {
            return self::$tables[$table];
        }

        try {
            $stmt = Database::connection()->prepare(
                'SELECT 1 FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?
                 LIMIT 1'
            );
            $stmt->execute([$table]);
            $found = $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            $found = false;
        }

        return self::$tables[$table] = $found;
    }

    /**
     * Apakah kolom ini ada di tabel tersebut.
     */
    public static function hasColumn(string $table, string $column): bool
    {
        if (!isset(self::$columns[$table])) {
            self::$columns[$table] = [];

            if (self::hasTable($table)) {
                try {
                    $stmt = Database::connection()->prepare(
                        'SELECT column_name FROM information_schema.columns
                         WHERE table_schema = DATABASE() AND table_name = ?'
                    );
                    $stmt->execute([$table]);

                    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
                        self::$columns[$table][strtolower((string) $name)] = true;
                    }
                } catch (PDOException $e) {
                    // Biarkan kosong: semua hasColumn() mengembalikan false.
                }
            }
        }

        return self::$columns[$table][strtolower($column)] ?? false;
    }
}
