<?php
/**
 * Konfigurasi database — pemuat (loader).
 *
 * Urutan prioritas nilai:
 *   1. Variabel environment (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_ENV)
 *   2. config/database.local.php  (tidak masuk repo)
 *   3. Nilai default di file ini
 *
 * File ini hanya MENGEMBALIKAN array. Tidak membuka koneksi.
 * Koneksi dibuat oleh app/Support/Database.php atau tools/migrate.php.
 *
 * ---------------------------------------------------------------------------
 * PORT — kenapa default 3306 dan bukan 3307
 * ---------------------------------------------------------------------------
 * 3306 adalah port bawaan MySQL/MariaDB. Siapa pun yang memasang XAMPP,
 * Laragon, atau MySQL biasa akan mendapat 3306 tanpa mengubah apa pun.
 * Karena itu default repo ini 3306: clone, jalankan, selesai.
 *
 * Mesin pengembang yang memakai port lain (XAMPP di sini memakai 3307)
 * cukup mengisinya di config/database.local.php — file itu tidak masuk repo,
 * jadi pengaturan satu mesin tidak menular ke mesin lain.
 *
 * Kalau port TIDAK diatur di mana pun (murni memakai default), Database.php
 * mencoba port lain yang umum saat 3306 tidak bisa dihubungi, dan menuliskan
 * port mana yang berhasil ke log. Ini menghindari pesan "MariaDB mati" yang
 * sebenarnya hanya salah port. Begitu port diatur eksplisit, tidak ada
 * percobaan cadangan — yang salah harus terlihat sebagai salah.
 */

$defaults = [
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'mts_alhuda',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'timezone'  => '+07:00',
    'app_env'   => 'local',
];

$config = $defaults;

// Penanda: apakah port ditentukan sendiri oleh pengguna, atau masih default.
// Dipakai Database.php untuk memutuskan boleh-tidaknya mencoba port lain.
$config['port_is_default'] = true;

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        if (array_key_exists('port', $local)) {
            $config['port_is_default'] = false;
        }
        $config = array_merge($config, $local);
    }
}

$envMap = [
    'DB_HOST'     => 'host',
    'DB_PORT'     => 'port',
    'DB_NAME'     => 'database',
    'DB_USER'     => 'username',
    'DB_PASS'     => 'password',
    'DB_TIMEZONE' => 'timezone',
    'APP_ENV'     => 'app_env',
];

foreach ($envMap as $envKey => $configKey) {
    $value = getenv($envKey);
    if ($value !== false && $value !== '') {
        if ($configKey === 'port') {
            $config['port'] = (int) $value;
            $config['port_is_default'] = false;
        } else {
            $config[$configKey] = $value;
        }
    }
}

// Port selalu bilangan bulat. Kalau isinya sampah, jatuh ke default.
$config['port'] = (int) $config['port'];
if ($config['port'] <= 0) {
    $config['port'] = $defaults['port'];
    $config['port_is_default'] = true;
}

return $config;
