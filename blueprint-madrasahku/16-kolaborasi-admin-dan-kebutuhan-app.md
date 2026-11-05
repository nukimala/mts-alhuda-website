# 16 — Kolaborasi Panel Admin & Kebutuhan Aplikasi Mobile MADRASAHKU

**Dokumen Acuan Integrasi & Panduan Kolaborasi Tim**  
**Proyek:** Sistem Informasi MTs Al-Huda Gondang & Aplikasi Mobile MADRASAHKU  
**Platform:** PHP MVC Backend, MariaDB Database, Flutter Mobile App (Android/iOS)

---

## 1. Konsep Utama: Single Source of Truth

Sistem Informasi MTs Al-Huda dirancang dengan arsitektur **satu sumber kebenaran (single source of truth)**. Panel Admin Web dan Aplikasi Mobile MADRASAHKU tidak berjalan terpisah, melainkan saling melengkapi dan terhubung langsung ke basis data MySQL/MariaDB yang sama melalui REST API v1:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        PANEL ADMIN WEB (OPERATOR)                      │
│   • Buat Akun Guru & Siswa               • Susun Jadwal Pelajaran       │
│   • Setel Geofence & Jam Masuk           • Pantau & Koreksi Presensi   │
│   • Evaluasi Mutu Akreditasi IA2024       • Publikasi Berita & Agenda   │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        DATABASE UTAMA (mts_alhuda)                     │
│                  42 Tabel Relasional (InnoDB + utf8mb4)                │
└──────────────────────────────────▲─────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                       REST API ENGINE (/api/v1/*)                      │
│                  JSON Output · Bearer Auth Token SHA-256               │
└──────────────────────────────────▲─────────────────────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                     APLIKASI MOBILE (MADRASAHKU)                       │
│  [Role Guru]                             [Role Siswa]                  │
│  • Check-in Datang & Jam Mengajar        • Lihat Jadwal Kelas Pribadi  │
│  • Lihat Riwayat Kehadiran Bulanan       • Unduh Materi Pelajaran      │
│  • Kelola Tugas & Beri Nilai             • Kumpulkan Tugas & Catatan   │
│  • Notifikasi Pengumuman & Jadwal        • Notifikasi Tugas Baru & Nilai│
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Matriks Kolaborasi Fitur Admin vs Kebutuhan Mobile App

| Kebutuhan Aplikasi Mobile | Disediakan / Dikelola di Panel Admin | Endpoint REST API Terkait | Alur Kerja Kolaborasi |
|---|---|---|---|
| **Login Guru & Siswa** (Tanpa Self-Registration) | Menu **Pengguna & Role** (`/admin/pengguna`) | `POST /api/v1/auth/login`<br>`GET /api/v1/me` | Operator membuat akun di Admin dan memilih Role (Guru/Siswa). Profil otomatis ditautkan ke tabel `guru` atau `siswa`. Pengguna login di HP menggunakan Email/Username dan wajib ganti kata sandi perdana. |
| **Presensi Mandiri Guru** (Datang & Jam Mengajar) | Menu **Presensi Guru** (`/admin/presensi`) | `GET /api/v1/attendance/today`<br>`POST /api/v1/attendance/check-in`<br>`POST /api/v1/attendance/check-out` | Guru menekan tombol Presensi di aplikasi mobile. Sistem server memvalidasi jam keterlambatan dan lokasi GPS. Data langsung muncul di tabel rekap Admin dengan label `📱 Mobile App`. |
| **Koreksi Presensi** (Izin, Sakit, Dinas Luar) | Menu **Presensi Guru** (`/admin/presensi/correct`) | `GET /api/v1/attendance/history` | Jika guru dinas luar atau terkendala HP, Operator mencatat koreksi di Admin (`🖥️ Operator Web`). Status langsung diperbarui di riwayat presensi aplikasi guru. |
| **Aturan Jam & Geofence GPS** | Menu **Pengaturan Situs** (`/admin/pengaturan?tab=aplikasi`) | `GET /api/v1/settings/public` | Admin mengatur Jam Masuk, Batas Toleransi Terlambat, Jam Pulang, Titik Koordinat Madrasah, dan Radius Toleransi Meter. Aplikasi Flutter membaca parameter ini untuk kontrol validasi presensi. |
| **Jadwal Pelajaran** (Guru & Siswa) | Menu **Jadwal Pelajaran** (`/admin/jadwal`) | `GET /api/v1/schedules` | Admin menyusun jadwal per kelas, hari, jam, mapel, dan guru. Di HP, guru otomatis hanya melihat jadwal mengajarnya, dan siswa otomatis hanya melihat jadwal kelasnya. |
| **Pengumpulan Tugas Siswa** | Menu **Akademik & Akreditasi** | `GET /api/v1/assignments`<br>`POST /api/v1/assignments/{id}/submit` | Siswa melihat tugas aktif sesuai kelasnya dan dapat mengunggah berkas/catatan tugas langsung dari ponsel pintar. |
| **Notifikasi In-App** | Sistem Notifikasi Terpusat | `GET /api/v1/notifications`<br>`POST /api/v1/notifications/{id}/read` | Polling in-app berkala oleh aplikasi Flutter untuk menampilkan badge lonceng pengumuman, tugas baru, dan info presensi. |
| **Data Dukung Akreditasi IA2024** | Menu **Pusat Akreditasi** (`/admin/akreditasi`) | Terhubung ke Tabel `presensi_guru` & `bukti` | Bukti presensi guru dan aktivitas pembelajaran yang terjadi di aplikasi mobile menjadi data otentik kesiapan visitasi akreditasi IA2024. |

---

## 3. Detail Integrasi Modul-Modul Kunci

### 3.1 Modul Pengguna: Penautan Profil Otomatis
- **Masalah sebelumnya:** Pembuatan akun hanya menyimpan data di tabel `pengguna`. Akun siswa tidak memiliki record di `siswa`, sehingga aplikasi mobile menolak akses dengan galat `PROFILE_NOT_LINKED`.
- **Solusi yang diimplementasikan:**
  1. Pada form modal Tambah Pengguna di `/admin/pengguna`, tersedia pilihan dinamis:
     - Jika role **Guru**: Admin menginput NIP, NUPTK, dan Spesialisasi Mapel &rarr; otomatis membuat entri di tabel `guru`.
     - Jika role **Siswa**: Admin menginput NIS, NISN, dan memilih Kelas dari tabel `kelas` &rarr; otomatis membuat entri di tabel `siswa` dengan UUID `public_id`.
  2. Tabel Admin Pengguna kini menampilkan status `✓ Guru Terhubung` atau `✓ Siswa (Kelas X)` untuk memastikan akun siap dipakai di mobile app.

### 3.2 Modul Presensi: Validasi Waktu Server & Geofence
- Waktu presensi **selalu merujuk ke jam server PHP/MySQL (WIB)**, bukan jam lokal perangkat smartphone guru.
- Validasi status kehadiran:
  - Jam presensi &le; `attendance_check_in_time` + `attendance_late_tolerance`: Berstatus **`hadir`** (Tepat Waktu).
  - Jam presensi > `attendance_check_in_time` + `attendance_late_tolerance`: Berstatus **`terlambat`**.
- Pembedaan metode:
  - Presensi dari HP tercatat dengan `method = 'self'`.
  - Presensi koreksi admin tercatat dengan `method = 'operator'` dan menyimpan ID operator pemeriksa (`verified_by`).

### 3.3 Modul Pengaturan: Konfigurasi Parameter Mobile
Tab baru **Presensi & Mobile App** di `/admin/pengaturan?tab=aplikasi` mengontrol:
1. `attendance_check_in_time`: Jam Mulai Masuk Presensi (default `06:30`).
2. `attendance_late_tolerance`: Batas Toleransi Terlambat dalam menit (default `15`).
3. `attendance_check_out_time`: Jam Mulai Presensi Pulang (default `14:00`).
4. `attendance_lat` & `attendance_lng`: Koordinat Latitude & Longitude madrasah.
5. `attendance_radius_meters`: Radius toleransi geofence presensi (default `100` meter).
6. `attendance_mobile_enabled`: Saklar izin presensi mobile (Aktif/Nonaktif).

Semua parameter ini bertanda `is_public = 1` sehingga dapat diakses oleh aplikasi Flutter tanpa autentikasi melalui `GET /api/v1/settings/public`.

---

## 4. Panduan Eksekusi bagi Tim Flutter

1. **Base URL Pengembangan:**
   - Emulator Android: `http://10.0.2.2/mts-alhuda-website/api/v1`
   - Perangkat Fisik (HP di Wi-Fi yang sama): `http://<IP_KOMPUTER_SERVER>/mts-alhuda-website/api/v1`
2. **Alur Login:**
   - Panggil `POST /auth/login` dengan `identifier` (email atau username) dan `password`.
   - Simpan `access_token` di secure storage. Sertakan di setiap request:
     `Authorization: Bearer <access_token>`
3. **Alur Presensi Guru (Screen C-2 & C-3):**
   - Saat membuka layar presensi, panggil `GET /attendance/today` untuk mendapatkan status presensi hari ini dan jadwal mengajar hari ini.
   - Ambil koordinat GPS perangkat.
   - Tekan tombol check-in &rarr; kirim `POST /attendance/check-in` dengan payload `{ "type": "daily", "latitude": lat, "longitude": lng }`.
   - Tampilkan konfirmasi status (`hadir` / `terlambat`) berdasarkan respons API.
4. **Alur Tugas Siswa (Screen C-7):**
   - Ambil daftar tugas via `GET /assignments`.
   - Kumpulkan tugas via `POST /assignments/{publicId}/submit` (mendukung `multipart/form-data` berkas file PDF/DOC/JPG dan catatan teks).
