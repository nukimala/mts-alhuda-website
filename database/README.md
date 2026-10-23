# Database — MTs Al-Huda Gondang

Sumber kebenaran tunggal: **MySQL 8.4.3**, database `mts_alhuda`, `utf8mb4_unicode_ci`.
(Skema tetap kompatibel dengan MariaDB 10.4 — lihat catatan di bagian 6.)
Tanpa ORM. Semua query memakai PDO prepared statement.

---

## 1. Koneksi

| Item | Nilai |
|---|---|
| Host | `127.0.0.1` |
| **Port** | **3306** — port bawaan MySQL/MariaDB |
| Database | `mts_alhuda` |
| Charset / Collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| Zona waktu sesi | `+07:00` (WIB) |
| Mode SQL | `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |

### Port: kenapa default 3306, dan kenapa tidak perlu diubah

3306 adalah port bawaan MySQL/MariaDB. Siapa pun yang memasang XAMPP, Laragon, atau
MySQL biasa akan mendapat 3306 tanpa mengubah apa pun. Karena itu default repo ini 3306:
clone, jalankan, selesai.

Mesin pengembang yang memakai port lain (XAMPP di mesin penyusun skema ini memakai 3307)
cukup mengisinya di `config/database.local.php`. File itu tidak masuk repo, jadi setelan
satu mesin tidak menular ke mesin lain:

```php
<?php
// config/database.local.php
return ['port' => 3307];
```

**Port cadangan.** Kalau port tidak diatur di mana pun (murni memakai default 3306), dan
koneksi gagal di tingkat jaringan, `Database.php` mencoba 3306 → 3307 → 3308 → 3309 dan
memakai yang menjawab. Ini mencegah pesan "MariaDB mati" yang sebenarnya hanya salah port.

Yang memicu percobaan cadangan **hanya** kegagalan jaringan. Galat lain — database tidak
ada, sandi salah, akses ditolak — dilempar apa adanya. Kalau tidak dibedakan, "sandi root
salah" akan dilaporkan sebagai "server mati" dan orang mencari masalah di tempat yang salah.

Begitu port diatur eksplisit (lewat `database.local.php` atau `DB_PORT`), **tidak ada**
percobaan cadangan. Port yang salah harus terlihat sebagai salah, bukan ditutupi.

Kredensial asli ada di `config/database.local.php` — **tidak masuk repo**.
`config/database.php` hanya membaca: env → file lokal → default.

### Berpindah tumpukan server (XAMPP ↔ Laragon)

Proyek ini tidak menyimpan path absolut. Semua path memakai `__DIR__`, dan `base_url()`
dihitung dari `SCRIPT_NAME`. Memindahkan folder proyek aman.

Satu-satunya jebakan: **`config/database.local.php` yang mengunci port lama**. Kalau
berkas itu berisi `'port' => 3307` sementara server baru ada di 3306, koneksi gagal tanpa
mencoba port cadangan — karena percobaan cadangan hanya aktif saat port masih default.

Saat pindah: hapus atau sesuaikan `config/database.local.php`, lalu jalankan
`php tools/setup.php`. Skrip itu kini memperingatkan hal ini di awal keluarannya.

`app/Support/ServerLayout.php` mendeteksi tumpukan yang terpasang (XAMPP/Laragon/mandiri)
dan menemukan `php.ini`, `my.ini`, `httpd.conf`, serta port web yang benar. Dipakai
`tools/setup.php` dan `tools/db-share.php` supaya petunjuk yang dicetak tidak menunjuk
berkas yang tidak ada di komputer pengguna.

Variabel environment yang dikenali: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`,
`DB_TIMEZONE`, `APP_ENV`.

### Kenapa mode ketat wajib

Server default XAMPP berjalan **tanpa** `STRICT_TRANS_TABLES`. Akibatnya MariaDB menerima
nilai `ENUM` di luar daftar dan menyimpannya sebagai string kosong — tanpa error. Ini
kerusakan data yang tidak terlihat sampai laporan jadi salah.

`app/Support/Database.php` menyalakan mode ketat di setiap koneksi. Jangan dihapus.

---

## 2. Struktur folder

