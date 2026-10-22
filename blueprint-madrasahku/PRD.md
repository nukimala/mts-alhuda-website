# Product Requirement Document (PRD)
## Sistem Informasi Terpadu & Pusat Kendali Mutu MTs Al-Huda Gondang
### (Website Portal, Pusat Kendali Akreditasi IA2024, & Aplikasi Mobile MADRASAHKU)

---

## 1. Dokumen Kontrol & Metadata

| Atribut | Keterangan |
|---|---|
| **Nama Dokumen** | Product Requirement Document (PRD) — MTs Al-Huda & MADRASAHKU |
| **Versi Dokumen** | v1.2 (Final Architecture & Scope) |
| **Tanggal Terbit** | 22 Oktober 2026 |
| **Status Dokumen** | Disetujui (Baseline Implementasi) |
| **Target Rilis (Demo)** | < 1 Bulan (Sprint 4 Minggu) |
| **Pemilik Produk** | Tim Pengembang Sistem MTs Al-Huda Gondang |
| **Stakeholder Utama** | Kepala Madrasah, Operator Madrasah, Guru, Siswa/Wali, Asesor Akreditasi |

---

## 2. Latar Belakang & Pernyataan Masalah

### 2.1 Konteks
MTs Al-Huda Gondang saat ini memiliki website berupa halaman landing page statis (tampilan visual awal sudah tersedia, namun konten masih *hardcoded*, belum memiliki basis data, belum memiliki panel admin, dan belum terhubung ke sistem fungsional madrasah). Di sisi lain, madrasah menghadapi kebutuhan mendesak:
1. **Akreditasi Madrasah (Instrumen IA2024 BAN-PDM):** Penyiapan dokumen bukti fisik, penyusunan Deskripsi Kinerja Asesi (DKA), dan pemetaan butir selama ini berserakan secara fisik/drive pribadi, menyulitkan monitoring kesiapan menjelang visitasi SISPENA.
2. **Keteraturan Akademik & Presensi Guru:** Siswa dilarang membawa/mengoperasikan smartphone selama jam sekolah, sehingga presensi digital siswa tidak dijadikan fitur. Fokus presensi adalah kehadiran guru (harian dan jam mengajar) sebagai bukti disiplin kerja dan keterlaksanaan pembelajaran untuk IA2024.
3. **Penyebaran Informasi Publik & PPDB:** Publik membutuhkan informasi madrasah yang mutakhir dan dokumen resmi PPDB yang dapat diunduh tanpa keharusan mengisi formulir online rumit yang membebani panitia.
4. **Kebutuhan Multiplatform:** Tersedianya aplikasi mobile (Flutter - MADRASAHKU) untuk mempermudah operasional harian guru di kelas dan akses informasi bagi wali/siswa di rumah, yang terhubung ke satu sumber basis data yang sama dengan website. Autentikasi aplikasi ditangani sendiri oleh backend PHP (tanpa Firebase atau penyedia autentikasi eksternal) agar satu akun berlaku untuk web dan mobile.

### 2.2 Tujuan Produk
Membangun platform terpadu berbasis web (PHP MVC + MySQL) dan mobile (Flutter) yang berfungsi sebagai:
- **Pusat Informasi Publik:** Website profil madrasah yang dinamis dan terkelola.
- **Pusat Informasi & Unduhan PPDB:** Media penyebaran syarat, brosur, dan formulir manual PPDB tanpa form online.
- **Pusat Kendali Akreditasi IA2024:** Bank bukti digital, penyusunan DKA (maks. 1.000 karakter), pelacakan progres kesiapan butir, dan ekspor data siap unggah ke SISPENA.
- **Sistem Pembelajaran & Presensi Guru:** Manajemen jadwal, materi, tugas/feedback, serta presensi kehadiran guru harian dan per jam mengajar.

---

## 3. Persona Pengguna & Peran Sistem (RBAC)

Sistem menerapkan kontrol akses berbasis peran (*Role-Based Access Control* - RBAC) yang ketat:

