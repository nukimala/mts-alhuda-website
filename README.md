# Sistem Informasi MTs Al-Huda Gondang

Website portal madrasah, panel admin, Pusat Kendali Akreditasi IA2024, dan REST API untuk
aplikasi mobile **MADRASAHKU**.

Dibangun dengan PHP 8 + MySQL/MariaDB. Tanpa framework, tanpa ORM, tanpa layanan pihak ketiga
untuk autentikasi (tidak ada Firebase, Auth0, Supabase, FCM, atau OneSignal).

---

## Yang dibutuhkan

| Kebutuhan | Versi | Catatan |
|---|---|---|
| PHP | 8.0+ | 8.2 disarankan |
| MySQL / MariaDB | 10.4+ | XAMPP sudah membawa MariaDB |
| Apache | 2.4+ | dengan `mod_rewrite` aktif |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`, `curl` | semuanya aktif di XAMPP bawaan |

---

## Menjalankan di laptop Anda

### 1. Clone

```bash
# Laragon
cd C:\laragon\www
git clone <url-repo> mts-alhuda-website
cd mts-alhuda-website

# XAMPP
cd C:\xampp\htdocs
git clone <url-repo> mts-alhuda-website
cd mts-alhuda-website
```

### 2. Nyalakan Apache dan MySQL

Laragon: tombol **Start All**. XAMPP: tombol **Start** pada **Apache** dan **MySQL**.

### 3. Satu perintah

```bash
# Laragon
C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe tools\setup.php

# XAMPP
C:\xampp\php\php.exe tools\setup.php
```

Perintah itu mengerjakan semuanya:

1. memeriksa PHP dan ekstensi wajib
2. mencari server MySQL (port **3306**, cadangan 3307/3308/3309)
3. membuat database `mts_alhuda`
4. menjalankan 14 migrasi — **42 tabel**
5. mengisi pengaturan dan data contoh
6. membuat akun admin pertama
7. menampilkan alamat yang harus dibuka

### 4. Buka

```
Website      http://localhost/mts-alhuda-website/
Panel admin  http://localhost/mts-alhuda-website/admin/login
API          http://localhost/mts-alhuda-website/api/v1/health
```

Kalau XAMPP Anda memakai port web lain (mis. 8080), alamatnya menyesuaikan —
`setup.php` membaca `httpd.conf` dan menampilkan alamat yang benar.

---

## Tidak perlu mengubah konfigurasi apa pun

Default repo sudah memakai setelan paling umum:

```
host      127.0.0.1
port      3306
database  mts_alhuda
user      root
password  (kosong)
```

Kalau itu cocok dengan XAMPP/Laragon Anda, **lewati bagian ini**.

Kalau MySQL Anda di port lain, atau root punya sandi, buat `config/database.local.php`:

```php
<?php
return [
    'port'     => 3307,
    'password' => 'sandi-root-anda',
];
```

File itu masuk `.gitignore` — tidak akan ter-commit, jadi setelan laptop Anda tidak
menular ke laptop lain.

**Port cadangan.** Kalau port tidak diatur di mana pun dan 3306 tidak menjawab,
koneksi otomatis mencoba 3307, 3308, 3309. Jadi kalau XAMPP Anda ternyata di 3307,
aplikasi tetap jalan tanpa konfigurasi. Pesan port yang dipakai ditulis ke log.
Setelah itu, sebaiknya tulis port-nya di `database.local.php` supaya tidak ada percobaan
setiap request.

---

## Pindah dari XAMPP ke Laragon (atau sebaliknya)

Kode tidak menyimpan path absolut apa pun. Semua path dihitung dengan `__DIR__`, dan alamat
web dihitung dari `SCRIPT_NAME`. Jadi memindahkan folder tidak merusak apa pun.

Yang perlu dikerjakan:

1. Salin folder proyek ke `C:\laragon\www\mts-alhuda-website`
2. **Hapus (atau sesuaikan) `config/database.local.php`**
3. Jalankan `php tools\setup.php` sekali

Laragon memakai Apache **80** dan MySQL **3306** — persis default repo ini, jadi tidak ada
konfigurasi yang perlu dibuat.

### Kenapa `database.local.php` harus dihapus dulu

Ini satu-satunya hal yang benar-benar menggigit saat pindah.

File itu mengunci port secara eksplisit. Kalau isinya masih `'port' => 3307` (sisa XAMPP)
sementara Laragon berjalan di 3306, koneksi **gagal tanpa mencoba port cadangan**. Percobaan
cadangan sengaja dimatikan begitu port diatur sendiri — supaya port yang salah terlihat
sebagai salah, bukan ditutupi.

Jadi: hapus berkasnya, biarkan default 3306 dipakai. Kalau Laragon Anda di port lain, isi
ulang dengan angka yang benar.

`tools/setup.php` sekarang memperingatkan hal ini di baris pertama keluarannya.

### Kalau sudah ada data sungguhan

Peringatan penting: **dump penuh dari MariaDB tidak bisa langsung diimpor ke MySQL 8.**
`mysqldump` menulis perintah `CREATE TABLE` lengkap, termasuk FK yang MariaDB terima tetapi
MySQL 8 tolak (`ERROR 3823` dan `ERROR 1215`). Yang gagal justru saat impor skema, bukan
datanya.

Pilih salah satu:

**A. Database masih berisi data contoh** — jangan pindahkan apa pun. Jalankan `setup.php`
di Laragon dan biarkan skema dibangun ulang oleh MySQL 8. Ini yang dipakai saat proyek ini
pindah, dan seluruh 14 migrasi lulus di MySQL 8.4.3.

**B. Sudah ada data sungguhan** — impor datanya saja, tanpa skema:

```bash
# di XAMPP (port 3307) — hanya INSERT, tanpa CREATE TABLE
mysqldump -u root -P 3307 -h 127.0.0.1 \
  --no-create-info --skip-triggers --complete-insert \
  --single-transaction mts_alhuda > data.sql