```
config/
  database.php              # pemuat: env -> local -> default
  database.local.php        # kredensial asli (gitignored)
  database.example.php      # contoh, aman di-commit

app/Support/
  Database.php              # singleton PDO + ensureDatabase()
  ApiAuth.php               # terbit/rotasi/cabut token API
  ApiRouter.php             # router /api/v1, JSON murni
  ApiResponse.php           # bentuk respons seragam
  RateLimiter.php           # pembatas percobaan berbasis tabel
  AuditLog.php              # jejak audit tanpa sesi (untuk API)

app/Controllers/Api/
  ApiAuthController.php     # login, refresh, logout, me, settings, health

database/
  migrations/               # struktur, bernomor, punya @UP dan @DOWN
  seeders/                  # data awal, idempoten (ikut `seed`)
    demo/                   # data contoh, hanya ikut `seed --demo`
  mts_alhuda.sql            # satu berkas siap impor: 42 tabel + data contoh
  README.md                 # dokumen ini

tools/
  setup.php                 # persiapan satu perintah untuk anggota tim baru
  migrate.php               # CLI: status | up | down | fresh | seed
  schema-test.php           # 67 uji integritas skema (dalam transaksi, rollback)
  db-check.php              # verifikasi integritas otomatis (30 pemeriksaan)
  db-share.php              # buat/hapus/uji akun database untuk tim
  api-test.php              # uji end-to-end API v1 (80 pemeriksaan)
```

---

## 3. Konvensi

| Aspek | Ketentuan |
|---|---|
| Engine | `InnoDB` — wajib, untuk FK dan transaksi |
| Charset | `utf8mb4_unicode_ci` — **bukan** `utf8mb4_0900_ai_ci` (khusus MySQL 8, gagal di MariaDB 10.4) |
| Primary key | `id BIGINT UNSIGNED AUTO_INCREMENT` |
| Foreign key | `<tabel_tunggal>_id`, misal `user_id`, `class_id` |
| Timestamp | `created_at`, `updated_at` — `DATETIME`, bukan `TIMESTAMP` |
| Soft delete | hanya `users`, `posts`, `pages`, `galleries`, `facilities`, `evidence` |
| Boolean | `TINYINT(1)`, nama berawalan `is_` / `has_` |
| Enum | untuk nilai yang benar-benar tertutup |
| Nama tabel | `snake_case`, jamak |
| ID publik | `public_id CHAR(36)` UUID untuk tabel yang diekspos API mobile |

### Aturan migrasi

1. Migrasi **tidak boleh diubah** setelah dijalankan. Perubahan = file baru.
2. Setiap migrasi punya `-- @DOWN`. Kalau tidak bisa di-rollback, nyatakan di komentar.
3. Satu file = satu domain. Jangan gabung CMS dan akreditasi.
4. `DROP TABLE` hanya di `@DOWN`, tidak pernah di `@UP`.

### Format berkas

```sql
-- @UP
CREATE TABLE contoh ( ... ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN
DROP TABLE IF EXISTS contoh;
```

---

## 4. Perintah

```bash
php tools/setup.php                     # SEMUA: cek PHP, buat DB, migrasi, seed, admin
php tools/setup.php --no-demo           # sama, tanpa data contoh

php tools/migrate.php status            # daftar migrasi + status
php tools/migrate.php db:create         # buat database kalau belum ada
php tools/migrate.php up                # jalankan semua yang belum
php tools/migrate.php up --step=1       # satu migrasi saja
php tools/migrate.php down --step=1     # rollback satu batch
php tools/migrate.php fresh --force     # kosongkan + jalankan ulang (DEV)
php tools/migrate.php seed              # seeder inti (pengaturan)
php tools/migrate.php seed --demo       # seeder inti + data contoh
php tools/migrate.php seed --admin      # buat akun admin pertama
php tools/schema-test.php               # 67 uji integritas skema
php tools/db-check.php --verbose        # 30 pemeriksaan integritas
```

`fresh` menolak berjalan kalau `APP_ENV=production`, kecuali diberi `--force`.

### Impor satu berkas (tanpa menjalankan migrasi)

Untuk rekan tim yang ingin langsung jalan — atau mesin yang tidak punya PHP CLI —
seluruh skema dan data sudah dibungkus jadi satu berkas:

```
database/mts_alhuda.sql
```

