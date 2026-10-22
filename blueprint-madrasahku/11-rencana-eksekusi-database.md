# 11 — Rencana Eksekusi Database

> **STATUS: SUDAH DIEKSEKUSI — 23 Oktober 2026.**
>
> Seluruh 7 batch selesai. 14 migrasi, **42 tabel** (40 domain + 2 sistem) terpasang di
> MariaDB 10.4.32. Dokumen ini dipertahankan sebagai catatan keputusan: alasan tiap tabel
> dibentuk seperti sekarang, dan batas-batas yang diketahui.
>
> Yang belum dikerjakan dari rencana ini: §9 (cutover halaman publik ke database) dan
> pengisian teks resmi 33 butir IA2024 (§14, menunggu mitra).
>
> Target: MySQL/MariaDB sebagai sumber kebenaran tunggal, 40 tabel domain, 6 domain, tanpa ORM pihak ketiga.
>
> Terakhir diperbarui: 23 Oktober 2026

---

## 1. Kondisi saat ini (hasil audit)

| Item | Status |
|---|---|
| `config/database.php` | **0 byte** — belum ada koneksi sama sekali |
| File `.sql` / migrasi | **tidak ada** di seluruh repo |
| `app/Models/*` | **semua 0 byte** (Berita, Admin, Akademik, Ppdb, dll) |
| Controller publik | hanya menyiapkan `$data['title']`, isi halaman masih **hardcoded di view** |
| Database `mts_alhuda` | belum ada |
| MariaDB 10.4.32 | terpasang di `C:\xampp\mysql\bin`, **service sedang mati** |
| PHP 8.2.12 | `pdo_mysql`, `mysqli`, `mbstring`, `openssl`, `json` semua aktif |
| `.gitignore` | **tidak ada** — kredensial DB akan ikut ter-commit kalau tidak dibuat |

**Implikasi penting:** website publik saat ini 100% statis. Begitu database masuk, bukan hanya
tambah tabel — halaman publik (`home`, `berita`, `galeri`, `fasilitas`, `ppdb`, `profil`) harus
diubah dari hardcoded view menjadi query. Ini pekerjaan terpisah dari migrasi dan harus dihitung.

---

## 2. Prinsip yang dipakai

1. **MySQL adalah sumber kebenaran tunggal.** Tidak ada cache eksternal, tidak ada sinkronisasi ke layanan luar.
2. **Tanpa ORM.** PDO prepared statement, SQL eksplisit, mudah dibaca dan diaudit.
3. **Migrasi bernomor dan tidak boleh diubah setelah dijalankan.** Perubahan = file migrasi baru.
4. **Setiap migrasi punya pasangan rollback.** Kalau tidak bisa di-rollback, harus dinyatakan di file.
5. **Skema dulu, seeder kemudian.** Data contoh tidak boleh bercampur dengan struktur.
6. **Tidak ada data siswa/guru yang di-seed palsu di produksi.** Seeder produksi hanya akun operator + pengaturan.

---

## 3. Konvensi teknis

| Aspek | Ketentuan |
|---|---|
| Engine | `InnoDB` (wajib, untuk FK + transaksi) |
| Charset | `utf8mb4` / `utf8mb4_unicode_ci` — **bukan** `utf8mb4_0900_ai_ci` (itu khusus MySQL 8, error di MariaDB 10.4) |
| Primary key | `id BIGINT UNSIGNED AUTO_INCREMENT` |
| Foreign key | `<tabel_tunggal>_id`, contoh `user_id`, `class_id` |
| Timestamp | `created_at`, `updated_at` — `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| Soft delete | hanya pada `posts`, `pages`, `galleries`, `facilities`, `users`, `evidence` → kolom `deleted_at DATETIME NULL` |
| Boolean | `TINYINT(1)` dengan nama berawalan `is_` / `has_` |
| Enum | `ENUM(...)` untuk nilai yang benar-benar tertutup; `VARCHAR` kalau masih mungkin bertambah |
| Status akreditasi | `ENUM('draft','submitted','approved','rejected')` |
| Zona waktu | `DATETIME` disimpan dalam WIB, koneksi set `time_zone = '+07:00'` |
| Nama DB | `mts_alhuda` |
| Collation nama tabel | `snake_case`, jamak (`users`, `posts`, `evidence`) |

**Aturan ID publik:** tabel yang datanya keluar lewat API mobile mendapat kolom tambahan
`public_id CHAR(36)` UNIQUE berisi UUID v4. Tujuannya supaya ID berurutan (`id=1,2,3`) tidak bisa
ditebak lewat API — mencegah IDOR. Tabel yang kena: `assignments`, `submissions`, `materials`,
`schedules`, `notifications`, `teacher_attendances`.

---

## 4. Struktur folder yang akan dibuat

```
config/
  database.php              # return array; baca override dari database.local.php
  database.local.php        # kredensial asli — MASUK .gitignore
  database.example.php      # contoh, aman di-commit