| Peran | Saluran Akses | Deskripsi & Hak Akses Utama |
|---|---|---|
| **Publik / Calon Wali** | Website Publik | Membaca profil, fasilitas, berita, galeri, agenda; mengunduh dokumen resmi & brosur PPDB; mengirim pesan via form kontak. |
| **Siswa** | Aplikasi Flutter | Melihat jadwal pelajaran pribadi, mengunduh materi, mengumpulkan tugas, melihat nilai & feedback guru. Tidak memiliki fitur maupun kartu QR presensi. |
| **Guru** | Aplikasi Flutter & Web | Melakukan check-in presensi harian dan jam mengajar, mengunggah materi & tugas, memberi nilai & feedback tugas, mengunggah bukti akreditasi yang ditugaskan. |
| **Operator Madrasah** | Panel Admin Web | Mengelola konten website publik (CMS), mengelola dokumen PPDB, membuat & menyinkronkan akun guru/siswa, mengelola jadwal & kelas, mencatat/mengoreksi presensi guru, input data akreditasi. |
| **Kepala Madrasah** | Panel Admin Web | Memantau dashboard agregat akademik & presensi guru, mereview kesiapan butir akreditasi, memberikan status persetujuan/revisi DKA dan bukti. |
| **Tim Akreditasi** | Panel Admin Web | Mengelola bank bukti fisik, memetakan bukti ke butir/indikator IA2024, menyusun teks DKA, mengevaluasi celah kesiapan (*gap analysis*), mengekspor paket akreditasi. |
| **Super Admin** | Panel Admin Web | Hak penuh atas konfigurasi sistem, audit log aktivitas, backup basis data, manajemen peran pengguna, dan reset kredensial. |

---

## 4. Ruang Lingkup Produk (MoSCoW)

### 4.1 In-Scope (Must-Have — Versi 1.0)
1. **CMS Publik Dinamis:** Migrasi data statis berita, galeri, fasilitas, profil madrasah, dan form kontak ke MySQL.
2. **Halaman Informasi & Dokumen PPDB:** Tampilan informasi PPDB terkelola dan fitur unduhan berkas (brosur, formulir cetak offline, juknis) tanpa formulir input online.
3. **Otentikasi & Akun Terpusat:** Pembuatan akun hanya oleh Admin/Operator, satu tabel `users` untuk web dan mobile, login web admin berbasis session PHP, login aplikasi berbasis API token milik backend, dan kewajiban ganti password saat login perdana.
4. **Presensi Kehadiran Guru:** Guru melakukan check-in harian dan/atau check-in jam mengajar via mobile; validasi duplikasi, jadwal, dan waktu server; rekapitulasi serta koreksi presensi di web admin.
5. **Akademik Inti:** Manajemen tahun ajaran, kelas, mata pelajaran, jadwal mingguan, unggah materi, pengumpulan tugas, dan pemberian nilai/feedback tugas.
6. **Pusat Kendali Akreditasi IA2024:**
   - Struktur instrumen 4 Komponen, 14 Butir, dan 59 Indikator.
   - Bank bukti digital (PDF, JPG, DOCX) di penyimpanan privat.
   - Pemetaan *many-to-many* bukti ke butir.
   - Editor DKA (validasi batas 1.000 karakter per butir).
   - Dashboard kesiapan akreditasi (*gap analysis* butir kosong/belum direview).
   - Generator ekspor ringkasan instrumen dan berkas bukti untuk diunggah ke SISPENA.
7. **REST API Backend (`/api/v1`):** Endpoint JSON terenkripsi HTTPS untuk konsumsi aplikasi Flutter dengan verifikasi Bearer API Token.
8. **Notifikasi:** Notifikasi in-app (tabel `notifications`, polling ringan saat aplikasi aktif) dan email SMTP untuk reset password serta pengumuman penting. Tidak ada push notification real-time (tanpa FCM/OneSignal); push dapat ditambahkan di fase berikutnya tanpa mengubah skema data.

### 4.2 Out-of-Scope (Ditunda ke Fase Berikutnya)
- Formulir pendaftaran PPDB online, verifikasi data pendaftar digital, dan seleksi nilai online.
- Pembayaran SPP / integrasi Payment Gateway perbankan.
- Sistem e-Rapor kurikulum penuh (penilaian rapor resmi tetap mengikuti aplikasi RDM Kemenag).
- Seluruh presensi siswa, kartu QR siswa, dan pemindaian QR oleh guru.
- Sinkronisasi API dua arah otomatis dengan database pusat EMIS/Dapodik/SISPENA (proses unggah SISPENA tetap manual menggunakan berkas yang diekspor sistem).
- Fitur chat interaktif / forum diskusi realtime siswa-guru.

---

## 5. Arsitektur Teknis & Prinsip Desain

```
[ Publik ] -------------> [ Website Publik (PHP MVC) ]
                                 |
[ Admin/Operator/Guru ] -> [ Panel Admin Web (PHP MVC) ]
                                 |
                                 v
[ Flutter App ] --------> [ REST API Engine (/api/v1) ]
  (Guru/Siswa)                   |
       |                         v
       | (API Token)    [ MySQL Database Server ] (Single Source of Truth)
       |                         |
       +-- polling notifikasi ---+
                                 |
                        [ SMTP Server ] (Reset Password & Pengumuman)
```