# di Laragon (port 3306) — skema dibuat dulu oleh migrasi, baru datanya masuk
php tools/migrate.php up
mysql -u root -P 3306 -h 127.0.0.1 mts_alhuda < data.sql
```

Urutan tabel di `data.sql` mengikuti urutan alfabet, sehingga FK bisa menolak baris anak
yang datang sebelum induknya. Kalau itu terjadi, impor dengan
`SET FOREIGN_KEY_CHECKS = 0;` di awal berkas dan `SET FOREIGN_KEY_CHECKS = 1;` di akhir.
Setelah selesai, jalankan `php tools/db-check.php` untuk memastikan tidak ada baris yatim.

Jangan jalankan `setup.php` setelah impor data — seed contoh akan menimpa pengaturan.

### Alamat di Laragon

Laragon membuat virtual host otomatis per folder proyek, jadi alamatnya bisa berubah:

```
http://mts-alhuda-website.test/
```

Tetapi domain `.test` baru ditambahkan ke berkas `hosts` ketika **Laragon dijalankan sekali
lewat GUI** (tombol Start). Sebelum itu domainnya belum bisa dibuka. `setup.php` memeriksa
hal ini dulu, jadi ia hanya mencetak alamat `.test` kalau alamatnya benar-benar bisa dibuka.

Selama itu, pakai:

```
http://localhost/mts-alhuda-website/
```

Keduanya sama-sama berfungsi tanpa perubahan kode: alamat dasar dihitung dari `SCRIPT_NAME`,
bukan ditulis tetap.

---

Dibuat oleh `seed --demo` (bagian dari `setup.php`). Semua wajib ganti sandi saat login pertama.

| Peran | Username | Sandi |
|---|---|---|
| Operator | `operator` | `Contoh#AlHuda2026` |
| Guru | `guru` | `Contoh#AlHuda2026` |
| Admin | sesuai yang Anda isi saat setup | — |

Akun `admin` dibuat oleh `setup.php` dengan kredensial yang Anda tentukan sendiri.

> Data contoh hanyalah contoh. Isinya bukan pengumuman resmi madrasah. Hapus dengan
> `php tools/migrate.php fresh --force` lalu `php tools/migrate.php seed` sebelum dipakai
> sungguhan.

---

## Struktur proyek

```
app/
  Controllers/        # controller halaman publik, admin, dan API
  Models/             # akses data (prepared statement, tanpa ORM)
  Support/            # Database, Router, ApiAuth, RateLimiter, AuditLog
  Views/              # template PHP
config/
  database.php        # pemuat: env -> local -> default (port 3306)
  database.example.php
  database.local.php  # setelan laptop Anda (gitignored)
database/
  migrations/         # 14 berkas, bernomor, punya @UP dan @DOWN
  seeders/            # data awal
    demo/             # data contoh, hanya dengan `seed --demo`
public/
  index.php           # satu-satunya pintu masuk
tools/
  setup.php           # persiapan satu perintah
  migrate.php         # status | up | down | fresh | seed
  schema-test.php     # 67 uji integritas skema
  db-check.php        # 30 pemeriksaan integritas
  db-share.php        # akun database untuk anggota tim
  api-test.php        # 80 uji end-to-end API
blueprint-madrasahku/ # PRD, diagram, rencana database, dokumentasi API
  mockup/             # 23 layar dummy untuk acuan desain
DESIGN.md             # spesifikasi desain
```