Isinya 42 tabel (hasil 14 migrasi) + pengaturan awal + data contoh, sudah termasuk
akun admin. Berkas ini **membuat** database `mts_alhuda` lalu mengisi tabelnya.

**Lewat HeidiSQL / phpMyAdmin / TablePlus**

1. Sambung ke server (host `127.0.0.1`, port `3306`, user `root`).
2. HeidiSQL: *File → Run SQL file*. phpMyAdmin: tab *Import*.
3. Pilih `database/mts_alhuda.sql`, jalankan.

**Lewat terminal**

```bash
mysql -u root -p < database/mts_alhuda.sql
```

PowerShell tidak mendukung `<`, jadi pakai:

```bash
cmd /c "mysql -u root -p < database/mts_alhuda.sql"
```

**Akun setelah impor**

| Akun | Username | Sandi |
|---|---|---|
| Admin | `admin` | `Admin#AlHuda2026` — wajib diganti saat login pertama |
| Operator | `operator` | `Contoh#AlHuda2026` |
| Guru | `guru` | `Contoh#AlHuda2026` |
| Siswa | `siswa` | `Contoh#AlHuda2026` |

Kalau `config/database.php` Anda memakai nama database lain, ubah baris `USE`
di berkas itu sebelum impor.

**Yang perlu diketahui**

- Impor ulang **menimpa tabel** (`DROP TABLE IF EXISTS` per tabel). Data lama di
  database `mts_alhuda` hilang; database lain tidak tersentuh.
- Berkas ini tidak menggantikan migrasi. Perubahan skema berikutnya tetap dibuat
  sebagai migrasi baru (`015_...sql`), bukan dengan mengedit berkas ini.
- Setelah impor, `php tools/migrate.php status` harus menunjukkan semua 14 migrasi
  sudah terpakai — itu tanda isinya sinkron.

### Data contoh dipisah

Seeder di `database/seeders/*.sql` ikut setiap `seed` — itu data yang memang harus ada
di mana pun, seperti pengaturan situs.

Seeder di `database/seeders/demo/` hanya ikut kalau dipanggil dengan `--demo`. Alasannya
sederhana: `seed` dipakai juga di server sungguhan, dan data contoh di sana bukan sekadar
berisik — bisa tertukar dengan data asli. Halaman berita yang berisi "Contoh Berita Pertama"
di website madrasah yang sudah jalan adalah kesalahan yang sulit ditarik kembali.

Akun contoh (`operator`, `guru`, sandi `Contoh#AlHuda2026`) semuanya
`must_change_password = 1`.

### Seeder hanya menjalankan bagian `@UP`

Berkas seeder boleh punya `-- @DOWN` sebagai dokumentasi cara membersihkan data contoh,
tetapi bagian itu **tidak dieksekusi**. Runner mengambil bagian `@UP` saja lewat
`seederUpSection()`.

Ini bukan detail sepele. Sebelum fungsi itu ada, `splitSql()` mengeksekusi seluruh berkas —
termasuk `@DOWN`. Hasilnya: seeder melaporkan "selesai" padahal tabel tetap kosong, karena
data yang baru dimasukkan langsung dihapus oleh statement `DELETE` di bagian bawah berkas
yang sama. Gejalanya membingungkan: tidak ada error, hanya nol baris.

### Cara kerja runner

- Berkas dibaca, dipisah pada penanda `-- @UP` / `-- @DOWN`.
- Isi SQL dipecah per statement dengan penghormatan pada string, identifier, dan komentar.
- Statement dijalankan satu per satu. **DDL MariaDB tidak transaksional** — kalau statement
  ke-7 gagal, statement 1–6 sudah jadi. Runner berhenti, mencetak nomor statement yang gagal,
  potongan SQL-nya, dan pesan errornya. Tidak ada rollback otomatis yang menipu.
- Karena itu: **selalu dump sebelum batch baru.**

---

## 5. Status eksekusi