### 5.1 Prinsip Desain Teknis
1. **MySQL Sebagai Single Source of Truth:** Seluruh data akademik, akun, role, log, status presensi guru, notifikasi, dan akreditasi tersimpan di MySQL lokal madrasah/server hosting.
2. **Tanpa Penyedia Identitas Eksternal:** Tidak ada Firebase, FCM, Firestore, Auth0, Supabase Auth, atau penyedia autentikasi/notifikasi push pihak ketiga. Autentikasi dan notifikasi diimplementasikan sendiri oleh backend PHP sehingga tidak ada kredensial/service account yang harus dikelola dan tidak ada lock-in vendor. Satu-satunya layanan eksternal yang dipakai adalah SMTP untuk email transaksional (reset password/pengumuman), dan SMTP tidak menyimpan akun, role, atau hash password.
3. **Satu Sistem Identitas:** Web memakai session PHP, mobile memakai API token yang diterbitkan backend; keduanya membaca hash password, role, dan status dari tabel `users` yang sama.
4. **Penyimpanan Berkas Privat:** Berkas materi, tugas, dan bukti akreditasi disimpan di luar web root (`storage/evidence/`, `storage/materials/`, `storage/ppdb/`) dan hanya dapat diunduh melalui controller berautentikasi untuk mencegah akses publik ilegal.
5. **Validasi Ganda pada API:** Pada setiap request API mobile, backend PHP memvalidasi hash API token (sha256), masa berlaku token, status pencabutan, **dan** memastikan record user di MySQL memiliki status `is_active = 1`. Akun yang dinonaktifkan admin di MySQL langsung tertolak secara instan.
6. **Token Acak dan Dirotasi:** Token API dibuat dengan `random_bytes(32)`, hanya hash-nya yang disimpan, access token berumur pendek (30 menit) dan refresh token (30 hari) dirotasi setiap kali dipakai.

---

## 6. Spesifikasi Persyaratan Fungsional (Functional Requirements)

### Modul 1: Website Publik & CMS
- **FR-PUB-01 (Beranda Dinamis):** Sistem wajib menampilkan profil singkat, statistik madrasah (agregat siswa, guru, akreditasi), program unggulan, dan ringkasan berita terbaru dari basis data.
- **FR-PUB-02 (Katalog Berita & Artikel):** Sistem wajib menyediakan daftar berita dengan pagination dan halaman detail berita berdasarkan slug SEO-friendly (`/berita/{slug}`).
- **FR-PUB-03 (Profil, Fasilitas & Galeri):** Sistem wajib menampilkan sejarah/visi-misi, daftar sarana-prasarana, dan album foto kegiatan madrasah yang dikelola dari admin.
- **FR-PUB-04 (Form Kontak & Aduan):** Pengunjung dapat mengirim pesan melalui form kontak publik dengan validasi anti-spam (honeypot + CSRF). Pesan tersimpan di database dan masuk ke notifikasi panel admin.
- **FR-PUB-05 (Pengelolaan Konten Admin):** Admin dapat membuat, menyunting, menghapus (soft-delete), dan mengatur status publikasi (*draft/published*) untuk berita, fasilitas, galeri, dan halaman profil.

### Modul 2: Informasi & Dokumen PPDB (Tanpa Formulir Input)
- **FR-PDB-01 (Halaman Informasi PPDB):** Sistem wajib menyediakan halaman `/ppdb` yang memuat jadwal gelombang, persyaratan administrasi, diagram alur pendaftaran offline, dan kontak panitia (WhatsApp/telepon).
- **FR-PDB-02 (Katalog Dokumen Unduhan):** Sistem wajib menampilkan daftar dokumen resmi PPDB yang berstatus aktif (brosur, edaran madrasah, formulir manual PDF/DOCX).
- **FR-PDB-03 (Pengunduhan Terproteksi):** Pengunduhan dokumen melalui endpoint controller `/ppdb/dokumen/{id}` yang memvalidasi ketersediaan berkas fisik dan status publikasinya.
- **FR-PDB-04 (Manajemen Dokumen Admin):** Admin dapat mengunggah dokumen baru (maksimal 10 MB per file, format PDF/DOCX/JPG), mengganti dokumen lama, mengatur urutan tampil, dan menonaktifkan dokumen usang.
- **FR-PDB-05 (Penegasan Tanpa Formulir):** Halaman PPDB tidak menyediakan form registrasi data diri calon siswa secara online. Seluruh proses pendaftaran fisik dilakukan offline di madrasah.