---

## Perintah yang sering dipakai

```bash
php tools/setup.php                  # persiapan awal (aman diulang)
php tools/migrate.php status         # cek migrasi mana yang sudah jalan
php tools/migrate.php up             # jalankan migrasi baru
php tools/migrate.php seed --demo    # isi data contoh
php tools/schema-test.php            # 67 uji integritas skema
php tools/db-check.php               # 30 pemeriksaan integritas
php tools/api-test.php http://localhost/mts-alhuda-website admin "sandi-anda"
```

---

## Untuk tim mobile

Aplikasi mobile **tidak menyambung langsung ke database**. Semua lewat REST API:

```
GET  /api/v1/health
GET  /api/v1/settings/public

POST /api/v1/auth/login
POST /api/v1/auth/refresh
POST /api/v1/auth/logout
GET  /api/v1/me

GET  /api/v1/posts                  daftar berita terbit
GET  /api/v1/posts/{slug}           satu berita lengkap
GET  /api/v1/announcements          pengumuman (sasaran mengikuti peran token)
GET  /api/v1/agendas                agenda, ?past=1 untuk riwayat
GET  /api/v1/facilities             fasilitas aktif
GET  /api/v1/galleries              foto galeri, ?album= untuk satu kegiatan
GET  /api/v1/pages/{slug}           halaman statis (profil, akademik)

GET  /api/v1/schedules              butuh token — jadwal sesuai peran
GET  /api/v1/assignments            butuh token — tugas sesuai peran
GET  /api/v1/assignments/{public_id}  butuh token
GET  /api/v1/materials              butuh token — materi sesuai peran
```

Semua daftar memakai `?page=&per_page=` (maks 100) dan mengembalikan `meta`
berisi `total`, `total_pages`, dan `has_next`.

Dokumentasi lengkap dengan contoh `curl`: `blueprint-madrasahku/12-dokumentasi-api.md`.

Autentikasi memakai header `Authorization: Bearer <access_token>`, bukan cookie.
Access token berlaku 30 menit, refresh token 30 hari dan selalu berotasi.

Endpoint akademik dibatasi di server, bukan di aplikasi: guru hanya menerima
jadwal dan tugas yang dia ampu, siswa hanya kelasnya, staf menerima semuanya.

---

## Kalau ada masalah

| Gejala | Penyebab paling sering |
|---|---|
| `Tidak ada server MySQL/MariaDB di port ...` | MySQL belum Start (Laragon: tombol Start, atau `mysqld` manual) |
| `Access denied for user 'root'` | root punya sandi — isi `password` di `config/database.local.php` |
| `Unknown database 'mts_alhuda'` | jalankan `php tools/setup.php` |
| Halaman 404 semua | `mod_rewrite` mati — aktifkan di `httpd.conf` |
| Halaman tampil tanpa CSS | folder proyek tidak di `htdocs`, atau `base_url` salah |
| `Class "Database" not found` | jalankan lewat Apache, bukan membuka `public/index.php` langsung |

Pemeriksaan cepat:

```bash
php tools/migrate.php status
php tools/db-check.php
```

---

## Status

| Bagian | Status |
|---|---|
| Skema database (42 tabel, 14 migrasi) | selesai |
| Layer API v1 (auth, settings, konten publik, akademik baca-saja) | selesai |
| Akses database untuk tim | selesai |
| Website publik | tampilan selesai, **masih statis** (belum membaca database) |
| Panel admin CRUD | sebagian — beberapa view masih kosong |
| Endpoint API data (berita, jadwal, tugas) | selesai (baca-saja) |
| Integrasi SMTP | belum (butuh kredensial dari mitra) |
| Teks 33 butir IA2024 | belum (menunggu instrumen resmi) |

Rencana lengkap: `blueprint-madrasahku/11-rencana-eksekusi-database.md`.

---

## Lisensi & kepemilikan

Proyek akademik untuk MTs Al-Huda Gondang. Data dan konten milik madrasah.