| Batch | Migrasi | Tabel | Status |
|---|---|---|---|
| 0 | `001_foundation` | `schema_migrations`, `schema_seeders`, `settings` | **selesai** |
| 1 | `002_identity_core` | `users`, `teachers`, `students` | **selesai** |
| 1 | `003_identity_security` | `api_tokens`, `password_resets`, `activity_logs` | **selesai** |
| 1 | `013_api_support` | `rate_limits` | **selesai** |
| 2 | `004_cms_content` | `posts`, `pages`, `galleries`, `facilities`, `announcements`, `agendas` | **selesai** |
| 2 | `005_cms_support` | `menus`, `contact_messages`, `ppdb_documents`, `media` | **selesai** |
| 3 | `006_academic_core` | `academic_years`, `classes`, `subjects`, `class_subjects` | **selesai** |
| 3 | `007_academic_learning` | `schedules`, `materials`, `assignments`, `submissions` | **selesai** |
| 4 | `008_teacher_attendance` | `teacher_attendances` | **selesai** |
| 5 | `009_notifications` | `notifications` | **selesai** |
| 6 | `010_accreditation_structure` | `accreditation_periods`, `_components`, `_butirs`, `_indicators` | **selesai** |
| 6 | `011_accreditation_evidence` | `evidence`, `evidence_butir`, `dka_entries`, `indicator_checks` | **selesai** |
| 6 | `012_accreditation_documents` | `main_documents`, `tasks`, `accreditation_results`, `follow_ups` | **selesai** |
| — | `014_public_ids` | `students.public_id`, `teachers.public_id` | **selesai** |

**14 migrasi, 42 tabel.** 40 tabel domain (termasuk `settings` dan `rate_limits`) ditambah
2 tabel sistem (`schema_migrations`, `schema_seeders`).

Seeder yang sudah jalan: `001_settings` (pengaturan situs) dan `DEMO-002_demo_content`
(data contoh — 4 pengguna termasuk 1 guru dan 1 siswa, 4 berita, 2 halaman, 6 fasilitas,
8 menu, 13 mapel, 3 kelas, 1 tahun ajaran, 3 kelas-mapel, 2 jadwal, 2 tugas, 1 materi,
1 pengumuman, 1 agenda, kerangka akreditasi).

Data guru/siswa/jadwal/tugas itu bukan hiasan: tanpa itu, endpoint `/api/v1/schedules`,
`/api/v1/assignments`, dan `/api/v1/materials` selalu mengembalikan daftar kosong, dan
tim Flutter tidak bisa membedakan "belum ada data" dari "integrasinya salah".

Akun admin pertama sudah dibuat: `admin` / `admin@mts-alhuda.sch.id`, role `admin`,
label `Super Admin`, `must_change_password = 1`.

---

## 6. Keputusan yang sudah diterapkan

| No | Keputusan | Penerapan |
|---|---|---|
| **K-1** | `admin` = super admin, label dipisah | `users.role` ENUM berisi `admin`; `users.role_label` menyimpan "Super Admin" |
| **K-2** | `schedules.teacher_id` denormalisasi | dikerjakan di migrasi 007 |
| **K-3** | Satu tahun ajaran aktif | generated column + UNIQUE di migrasi 006 |
| **K-4** | Duplikat presensi harian | generated column + UNIQUE di migrasi 008 |
| **K-5** | Satu draft DKA per butir | generated column + UNIQUE di migrasi 011 |
| **K-6** | `settings.key` → `setting_key` | sudah: `setting_key` / `setting_value` |
| **K-7** | `public_id` UUID | `posts`, `assignments`, `submissions`, `evidence` saat dibuat; `students`, `teachers` via migrasi 014 |
| **K-8** | Kredensial DB | `config/database.local.php` + `.gitignore` |
| **K-9** | Akun admin pertama | `php tools/migrate.php seed --admin` |
| **K-10** | Teks 33 butir IA2024 | kerangka kosong; teks menunggu instrumen resmi mitra |
| **K-11** | Slug unik hanya untuk baris hidup | generated column `slug_live` + UNIQUE di `posts`, `pages` |
| **K-11b** | `schedules.academic_year_id` denormalisasi | UNIQUE bentrok guru memperhitungkan tahun ajaran |
| **K-12** | Hindari nama kolom `text` | `accreditation_butirs.butir_text`, `accreditation_indicators.indicator_text` |
| **K-13** | Komponen akreditasi pakai `code` | `accreditation_components.code` + `number` terpisah |
| **K-14** | Checksum bukti per periode | `UNIQUE (period_id, checksum)`, bukan global |
| **K-15** | `main_documents.doc_type` VARCHAR | daftar dokumen IA2024 belum final dari mitra |