### Modul 3: Manajemen Pengguna, Akun & Keamanan
- **FR-USR-01 (Penyediaan Akun Admin-Only):** Akun siswa dan guru hanya dibuat oleh Admin/Operator melalui panel web atau import data tabular. Tidak disediakan tombol pendaftaran akun mandiri pada web maupun aplikasi mobile.
- **FR-USR-02 (Satu Sumber Identitas):** Akun, hash password, role, dan status disimpan hanya di tabel `users` MySQL. Web dan aplikasi mobile memakai kredensial yang sama; tidak ada proses sinkronisasi ke penyedia identitas eksternal (misalnya Firebase Auth, Auth0, atau Supabase Auth).
- **FR-USR-03 (Kewajiban Ganti Password Perdana):** Akun baru dibuat dengan flag `must_change_password = 1`. Saat pertama kali login, sistem mengunci akses menu lain hingga pengguna memperbarui password default.
- **FR-USR-04 (Otentikasi Web Session):** Login admin web menggunakan session PHP dengan mitigasi *Session Fixation* (`session_regenerate_id()`), cookie berflag `HttpOnly` dan `SameSite=Lax`.
- **FR-USR-05 (Reset Password Ganda):** Tersedia opsi reset password mandiri via tautan email bertoken kadaluarsa (60 menit) yang dikirim melalui SMTP, dan opsi override reset password instan oleh Administrator madrasah.
- **FR-USR-06 (Penonaktifan Akun Instan):** Jika admin menyetel status akun menjadi `is_active = 0`, seluruh sesi web user tersebut diinvalidasi, seluruh API token dicabut, dan endpoint API menolak request dengan kode HTTP 401 Unauthorized.
- **FR-USR-07 (API Token Aplikasi):** Aplikasi mobile memperoleh `access_token` (30 menit) dan `refresh_token` (30 hari) dari `POST /api/v1/auth/login`. Token dibuat acak, hanya hash SHA-256 yang disimpan di `api_tokens`, dan setiap penggunaan refresh token mengrotasi token lama.
- **FR-USR-08 (Notifikasi In-App):** Pengumuman dan notifikasi penting disimpan di tabel `notifications`. Aplikasi mengambilnya melalui `GET /api/v1/notifications` (polling ringan saat aktif) dan menandai terbaca melalui `POST /api/v1/notifications/{id}/read`. Email (SMTP) dipakai untuk reset password dan pengumuman resmi.

### Modul 4: Manajemen Akademik Inti
- **FR-AKD-01 (Struktur Data Akademik):** Admin dapat mengonfigurasi tahun ajaran aktif, rombongan belajar (kelas & tingkat), daftar mata pelajaran, dan penugasan guru pengampu (*class-subject mapping*).
- **FR-AKD-02 (Jadwal Pelajaran):** Sistem mendeteksi dan mencegah bentrok jadwal (guru atau ruangan yang sama di hari dan jam yang beririsan).
- **FR-AKD-03 (Materi Pembelajaran):** Guru dapat mengunggah berkas materi (PDF, DOCX, PPTX) atau menyisipkan tautan video referensi yang dapat diakses oleh siswa di kelas terkait.
- **FR-AKD-04 (Tugas Siswa):** Guru dapat membuat tugas dengan tenggat waktu (*deadline*) dan instruksi detail. Siswa dapat mengunggah berkas jawaban tugas sebelum tenggat berakhir.
- **FR-AKD-05 (Penilaian & Catatan Tugas):** Guru dapat memberikan nilai kuantitatif (0–100) dan teks masukan/feedback. Siswa hanya diizinkan melihat nilai dan catatan tugas miliknya sendiri (larangan IDOR).

### Modul 5: Presensi Kehadiran Guru (Tanpa Presensi Siswa)
- **FR-ABS-01 (Cakupan Presensi Guru):** Sistem hanya mencatat presensi untuk pengguna ber-role `guru`. Tidak ada endpoint, tabel, kartu, atau antarmuka presensi siswa.
- **FR-ABS-02 (Check-in Harian):** Guru dapat melakukan check-in kehadiran harian sekali per tanggal melalui aplikasi Flutter. Sistem mencatat `check_in_at` berdasarkan waktu server dan menetapkan status `hadir` atau `terlambat`.
- **FR-ABS-03 (Check-in Jam Mengajar):** Guru dapat melakukan check-in pada jadwal mengajar miliknya. Sistem memvalidasi bahwa jadwal benar milik guru tersebut, sesuai tanggal dan rentang waktu, serta belum pernah di-check-in pada hari itu.
- **FR-ABS-04 (Validasi Presensi Server-Side):**
  - Menolak request dari role selain `guru` (HTTP 403).
  - Menolak check-in ganda untuk tanggal/jadwal yang sama.
  - Menolak check-in di luar rentang jadwal dan toleransi yang dikonfigurasi.
  - Menolak akun yang berstatus `is_active = 0`.
  - Menetapkan status `hadir` atau `terlambat` berdasarkan jam server, bukan jam perangkat.
