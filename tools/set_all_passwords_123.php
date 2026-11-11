<?php
/**
 * Utilitas untuk menyetel kata sandi SEMUA pengguna menjadi '123'
 * untuk kemudahan demo dan pengujian lokal.
 *
 * Jalankan via terminal:
 *   php tools/set_all_passwords_123.php
 */

declare(strict_types=1);

require __DIR__ . '/../app/Support/Database.php';

echo "=== Menyetel Kata Sandi Semua Akun Menjadi '123' ===\n";

try {
    $pdo = Database::connection();

    $hash123 = '$2y$10$9dqguLxZXHpljGbpJxsRvONLl/UWk2lutMBSfARhaNd7.CRWi/lcO'; // BCrypt untuk '123'

    $stmt = $pdo->prepare(
        'UPDATE pengguna 
         SET password_hash = ?, 
             must_change_password = 0, 
             failed_login_attempts = 0, 
             locked_until = NULL, 
             is_active = 1,
             updated_at = NOW()'
    );

    $stmt->execute([$hash123]);
    $affected = $stmt->rowCount();

    echo "[SUKSES] Berhasil memperbarui {$affected} akun pengguna!\n";
    echo "Sekarang semua akun (admin, operator, guru, siswa) dapat login dengan kata sandi: 123\n";

} catch (PDOException $e) {
    echo "[GAGAL] Tidak dapat terhubung ke database: " . $e->getMessage() . "\n";
    echo "Pastikan MySQL/MariaDB sudah aktif (di Laragon: Start All).\n";
    exit(1);
}
