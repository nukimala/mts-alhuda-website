<?php
/**
 * Konfigurasi database — versi contoh.
 *
 * Cara pakai:
 *   1. Salin file ini menjadi config/database.local.php
 *   2. Ubah hanya yang berbeda di komputer Anda
 *
 * config/database.local.php sudah masuk .gitignore, jadi aman menyimpan password.
 * File ini (database.example.php) boleh di-commit karena tidak berisi rahasia.
 *
 * ---------------------------------------------------------------------------
 * Sebagian besar orang TIDAK perlu file ini sama sekali.
 * ---------------------------------------------------------------------------
 * Default sudah memakai setelan paling umum: 127.0.0.1:3306, user root,
 * tanpa sandi, database mts_alhuda. Kalau itu cocok dengan XAMPP/Laragon
 * Anda, lewati saja file ini.
 *
 * Buat file ini hanya kalau salah satu benar untuk Anda:
 *   - MySQL/MariaDB Anda di port lain (XAMPP kadang 3307)
 *   - root Anda memakai sandi
 *   - nama database Anda diberi prefix oleh hosting
 */

return [
    'host'      => '127.0.0.1',
    'port'      => 3306,          // ubah ke 3307 kalau MySQL Anda di sana
    'database'  => 'mts_alhuda',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'timezone'  => '+07:00',      // WIB. Jangan diubah kecuali pindah zona waktu
    'app_env'   => 'local',       // local | staging | production
];