- **FR-ABS-05 (Pencatatan Izin, Sakit & Dinas Luar):** Operator atau Kepala Madrasah dapat mencatat status `izin`, `sakit`, `dinas_luar`, atau `alpha` beserta catatan. Perubahan dicatat di `activity_logs`.
- **FR-ABS-06 (Rekapitulasi & Ekspor):** Admin dan Kepala Madrasah dapat melihat rekap kehadiran guru per guru, tanggal, dan jadwal, lalu mengekspornya ke Excel/CSV/PDF untuk bukti IA2024.

### Modul 6: Pusat Kendali Akreditasi IA2024
- **FR-AKR-01 (Inisialisasi Periode & Instrumen):** Admin menginisialisasi periode akreditasi baru. Sistem melakukan seeder otomatis struktur instrumen IA2024:
  - Komponen 1: Kinerja Pendidik dalam Mengelola Proses Pembelajaran (4 Butir, 19 Indikator).
  - Komponen 2: Kepemimpinan Kepala Madrasah dalam Pengelolaan Satuan Pendidikan (5 Butir, 21 Indikator).
  - Komponen 3: Iklim Lingkungan Belajar (5 Butir, 19 Indikator).
  - Komponen 4: Kompetensi Hasil Pembelajaran (Indikator Rapor Pendidikan / Asesmen Nasional).
- **FR-AKR-02 (Bank Bukti Digital):** Tim Akreditasi dapat mengunggah dokumen bukti (KSP, RKT, RKAS, RPP, SK, sertifikat, foto/video pembelajaran). Sistem mencatat metadata ukuran, checksum berkas, tanggal unggah, dan tanggal masa berlaku dokumen.
- **FR-AKR-03 (Pemetaan Bukti ke Butir):** Satu berkas bukti dapat dipetakan (*mapped*) ke satu atau lebih butir instrumen secara fleksibel tanpa duplikasi file fisik di server.
- **FR-AKR-04 (Penyusunan Deskripsi Kinerja Asesi - DKA):** Form penyusunan DKA per butir dengan validasi ketat batas karakter: **maksimal 1.000 karakter** (sesuai spesifikasi input SISPENA BAN-PDM). Sistem menyediakan *live character counter*.
- **FR-AKR-05 (Workflow Review Kepala Madrasah):** Setiap butir memiliki siklus status: `belum` → `draft` → `review` → `revisi` (dengan catatan perbaikan) → `selesai` (disetujui Kepala Madrasah).
- **FR-AKR-06 (Dashboard Gap Analysis Kesiapan):** Tampilan visual grafik kesiapan akreditasi yang menyorot:
  - Butir yang belum memiliki bukti fisik.
  - Butir yang DKA-nya belum ditulis.
  - Butir yang masih berstatus revisi/belum disetujui.
  - Dokumen bukti yang telah kadaluarsa masa berlakunya.
- **FR-AKR-07 (Generator Paket Ekspor SISPENA):** Sistem menyediakan fitur sekali klik (*single-click export*) untuk mengunduh:
  - Berkas ZIP paket bukti terstruktur per butir (dilengkapi penamaan file standar SISPENA).
  - Dokumen rekapitulasi DKA seluruh butir (format DOCX/PDF).
  - Lembar checklist kesiapan visitasi untuk asesor internal.

### Modul 7: REST API Engine
- **FR-API-01 (Standar Respons API):** Seluruh endpoint menghasilkan payload JSON konsisten dengan format:
  ```json
  {
    "success": true,
    "message": "Deskripsi pesan status",
    "data": {},
    "errors": []
  }
  ```
- **FR-API-02 (Middleware Otentikasi):** Memverifikasi header `Authorization: Bearer <API_Token>`, mencocokkan hash SHA-256 token dengan tabel `api_tokens`, memeriksa masa berlaku dan status pencabutan, mencocokkan `user_id` dengan basis data MySQL, dan menyuntikkan profil user aktif ke context request.
- **FR-API-03 (Pagination & Filter):** Endpoint daftar (materi, jadwal, riwayat presensi guru) wajib menerapkan paginasi (`page`, `limit`) dan mendukung filter dinamis.