### Kenapa K-11, K-12, K-14, K-15

**K-11 — slug setelah soft delete.** `posts.slug` dan `pages.slug` tidak bisa langsung
UNIQUE: berita yang dihapus akan menahan slug-nya selamanya, dan penulis tidak bisa memakai
ulang judul yang sama. MariaDB tidak punya partial index (`UNIQUE ... WHERE deleted_at IS NULL`),
jadi dipakai generated column:

```sql
slug_live VARCHAR(200) AS (IF(deleted_at IS NULL, slug, NULL)) STORED,
UNIQUE KEY uq_posts_slug_live (slug_live)
```

Baris hidup mendapat nilai slug, baris terhapus mendapat NULL. UNIQUE pada NULL memperbolehkan
berapa pun baris terhapus dengan slug sama, tetapi hanya satu baris hidup.

**K-11b — bentrok jadwal lintas tahun.** `schedules` menyimpan `academic_year_id` secara
denormalisasi. Tanpa itu, UNIQUE `(teacher_id, day_of_week, start_time)` akan memblokir
jadwal tahun depan pada jam dan hari yang sama — padahal itu wajar dan sah.

Batas yang jujur: UNIQUE ini menangkap bentrok yang **jam mulainya sama persis**. Bentrok
sebagian (08:00–09:30 vs 08:30–10:00) tidak bisa ditangkap index biasa dan harus divalidasi
aplikasi sebelum insert. Database tidak menjamin semuanya di sini.

**K-12 — `text` sebagai nama kolom.** `TEXT` adalah tipe data SQL. Memakainya sebagai nama
kolom memaksa backtick di setiap query. Sama seperti jebakan `settings.key` yang sudah
dihindari lewat `setting_key` (K-6).

**K-14 — checksum per periode.** Berkas identik yang diunggah di dua periode akreditasi
berbeda adalah dua bukti yang sah — periodenya berbeda. Karena itu UNIQUE-nya
`(period_id, checksum)`, bukan `checksum` saja.

**K-15 — `doc_type` bukan ENUM.** Daftar dokumen utama mengikuti instrumen IA2024 yang teks
resminya belum diterima. ENUM yang salah menutup pintu koreksi tanpa migrasi baru.

---

## 7. Catatan desain yang perlu diingat

**`students.class_id` — FK ditunda.** Kolomnya dibuat di migrasi 002, tetapi tabel `classes`
baru ada di migrasi 006. Karena migrasi yang sudah jalan tidak boleh diubah, FK-nya
ditambahkan lewat `ALTER TABLE` di 006 dengan nama `fk_students_class`
(`ON DELETE SET NULL`: hapus kelas, siswa tetap ada).

**Presensi: `schedule_id` dan `teacher_id` sama-sama memakai RESTRICT.** Dua alasan berbeda,
kebetulan hasilnya sama.

Untuk `schedule_id`: kalau jadwal dihapus dan `schedule_id` menjadi NULL, presensi per jam
mengajar berubah jadi presensi harian dan `CHECK chk_attendance_type` langsung gagal. Lebih
baik penghapusan ditolak — jadwal yang punya riwayat presensi cukup dinonaktifkan
(`is_active = 0`).

Untuk `teacher_id`: presensi adalah catatan audit. Menghapus guru tidak boleh menghapus
riwayat kehadirannya diam-diam. Guru yang berhenti cukup dinonaktifkan lewat
`users.is_active = 0`.

Selain itu keduanya juga **wajib** RESTRICT di MySQL 8 — lihat bagian berikut.

**Perbedaan MySQL 8 dan MariaDB yang ditemukan saat pindah ke Laragon.** Skema ini dibuat
di MariaDB (XAMPP) lalu dijalankan ulang di MySQL 8.4 (Laragon). Dua aturan MySQL 8 muncul
yang tidak ada di MariaDB:

| Galat | Aturan MySQL 8 | Kolom yang terkena |
|---|---|---|
| `3823` | Kolom yang dipakai `CHECK` tidak boleh punya FK dengan aksi `CASCADE` | `teacher_attendances.schedule_id` |
| `1215` | Base column dari **STORED generated column** tidak boleh punya FK `CASCADE`/`SET NULL`/`SET DEFAULT` | `teacher_attendances.teacher_id` |