database/
  migrations/
    001_schema_migrations.sql
    002_identity_core.sql
    003_identity_security.sql
    004_cms_content.sql
    005_cms_support.sql
    006_academic_core.sql
    007_academic_learning.sql
    008_teacher_attendance.sql
    009_notifications.sql
    010_accreditation_structure.sql
    011_accreditation_evidence.sql
    012_accreditation_documents.sql
  seeders/
    001_settings.sql
    002_roles_reference.sql
    003_academic_year_classes.sql
    004_subjects.sql
    005_accreditation_ia2024.sql     # 4 komponen + 33 butir (butuh teks dari mitra)
  README.md

tools/
  migrate.php               # CLI: status | up | down | fresh | seed
  db-check.php              # verifikasi integritas setelah migrasi
```

---

## 5. Urutan eksekusi — 7 batch

Dibagi per batch supaya setiap tahap bisa diuji dan di-rollback tanpa menyentuh yang lain.

### Batch 0 — Fondasi
| File | Isi | Tabel |
|---|---|---|
| `001` | `schema_migrations` (version, name, batch, executed_at) + `settings` | 2 |

**Verifikasi:** tabel `settings` bisa diisi 1 baris dan dibaca kembali.

---

### Batch 1 — Identitas & Keamanan (6 tabel)
| File | Tabel |
|---|---|
| `002` | `users`, `students`, `teachers` |
| `003` | `api_tokens`, `password_resets`, `activity_logs` |

Urutan wajib: `users` dulu, karena `students` dan `teachers` bergantung padanya.

**Detail penting:**
- `users.email` UNIQUE, `users.username` UNIQUE (nullable), `password_hash VARCHAR(255)`.
- `users.role ENUM('admin','operator','kepala','guru','siswa','akreditasi')` — lihat §6 keputusan K-1.
- `users.must_change_password TINYINT(1) DEFAULT 1`, `failed_login_attempts INT DEFAULT 0`, `locked_until DATETIME NULL`, `last_login_at DATETIME NULL`.
- `api_tokens.access_token_hash CHAR(64)` dan `refresh_token_hash CHAR(64)` — SHA-256 hex. Keduanya **UNIQUE + INDEX** (dipakai di setiap request API).
- `students.nis` UNIQUE, `teachers.nip` UNIQUE.
- FK: `students.user_id`, `teachers.user_id` → `users.id` ON DELETE RESTRICT.

**Verifikasi:** insert 1 user + 1 teacher + 1 token, query token berdasarkan hash, cek waktu < 5 ms.

---

### Batch 2 — Konten Web / CMS (11 tabel)
| File | Tabel |
|---|---|
| `004` | `posts`, `pages`, `galleries`, `facilities`, `announcements`, `agendas` |
| `005` | `menus`, `contact_messages`, `ppdb_documents`, `media` |

**Detail penting:**
- `posts.slug` UNIQUE, `pages.slug` UNIQUE, `announcements.slug` UNIQUE — index pada slug karena dipakai di URL publik.
- `posts.status ENUM('draft','published','archived')` + index gabungan `(status, published_at)` untuk query halaman publik.
- `menus.parent_id` self-FK nullable, `ON DELETE CASCADE`.
- `ppdb_documents.download_count BIGINT UNSIGNED DEFAULT 0` — tanpa menyimpan identitas pengunduh (sesuai batasan privasi).
- `media.checksum CHAR(64)` untuk dedup file.

**Verifikasi:** query halaman publik (`posts WHERE status='published' ORDER BY published_at DESC LIMIT 5`) pakai index, bukan full scan.

---

### Batch 3 — Akademik (4 + 4 tabel)
| File | Tabel |
|---|---|
| `006` | `academic_years`, `classes`, `subjects`, `class_subjects` |
| `007` | `schedules`, `materials`, `assignments`, `submissions` |

**Detail penting:**
- `academic_years.is_active` hanya boleh satu baris aktif → lihat §6 keputusan K-3.
- `classes.homeroom_teacher_id` → `teachers.id` ON DELETE SET NULL.
- `schedules` perlu `teacher_id` tambahan (denormalisasi) supaya pengecekan bentrok guru bisa langsung — lihat K-2.
- `submissions` UNIQUE `(assignment_id, student_id)` — mencegah siswa mengumpulkan dua kali.

**Verifikasi:** coba insert jadwal bentrok (guru sama, hari sama, jam tumpang tindih) → harus ditolak.

---

### Batch 4 — Presensi Guru (1 tabel)
| File | Tabel |
|---|---|
| `008` | `teacher_attendances` |

**Detail penting — ini tabel paling rawan bug:**
- `attendance_type ENUM('daily','session')` — dua jenis presensi dalam satu tabel.
- Untuk `daily`, `schedule_id` bernilai NULL. **Masalah:** `UNIQUE(teacher_id, attendance_date, schedule_id)` di MySQL/MariaDB **tidak mencegah duplikat** kalau ada NULL, karena NULL dianggap tidak sama dengan NULL.
- Solusi: generated column + UNIQUE (lihat K-4).
- Index `(attendance_date, status)` untuk rekap harian.
- `check_in_at`, `check_out_at` diisi dari **jam server**, bukan input klien.

**Verifikasi:** insert presensi harian guru yang sama dua kali di tanggal yang sama → baris kedua harus **ditolak**.

---

### Batch 5 — Notifikasi (1 tabel)
| File | Tabel |
|---|---|
| `009` | `notifications` |

- Index gabungan `(user_id, is_read, created_at DESC)` — ini query polling utama.
- `type ENUM('assignment','grade','attendance','announcement','system')`.
- Tidak ada tabel device token, tidak ada kolom FCM. Sesuai keputusan bebas Firebase.

**Verifikasi:** query "notifikasi belum dibaca milik user X" pakai index, limit 20.

---

### Batch 6 — Akreditasi IA2024 (12 tabel)
| File | Tabel |
|---|---|
| `010` | `accreditation_periods`, `accreditation_components`, `accreditation_butirs`, `accreditation_indicators` |
| `011` | `evidence`, `evidence_butir`, `dka_entries`, `indicator_checks` |
| `012` | `main_documents`, `tasks`, `accreditation_results`, `follow_ups` |

**Detail penting:**
- `evidence_butir` adalah tabel pivot — satu bukti bisa dipakai banyak butir (ini inti fitur Bank Bukti).
- `dka_entries` butuh `UNIQUE(butir_id, version)` + aturan "hanya satu draft per butir" (K-5).
- `dka_entries.content` dibatasi `CHECK (CHAR_LENGTH(content) <= 1000)` di level DB **dan** validasi di aplikasi.
- `evidence.checksum CHAR(64)` UNIQUE per periode → mencegah unggah file identik berulang.
- `evidence.valid_until` NULL = tidak berbatas. Index `(valid_until, status)` untuk query "menjelang kadaluarsa".
- FK akreditasi memakai `ON DELETE RESTRICT` — data akreditasi tidak boleh hilang diam-diam.

**Verifikasi:** satu bukti ditautkan ke 3 butir → muncul di ketiga butir, tidak terduplikasi.

---

### Rekap

| Batch | Tabel | Kumulatif |
|---|---|---|
| 0 | 2 | 2 |
| 1 | 6 | 8 |
| 2 | 10 | 18 |
| 3 | 8 | 26 |
| 4 | 1 | 27 |
| 5 | 1 | 28 |
| 6 | 12 | **40** |

> Catatan: PRD menyebut 39 tabel. Hitungan rinci PRD menghasilkan 40 baris karena `settings`
> tidak dihitung di daftar domain. Perbedaan ini akan diselaraskan di PRD saat eksekusi
> (tambah keterangan `settings` sebagai tabel sistem), bukan dengan menghapus tabel.

---

## 6. Keputusan yang perlu disetujui sebelum eksekusi

Ini bagian yang harus Bapak putuskan. Saya beri rekomendasi, bukan pilihan netral.

| No | Pertanyaan | Rekomendasi | Alasan |
|---|---|---|---|
| **K-1** | Role `Super Admin` di PRD vs `admin` di tabel `users` — mana yang dipakai? | Pakai `admin` sebagai super admin, tambah kolom `role_label` untuk tampilan "Super Admin" | Satu nilai enum, tidak ada duplikasi makna. Label tampilan dipisah dari nilai data |
| **K-2** | `schedules` tidak punya `teacher_id` — cek bentrok guru jadi berat | Tambah `teacher_id` denormalisasi + UNIQUE `(teacher_id, day_of_week, start_time)` | Pengecekan bentrok jadi satu index lookup, bukan JOIN 3 tabel |
| **K-3** | Bagaimana memastikan hanya 1 tahun ajaran aktif? | Generated column `active_flag` = `IF(is_active=1,1,NULL)` + UNIQUE | MariaDB tidak punya partial index. Ini trik standar |
| **K-4** | Duplikat presensi harian tidak tercegah karena `schedule_id` NULL | Generated column `unique_key` = `CONCAT(teacher_id,'-',attendance_date,'-',attendance_type,'-',IFNULL(schedule_id,0))` + UNIQUE | NULL di UNIQUE tidak mencegah duplikat. Ini bug senyap kalau dibiarkan |
| **K-5** | Satu butir bisa punya banyak draft DKA | Tambah `draft_flag` generated + UNIQUE `(butir_id, draft_flag)` | Mencegah dua orang menulis draft butir yang sama lalu saling menimpa |
| **K-6** | `settings.key` bertabrakan dengan kata kunci SQL `KEY` | Ganti jadi `setting_key` / `setting_value` | Menghindari backtick di setiap query. Lebih murah sekarang daripada nanti |
| **K-7** | Kolom `public_id` UUID untuk API — perlu atau tidak? | Perlu, untuk 6 tabel yang diekspos API | ID berurutan = data siswa/guru bisa dienumerasi dari aplikasi |
| **K-8** | Kredensial DB disimpan di mana? | `config/database.local.php` + `.gitignore`; `database.php` hanya membaca | Repo ini akan di-push. Password root XAMPP jangan ikut |
| **K-9** | Akun operator pertama dibuat bagaimana? | Seeder `tools/migrate.php seed --admin` yang meminta password interaktif | Password tidak pernah ditulis di file SQL yang ter-commit |
| **K-10** | Teks 33 butir IA2024 dari mana? | Mitra menyerahkan instrumen resmi; seeder hanya membuat kerangka kosong | Salah teks butir = seluruh DKA salah dasar |

---

## 7. Seeder — apa yang diisi, apa yang tidak

### Diisi otomatis
| Seeder | Isi |
|---|---|
| `001_settings` | nama madrasah, alamat, telepon, email, jam operasional, koordinat, meta SEO default, aturan presensi (jam masuk 07.00, toleransi 15 menit, jam pulang 15.10) |
| `002_roles_reference` | daftar role valid + label tampilan (tidak membuat akun) |
| `003_academic_year_classes` | 1 tahun ajaran aktif + kelas 7A/7B/8A/8B/9A/9B |
| `004_subjects` | 12 mata pelajaran (Al-Qur'an Hadis, Fikih, Akidah Akhlak, SKI, Bahasa Arab, Matematika, Bahasa Indonesia, Bahasa Inggris, IPA, IPS, PJOK, Seni Budaya) |
| `005_accreditation_ia2024` | 1 periode + 4 komponen + 33 butir (kerangka, teks menyusul) |

### Tidak diisi (menunggu data mitra)
- Akun guru (24), akun siswa (96) — atau impor dari Excel
- Jadwal pelajaran aktual
- Dokumen PPDB (brosur, formulir, tata cara)
- Berita/galeri/fasilitas yang sudah ada
- Bukti akreditasi (148 dokumen)
- Teks lengkap 33 butir IA2024

---

## 8. Migration runner — cara kerjanya

`tools/migrate.php`, murni PHP + PDO, tanpa dependency:

```
php tools/migrate.php status          # daftar migrasi + status sudah/belum
php tools/migrate.php up              # jalankan semua yang belum
php tools/migrate.php up --step=1     # jalankan 1 batch saja
php tools/migrate.php down --step=1   # rollback 1 batch terakhir
php tools/migrate.php fresh           # kosongkan + jalankan ulang (DEV ONLY)
php tools/migrate.php seed            # jalankan seeder
php tools/migrate.php seed --admin    # buat akun operator (password interaktif)
```

Format file migrasi: satu file berisi bagian `-- @UP` dan `-- @DOWN`.

```sql
-- @UP
CREATE TABLE users ( ... ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @DOWN
DROP TABLE IF EXISTS users;
```

Setiap file dijalankan dalam **transaksi**. Kalau gagal di tengah → rollback otomatis, tidak ada
tabel setengah jadi. MariaDB 10.4: DDL tidak transaksional, jadi runner juga mencatat progres per
statement dan berhenti di statement pertama yang gagal, lalu melaporkan posisi persisnya.

**Pengaman:** `fresh` menolak jalan kalau `APP_ENV=production`. Tidak ada akses via web —
hanya CLI.

---

## 9. Cutover halaman publik (pekerjaan terpisah, jangan dilupakan)

Database siap ≠ website jalan. Urutan yang saya sarankan:

| Tahap | Isi | Dampak |
|---|---|---|
| 9.1 | Model `Berita`, `Galeri`, `Fasilitas`, `Ppdb`, `Profil` diisi query PDO | halaman publik baca DB |
| 9.2 | View diubah dari array hardcoded → `foreach ($data['posts'])` | tampilan sama, sumber beda |
| 9.3 | Isi data nyata hasil scraping dari konten statis sekarang → seeder | tidak ada konten hilang saat cutover |
| 9.4 | Fallback: kalau tabel kosong, tampilkan pesan "belum ada konten" — bukan halaman kosong | aman saat DB baru |

Saya **tidak** menyarankan cutover bersamaan dengan migrasi. Migrasi dulu, uji dengan data seed,
baru sambungkan view. Kalau digabung, waktu ada error tidak jelas sumbernya di mana.

---

## 10. Verifikasi setelah setiap batch

Checklist yang harus lulus sebelum lanjut ke batch berikutnya:

```
[ ] Jumlah tabel bertambah sesuai rencana
[ ] Semua tabel ENGINE=InnoDB, CHARSET=utf8mb4
[ ] Semua FK terdaftar di information_schema.KEY_COLUMN_USAGE
[ ] Tidak ada kolom tanpa tipe eksplisit
[ ] Insert data uji → berhasil
[ ] Insert data yang melanggar UNIQUE/FK → ditolak (bukan diam-diam masuk)
[ ] Query utama pakai index (EXPLAIN, bukan full scan)
[ ] Rollback batch ini → bersih, tidak ada sisa tabel
[ ] Jalankan ulang batch ini → berhasil tanpa error
```

`tools/db-check.php` menjalankan 7 poin pertama otomatis dan mencetak laporan.

---

## 11. Backup & rollback

- **Sebelum batch pertama:** `mysqldump mts_alhuda > backup/pre-migration-YYYYMMDD.sql`
- **Sebelum setiap batch:** dump ulang. Ukuran kecil, tidak ada alasan untuk melewatkan.
- **Rollback skema:** `php tools/migrate.php down --step=1`
- **Rollback data:** restore dump terakhir. Hanya kalau rollback skema sudah tidak cukup.
- **Yang tidak bisa di-rollback:** `DROP TABLE` pada tabel yang sudah berisi data nyata. Karena itu
  migrasi `down` hanya diuji di database dev, tidak di produksi.

---

## 12. Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| MariaDB 10.4 tidak mendukung sintaks MySQL 8 (`utf8mb4_0900_ai_ci`, `CHECK` dengan subquery) | migrasi gagal di tengah | semua migrasi ditulis dan diuji langsung di MariaDB 10.4, bukan di MySQL 8 |
| DDL tidak transaksional di MariaDB | tabel setengah jadi kalau gagal | runner berhenti di statement gagal + laporan posisi + dump sebelum jalan |
| Teks 33 butir IA2024 salah | seluruh DKA dan bukti salah dasar | kerangka dulu, teks diisi setelah mitra menyerahkan instrumen resmi |
| ID berurutan bocor ke API | enumerasi data siswa/guru | `public_id` UUID di 6 tabel yang diekspos API |
| Kredensial DB masuk repo | kredensial bocor | `database.local.php` + `.gitignore` dibuat **sebelum** file config pertama |
| Presensi dobel karena NULL di UNIQUE | rekap kehadiran salah, sulit dideteksi | generated column + UNIQUE (K-4), diuji dengan insert ganda |
| Data nyata terhapus saat `fresh` | kehilangan data | `fresh` diblokir di `APP_ENV=production` |
| Cutover publik digabung dengan migrasi | error sulit dilacak | dipisah jadi tahap 9 (lihat §9) |

---

## 13. Estimasi waktu

| Batch | Pekerjaan | Estimasi |
|---|---|---|
| Persiapan | `.gitignore`, `config/database.php`, `database.local.php`, `tools/migrate.php`, `tools/db-check.php` | 2–3 jam |
| Batch 0 | fondasi + settings | 0,5 jam |
| Batch 1 | identitas + keamanan | 1,5 jam |
| Batch 2 | CMS | 2 jam |
| Batch 3 | akademik | 2 jam |
| Batch 4 | presensi guru (paling rawan) | 1,5 jam |
| Batch 5 | notifikasi | 0,5 jam |
| Batch 6 | akreditasi 12 tabel | 3 jam |
| Seeder | 5 seeder | 1,5 jam |
| Verifikasi | `db-check` + uji tiap batch | 2 jam |
| **Total skema** | | **± 16–18 jam** |
| Cutover publik (§9) | terpisah | ± 6–8 jam |

Realistis: **3–4 hari kerja** untuk database siap pakai, di luar cutover.

---

## 14. Yang dibutuhkan dari mitra sebelum eksekusi

Tidak semua bisa dikerjakan tanpa ini:

| No | Kebutuhan | Untuk apa | Kalau belum ada |
|---|---|---|---|
| M-1 | Instrumen resmi IA2024 (teks 33 butir) | isi `accreditation_butirs` | kerangka kosong dulu, DKA tidak bisa diisi |
| M-2 | Daftar guru + NIP + mapel yang diampu | `teachers`, `class_subjects` | seeder contoh saja |
| M-3 | Daftar siswa + NIS + kelas | `students`, `classes` | seeder contoh saja |
| M-4 | Struktur kelas & tahun ajaran aktif | `academic_years`, `classes` | pakai 2026/2027 + 6 kelas asumsi |
| M-5 | Jadwal pelajaran aktual | `schedules` | kosong, diisi manual lewat admin |
| M-6 | Dokumen PPDB (PDF brosur, formulir, tata cara) | `ppdb_documents` | kosong |
| M-7 | Konten berita/galeri/fasilitas yang sudah ada | `posts`, `galleries`, `facilities` | migrasi dari view statis sekarang |
| M-8 | Kredensial SMTP | `settings` | notifikasi email dimatikan dulu |
| M-9 | Nama database & user MySQL produksi | deployment | dev pakai `mts_alhuda` + root lokal |

---

## 15. Checklist go / no-go

Eksekusi baru dimulai kalau semua ini tercentang:

```
[ ] K-1 sampai K-10 sudah diputuskan
[ ] MariaDB service dinyalakan, database mts_alhuda dibuat
[ ] .gitignore sudah ada dan memuat config/database.local.php
[ ] backup/ folder sudah ada
[ ] Instrumen IA2024 sudah diterima (atau sadar bahwa butir akan kosong)
[ ] Setuju bahwa cutover publik dikerjakan terpisah setelah skema stabil
[ ] Setuju rollback hanya diuji di dev, tidak di produksi
```

---

## 16. Langkah pertama saat eksekusi dimulai

Kalau Bapak bilang "gas", urutan yang saya jalankan:

1. Buat `.gitignore` + `config/database.php` + `config/database.example.php`
2. Buat `database/` dan `tools/` beserta `migrate.php` dan `db-check.php`
3. Nyalakan MariaDB, buat database `mts_alhuda` (utf8mb4)
4. Jalankan Batch 0 → verifikasi
5. Lanjut Batch 1 → verifikasi
6. Berhenti, tunjukkan hasil + laporan `db-check`, minta konfirmasi sebelum Batch 2

Berhenti di Batch 1 untuk konfirmasi itu penting: kalau ada yang salah pada tabel `users`,
lebih murah dibatalkan sekarang daripada setelah 40 tabel berdiri.

---

## 17. Yang belum diputuskan di dokumen ini

- Apakah `students.class_id` boleh NULL (siswa tanpa kelas saat mutasi)?
- Apakah `posts` butuh tabel `categories` terpisah, atau cukup `ENUM`?
- Apakah `media` dipakai bersama oleh CMS dan akreditasi, atau dipisah?
- Berapa lama retensi `activity_logs` dan `notifications` sebelum dipangkas?

Empat hal ini tidak menghambat Batch 0–1. Akan diajukan saat mendekati batch terkait.