---

## 7. Persyaratan Non-Fungsional (Non-Functional Requirements - NFR)

### 7.1 Keamanan Sistem (Security)
- **NFR-SEC-01:** Seluruh transaksi data web dan API wajib melalui protokol HTTPS (TLS 1.2+).
- **NFR-SEC-02:** Seluruh interaksi database wajib menggunakan PDO Prepared Statements dengan parameter binding (kebal SQL Injection 100%).
- **NFR-SEC-03:** Seluruh form web dilindungi token CSRF bertenggat waktu; seluruh output HTML publik disanitasi menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` untuk menangkal XSS.
- **NFR-SEC-04:** Penanganan unggahan berkas menerapkan *whitelist* ekstensi (hanya `.pdf`, `.jpg`, `.jpeg`, `.png`, `.docx`, `.xlsx`), validasi *MIME-type binary*, dan penamaan ulang file fisik menjadi hash acak (*random UUID filename*).
- **NFR-SEC-05:** Larangan akses langsung terhadap folder `/app`, `/config`, `/database`, dan `/storage` melalui konfigurasi `.htaccess` web server.

### 7.2 Performa & Skalabilitas (Performance)
- **NFR-PRF-01:** Waktu respons API backend (*latency* p95) berada di bawah 1.000 ms pada koneksi jaringan standar.
- **NFR-PRF-02:** Check-in presensi guru memiliki waktu verifikasi transaksi rata-rata ≤ 500 ms per request.
- **NFR-PRF-03:** Halaman web publik mencapai skor audit Google Lighthouse:
  - Performance: ≥ 80
  - Accessibility: ≥ 90
  - Best Practices: ≥ 90
  - SEO: ≥ 90
- **NFR-PRF-04:** First Contentful Paint (FCP) web publik ≤ 2,0 detik; Largest Contentful Paint (LCP) ≤ 2,5 detik pada jaringan 4G.

### 7.3 Aksesibilitas & Responsivitas (Usability)
- **NFR-USB-01:** Antarmuka web publik dan panel admin sepenuhnya responsif terhadap layar mobile (360px), tablet (768px), dan desktop (1280px+).
- **NFR-USB-02:** Kontras warna teks memenuhi standar minimal WCAG 2.1 Level AA (kontras rasio minimal 4.5:1 untuk teks biasa terhadap warna latar).
- **NFR-USB-03:** Panel admin menyediakan umpan balik instan (*flash message* sukses/gagal, dialog konfirmasi hapus, dan indikator pemuatan data).

### 7.4 Keandalan & Integritas Data (Reliability)
- **NFR-REL-01:** Konsistensi referensial basis data ditegakkan melalui Foreign Key Constraint InnoDB dengan kebijakan `ON DELETE RESTRICT` pada transaksi utama.
- **NFR-REL-02:** Sistem menyediakan modul backup database terjadwal/manual dalam format SQL dump terkompresi.

---

## 8. Ringkasan Desain Basis Data (Schema Essentials)

Sistem menggunakan 39 tabel yang terbagi ke dalam 6 domain fungsional:

```
[ Domain Identitas & Keamanan ]
- users (id, name, email, username, password_hash, role, is_active, must_change_password, failed_login_attempts, locked_until, last_login_at, ...)
- students (id, user_id, nis, nisn, class_id, ...)
- teachers (id, user_id, nip, phone, ...)
- api_tokens (id, user_id, access_token_hash, refresh_token_hash, device_name, platform, ip_address, expires_at, refresh_expires_at, last_used_at, revoked_at)
- password_resets (id, user_id, token_hash, expires_at, used_at)
- activity_logs (id, user_id, action, entity, entity_id, metadata, created_at)

[ Domain Konten Web & Publik (CMS) ]
- posts (id, title, slug, excerpt, content, cover_path, status, published_at, author_id)
- pages (id, slug, title, content, meta_title, meta_description, status)
- galleries (id, title, image_path, caption, sort_order, is_active)
- facilities (id, name, description, image_path, sort_order, is_active)
- announcements (id, title, slug, content, published_at, expires_at, status)
- agendas (id, title, description, start_at, end_at, location, status)
- menus (id, label, url, parent_id, sort_order, is_active)
- settings (key, value)
- contact_messages (id, name, email, phone, subject, message, status, created_at)
- ppdb_documents (id, title, description, file_path, file_type, file_size, sort_order, is_active, download_count)
- media (id, path, mime_type, file_size, alt, uploaded_by, created_at)