`teacher_attendances.unique_key` adalah generated column dari `teacher_id`, `attendance_date`,
`attendance_type`, dan `schedule_id`. Itu sebabnya kedua FK di tabel ini harus RESTRICT.
Tidak ada yang hilang: kolom yang direferensikan adalah `AUTO_INCREMENT` yang tidak pernah
berubah, jadi `ON UPDATE CASCADE` di sini memang tidak pernah terpicu.

Larangan yang sama berlaku untuk 5 generated column lain, tetapi base column-nya bukan
kolom FK, sehingga tidak terpengaruh: `posts.slug_live`, `pages.slug_live`,
`academic_years.active_flag`, `accreditation_periods.active_flag`, `dka_entries.draft_flag`.

Cara memeriksa kalau nanti ada generated column baru:

```sql
-- Kolom yang menjadi base column dari generated column
SELECT TABLE_NAME, COLUMN_NAME, GENERATION_EXPRESSION
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'mts_alhuda' AND GENERATION_EXPRESSION <> '';

-- FK dengan aksi selain RESTRICT/NO ACTION
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME,
       UPDATE_RULE, DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS rc
JOIN information_schema.KEY_COLUMN_USAGE k
  ON k.CONSTRAINT_NAME = rc.CONSTRAINT_NAME AND k.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
WHERE rc.CONSTRAINT_SCHEMA = 'mts_alhuda'
  AND (rc.UPDATE_RULE IN ('CASCADE','SET NULL','SET DEFAULT')
       OR rc.DELETE_RULE IN ('CASCADE','SET NULL','SET DEFAULT'));
```

**Email unik + soft delete.** `users.email` UNIQUE. Kalau akun di-soft-delete, emailnya tetap
terpakai dan tidak bisa didaftarkan ulang. Aturan yang dipakai: saat soft delete, ubah email
menjadi `email#deleted#<id>` dalam satu transaksi. Belum diimplementasikan — dicatat di sini
supaya tidak jadi bug senyap. (Berbeda dengan slug yang sudah ditutup lewat K-11.)

**Token tidak pernah disimpan mentah.** `api_tokens` dan `password_resets` hanya menyimpan
SHA-256 hex (64 karakter). Token asli hanya ada di tangan pengguna. Kalau database bocor,
isi tabel ini tidak bisa dipakai untuk masuk.

**`activity_logs.user_id` ON DELETE SET NULL.** Log audit harus bertahan walau akunnya dihapus.
`api_tokens` dan `password_resets` memakai CASCADE karena token tanpa pemilik tidak berarti apa pun.

**Tidak ada tabel device token, tidak ada kolom FCM.** Notifikasi memakai tabel `notifications`
+ polling in-app + email SMTP. Tidak ada layanan push pihak ketiga.

---

## 8. Database lama

Database `mts_alhuda` versi lama (8 tabel: `admin`, `akademik`, `berita`, `fasilitas`,
`galeri`, `kontak`, `menu`, `profil_sekolah`) dipindahkan ke **`mts_alhuda_legacy`**
sebelum skema baru dibuat. Dump-nya ada di `backup/`.

Isinya masih bisa dipakai sebagai sumber migrasi konten pada tahap cutover halaman publik —
lihat `blueprint-madrasahku/11-rencana-eksekusi-database.md` §9.

---

## 9. Akses untuk tim

Rekan tim **tidak memakai akun `root`**. Setiap orang mendapat akun sendiri
dengan hak terbatas pada satu database. Akun dibuat lewat CLI, bukan lewat
phpMyAdmin, supaya tidak ada yang tidak sengaja memberi hak global.

### Perintah

```bash
php tools/db-share.php info                        # alamat & contoh koneksi
php tools/db-share.php list                        # akun non-root yang ada
php tools/db-share.php create --user=budi --role=developer
php tools/db-share.php create --user=sari --role=readonly --host=192.168.100.%
php tools/db-share.php test --user=budi            # uji koneksi dari IP LAN
php tools/db-share.php drop --user=budi            # hapus akun, data tidak tersentuh
```

### Peran

| Peran | Hak | Untuk siapa |
|---|---|---|
| `readonly` | `SELECT` saja | Analis, dosen pembimbing, demo |
| `app` | `SELECT INSERT UPDATE DELETE` + temporary table | Aplikasi yang berjalan (produksi) |
| `developer` | semua di atas + `CREATE ALTER DROP INDEX` | Tim developer yang mengubah skema |
| `admin` | `ALL PRIVILEGES` pada satu database | Hanya operator database |

Yang **tidak pernah** diberikan tool ini: hak global (`*.*`), `GRANT OPTION`,
akses `root` dari jaringan.

### Alamat yang diserahkan

| Item | Nilai |
|---|---|
| Host | `192.168.100.22` (IP LAN mesin ini, dari `db-share.php info`) |
| Port | `3307` pada mesin ini — default repo tetap `3306` |
| Database | `mts_alhuda` |

`db-share.php info` menghitung sendiri IP LAN dan membuang adaptor virtual
(Hyper-V, WSL, VMware) yang tidak bisa dijangkau komputer lain.

### Aturan

1. Port mengikuti setelan MySQL masing-masing mesin — **jangan** hardcode 3307 di kode.
   Default repo 3306; mesin yang memakai port lain mengisinya di `config/database.local.php`.
2. Satu orang satu akun. Tidak ada akun bersama — kalau ada yang merusak data,
   jejaknya harus bisa ditelusuri.
3. Akun `developer` bisa `DROP TABLE`. Jangan dipakai di komputer yang
   menyambung ke database berisi data nyata.
4. Kredensial tidak masuk repo. Simpan di `config/database.local.php` atau `.env`.
5. Akun dengan `--host=192.168.100.%` hanya bisa dari jaringan lokal. Ini lebih
   aman daripada `%` (dari mana saja).

### Kalau rekan tim tidak bisa menyambung

Periksa berurutan:

```powershell
# 1. MariaDB hidup?
Get-Process mysqld -ErrorAction SilentlyContinue

# 2. Mendengarkan di semua antarmuka? (ganti 3306 kalau port Anda lain)
Get-NetTCPConnection -LocalPort 3306 -State Listen

# 3. Firewall mengizinkan?
Get-NetFirewallRule -DisplayName "*mysql*" | Select DisplayName,Enabled,Direction,Action

# 4. bind-address tidak mengunci ke 127.0.0.1?
Select-String -Path C:\xampp\mysql\bin\my.ini -Pattern "bind-address"
```

Kalau nomor 2 hanya menampilkan `127.0.0.1`, tambahkan `bind-address=0.0.0.0`
di `my.ini` lalu restart MariaDB.

---

## 10. Layer API

Aplikasi mobile **tidak** menyambung ke database. Mereka memakai HTTP API di
`/api/v1/*`. Alasannya ada di `blueprint-madrasahku/12-dokumentasi-api.md` §1.

Ringkas:

| Aspek | Nilai |
|---|---|
| Basis | `/api/v1` |
| Autentikasi | `Authorization: Bearer <access_token>` |
| Access token | 30 menit (`settings.security_api_access_ttl`) |
| Refresh token | 30 hari (`settings.security_api_refresh_ttl`), selalu berotasi |
| Penyimpanan token | hash SHA-256 di `api_tokens`, token asli tidak pernah disimpan |
| Rate limit login | 10 percobaan / 5 menit / IP, tabel `rate_limits` |
| Respons galat | `{ "success": false, "error": { "code", "message" } }` |

Endpoint yang sudah ada:

```
GET  /api/v1/health
GET  /api/v1/settings/public
POST /api/v1/auth/login
POST /api/v1/auth/refresh
POST /api/v1/auth/logout
GET  /api/v1/me
```

Uji seluruhnya:

```bash
php tools/api-test.php
php tools/api-test.php http://localhost/mts-alhuda-website admin "SandiAdminAnda"
```

51 pemeriksaan: bentuk respons, kebocoran data, rotasi token, pencabutan,
validasi, dan rate limit. Skrip ini menolak lulus kalau ada yang salah.

**Prinsip yang dipegang:** semua keluaran API adalah JSON — termasuk saat
terjadi galat fatal. `ApiRouter` membuang output apa pun yang sudah terbentuk
dan menggantinya dengan JSON galat. Klien mobile tidak pernah menerima halaman
error PHP.