[ Domain Akademik & Pembelajaran ]
- academic_years (id, name, start_date, end_date, is_active)
- classes (id, name, level, academic_year_id, homeroom_teacher_id)
- subjects (id, code, name)
- class_subjects (id, class_id, subject_id, teacher_id)
- schedules (id, class_subject_id, day_of_week, start_time, end_time, room)
- materials (id, class_subject_id, title, description, file_path, published_at)
- assignments (id, class_subject_id, title, description, due_at, max_score)
- submissions (id, assignment_id, student_id, file_path, score, feedback, submitted_at, graded_at)

[ Domain Presensi Guru ]
- teacher_attendances (id, teacher_id, schedule_id, attendance_date, attendance_type, check_in_at, check_out_at, status, note, verified_by, created_at, updated_at)

[ Domain Notifikasi ]
- notifications (id, user_id, title, body, link, type, is_read, read_at, created_at)

[ Domain Akreditasi IA2024 ]
- accreditation_periods (id, name, start_date, end_date, status, is_active)
- accreditation_components (id, period_id, number, name)
- accreditation_butirs (id, component_id, number, text, status)
- accreditation_indicators (id, butir_id, number, text)
- evidence (id, period_id, title, file_path, mime_type, file_size, checksum, valid_until, status, uploaded_by)
- evidence_butir (id, evidence_id, butir_id)
- dka_entries (id, butir_id, content, version, status, updated_by)
- indicator_checks (id, butir_id, indicator_id, status, note)
- main_documents (id, period_id, type, title, file_path, status)
- tasks (id, period_id, title, assigned_to, due_date, status)
- accreditation_results (id, period_id, grade, score, notes, decided_at)
- follow_ups (id, result_id, recommendation, status, due_date)
```

---

## 9. Desain Antarmuka & Sistem Desain (Design System)

Sistem mempertahankan identitas visual hijau madrasah yang telah terbentuk:

| Elemen Desain | Spesifikasi Teknis |
|---|---|
| **Warna Primer** | `--green: #15803D` (Hijau Utama) & `--dark-green: #0B3D1E` (Hijau Gelap Identitas) |
| **Warna Aksen** | `--light-green: #4ADE80`, `--mint: #A7F3D0`, `--pale-green: #BBF7D0` |
| **Warna Netral** | `--white: #FFFFFF`, `--soft: #F9FAFB` (Latar Belakang), `--text: #111827`, `--border: #E1ECE5` |
| **Tipografi** | `Plus Jakarta Sans`, sans-serif (Bobot: 400 Reguler, 600 Semi-Bold, 700 Bold, 800 Extra-Bold) |
| **Gaya Kartu & Kontainer** | Border-radius: `16px`, Box-shadow: `0 12px 30px rgba(15, 61, 30, .10)`, Max-width kontainer: `1100px` |
| **Struktur Admin Layout** | Sidebar navigasi tetap di kiri, Topbar profil & notifikasi di atas, Breadcrumb dinamis, Canvas konten responsif |

---

## 10. Rencana Rilis, Milestone, & Jadwal Eksekusi 4 Minggu

```
Minggu 1: FONDASI & CMS PUBLIK
[H-01 s/d H-02] Skema Database, PDO Wrapper, Router Core, Middleware CSRF/Auth
[H-03 s/d H-04] Layout Admin Web, Seeder Pengguna Awal, Pembekuan Kontrak REST API
[H-05 s/d H-07] CMS Berita, Profil, Fasilitas, Galeri, Agenda, Kontak Dinamis

Minggu 2: AKADEMIK & AUTENTIKASI API
[H-08 s/d H-10] CRUD Tahun Ajaran, Kelas, Mapel, Penugasan Guru, Jadwal Pelajaran
[H-11 s/d H-12] Modul Unggah Materi, Tugas Siswa, Pengumpulan Berkas & Penilaian
[H-13 s/d H-14] Endpoint Login/Refresh API Token, Verifikasi Token, Notifikasi In-App

Minggu 3: PRESENSI GURU, AKREDITASI IA2024 & PPDB
[H-15 s/d H-17] Endpoint Check-in Harian & Jam Mengajar, Validasi Server & Rekap Presensi Guru
[H-18 s/d H-19] Bank Bukti Akreditasi, Mapping Multi-Butir, Form DKA (1.000 Karakter), Dashboard Gap
[H-20 s/d H-21] Halaman Info & Modul Unduhan PPDB (Tanpa Form), Generator Ekspor Sispena

Minggu 4: STABILISASI, SQA & DEMO FINAL
[H-22 s/d H-24] Eksekusi Uji 41 Test Case SQA, Perbaikan Bug (Zero Critical/High Defect)
[H-25 s/d H-26] Pengujian Audit Keamanan (IDOR, XSS, CSRF, Upload Sanitasi) & Lighthouse
[H-27 s/d H-28] UAT Bersama Tim Madrasah, Finalisasi Laporan SQA, Penyusunan Panduan & Demo
```

---

## 11. Kriteria Keberhasilan & Penerimaan (Acceptance Criteria)

Sistem dinyatakan memenuhi kualifikasi rilis (Definition of Done) jika:
1. **Fungsionalitas CMS:** Seluruh konten halaman publik terbukti bersumber dari basis data MySQL dan dapat disunting melalui panel admin.
2. **PPDB Tanpa Form:** Halaman PPDB berjalan tanpa form registrasi, seluruh berkas unduhan dapat diunduh tanpa error 404/500, dan berkas nonaktif terproteksi.
3. **Akun Terpusat:** Tidak ada antarmuka registrasi mandiri; akun guru dan siswa berhasil login di mobile Flutter via API Token milik backend, token kedaluwarsa/tercabut ditolak, dan satu akun berlaku untuk web dan mobile.
4. **Keandalan Presensi Guru:** Tingkat keberhasilan check-in guru mencapai minimal 98%; penolakan check-in ganda, check-in di luar jadwal, dan akses role siswa berjalan 100% akurat.
5. **Kesiapan Akreditasi IA2024:** Modul akreditasi berhasil memetakan bukti ke 14 butir, mencegah teks DKA melebihi 1.000 karakter, menampilkan grafik gap butir kosong, dan mengekspor berkas ringkasan SISPENA secara tepat.
6. **Notifikasi Tanpa Push Eksternal:** Pengumuman tersimpan di tabel `notifications`, tampil di aplikasi dengan badge belum dibaca, dapat ditandai terbaca, dan reset password berjalan melalui tautan email SMTP yang hanya berlaku sekali. Tidak ada ketergantungan pada FCM/OneSignal atau layanan push pihak ketiga.
7. **Kualitas Pengujian (SQA):**
   - Tingkat kelulusan test case (*Pass Rate*) ≥ 95% dari 41 skenario uji.
   - Nol (*zero*) defect berstatus `Critical` dan `High` pada saat evaluasi akhir.
   - Tidak ada temuan kebocoran data antar-peran (*Zero Cross-Role Access / IDOR*).

---

## 12. Manajemen Risiko & Mitigasi

| Risiko | Dampak | Probabilitas | Rencana Mitigasi |
|---|---|---|---|
| **Implementasi token API dan hashing sendiri** | Kesalahan penulisan token atau hashing membuka celah keamanan | Sedang | Gunakan pustaka standar PHP (`random_bytes`, `hash('sha256', ...)`, `password_hash`); test case wajib mencakup token kedaluwarsa, token dicabut, dan rotasi refresh token. |
| **SMTP belum siap / email masuk spam** | Reset password via email gagal | Sedang | Sediakan reset password instan oleh Admin sebagai jalur cadangan, dan catat kegagalan pengiriman di log. |
| **Keterlambatan Pengerjaan Tim Mobile Flutter** | Fitur presensi guru dan akses siswa tertunda | Sedang | Kontrak API (`/api/v1`) dikunci sejak Hari ke-2; backend menyediakan simulator/mock Postman collection lengkap. |
| **Tidak ada push notification real-time** | Guru/siswa harus membuka aplikasi untuk melihat notifikasi | Sedang | Notifikasi in-app dengan polling ringan saat aplikasi aktif; push eksternal (FCM/OneSignal) dapat ditambahkan di fase berikutnya tanpa mengubah skema data. |
| **Data Bukti Fisik Madrasah Belum Lengkap** | Dashboard akreditasi terlihat kosong saat demo | Tinggi | Siapkan data template (*dummy sample*) bukti standar (contoh KSP, SK, RPP) pada database seeder demo. |
| **Waktu Implementasi < 1 Bulan Terbatas** | Fitur melebar dan target tidak tercapai | Tinggi | Jalankan prinsip *Scope Guard* yang ketat: tolak penambahan fitur di luar daftar In-Scope (seperti e-rapor dan form PPDB online) hingga versi 1.0 stabil. |
