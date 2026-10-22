# Dokumen Permintaan Persetujuan Rancangan
## Website MTs Al-Huda Gondang + Aplikasi MADRASAHKU (Flutter)

| Item | Keterangan |
|---|---|
| Nama dokumen | Permintaan Persetujuan Rancangan Teknis & Sistem Desain (D-00) |
| Versi | v1.0 |
| Tanggal | 22 Oktober 2026 |
| Status | **Menunggu persetujuan mitra** |
| Penyusun | Tim Pengembang (Web PHP + Mobile Flutter) |
| Penerima | Mitra-Proyek, Kepala Madrasah, Operator Madrasah, Tim Akreditasi |
| Rujukan | `PRD.md` v1.2, blueprint `00`–`10`, `DIAGRAM.md` (12 diagram), paket SQA (41 test case) |
| Sifat | Dokumen acuan (baseline) — perubahan setelah disetujui harus dicatat di §17 |

---

## 1. Tujuan Dokumen

Dokumen ini dikirim sebagai **satu halaman pengantar dan alat persetujuan**. Tujuannya:

1. Menjelaskan ringkas apa yang akan dibangun, tanpa Mitra harus membaca seluruh dokumen lampiran.
2. Menunjukkan **keputusan rancangan** yang perlu disetujui (arsitektur, model data, sistem desain, alur kunci, kriteria penerimaan).
3. Meminta keputusan atas hal-hal yang masih terbuka agar implementasi dapat berjalan tanpa revisi besar.
4. Menjadi **baseline** versi 1.0 yang dipakai sebagai acuan apabila ada perubahan scope di tengah jalan.

> **Cara memberi persetujuan:** bacalah §4–§11 (ringkasan rancangan) dan §15 (checklist persetujuan). Isi kolom *Catatan Mitra* pada §15, lalu tanda tangani §17. Dokumen lengkap tersedia sebagai lampiran (§18) bila perlu membaca detail.

---

## 2. Ringkasan Eksekutif

- **Produk:** website publik madrasah yang saat ini masih statis, dilengkapi panel admin, modul akademik, presensi kehadiran guru, pusat akreditasi IA2024, informasi PPDB, dan aplikasi mobile Flutter untuk guru/siswa.
- **Stack:** custom PHP MVC + MySQL/PDO (bukan framework besar) untuk web, Flutter untuk mobile, satu sumber data di MySQL.
- **Identitas:** satu tabel `users` di MySQL. Web memakai session PHP, mobile memakai API token milik backend sendiri. **Tanpa Firebase/penyedia autentikasi eksternal** — tidak ada kredensial/service account pihak ketiga yang harus dikelola.
- **Notifikasi:** in-app center (tabel `notifications`) + email SMTP untuk reset password/pengumuman. Push real-time ditunda (bisa ditambahkan tanpa mengubah skema).
- **Presensi:** **hanya guru** (check-in harian + jam mengajar, jam server). Tidak ada presensi siswa maupun kartu QR siswa, sesuai kebijakan madrasah.
- **PPDB:** tanpa form online; hanya halaman informasi + katalog dokumen unduhan. Pendaftaran tetap manual/offline.
- **Akreditasi:** 4 komponen, 14 butir, 59 indikator (komponen 1–3) sebagai acuan; bank bukti digital, editor DKA (maks. 1.000 karakter), dashboard gap analysis, ekspor paket SISPENA.
- **Kualitas:** 41 test case SQA baru, target pass ≥ 95%, nol defect Critical/High, API p95 ≤ 1 detik, Lighthouse performance ≥ 80 & accessibility ≥ 90.
- **Waktu:** 4 minggu (28 hari kerja) sampai versi dapat didemokan, dengan UAT di minggu ke-4.

---

## 3. Ruang Lingkup dan Hasil yang Diharapkan

| # | Modul | Keluaran utama | Sasaran |
|---|---|---|---|
| 1 | Website Publik & CMS | Profil, berita, galeri, fasilitas, agenda, form kontak, SEO dasar | Konten dinamis, dikelola admin |
| 2 | PPDB (tanpa form) | Halaman `/ppdb` + katalog dokumen unduhan terlindungi | Brosur & formulir offline siap unduh |
| 3 | Pengguna & Keamanan | Manajemen akun/role, ganti & reset password, penonaktifan akun instan | Satu akun berlaku web + mobile |
| 4 | Akademik | Tahun ajaran, kelas, mapel, penugasan guru, jadwal anti-bentrok, materi, tugas, nilai | Guru & siswa memakai data sama |
| 5 | Presensi Guru | Check-in harian & jam mengajar, rekap, koreksi oleh operator, ekspor | Disiplin kerja & bukti IA2024 |
| 6 | Akreditasi IA2024 | Periode, bank bukti, pemetaan butir, editor DKA, dashboard gap, ekspor SISPENA | Kesiapan visitasi terpantau |
| 7 | REST API | 16 endpoint untuk aplikasi mobile | Kontrak dikunci di H-2 |

**Di luar lingkup v1 (ditunda):** presensi siswa, rapor lengkap, pembayaran SPP, e-learning penuh, chat, integrasi Dapodik/EMIS, push notification real-time, pentest pihak ketiga.

---

## 4. Keputusan Arsitektur Kunci (Perlu Disetujui)

| # | Area | Keputusan | Alasan | Konsekuensi |
|---|---|---|---|---|
| A1 | Platform web | Custom PHP MVC + MySQL/PDO, revamp bertahap | Memakai kode dan hosting yang ada; tidak ada rewrite | Gaya kode yang ada harus dijaga; tanpa scaffolding otomatis |
| A2 | Sumber data | MySQL sebagai satu-satunya sumber kebenaran | Konsistensi data web & mobile | API wajib memvalidasi ke MySQL, bukan cache lokal |
| A3 | Identitas | Satu tabel `users`; web session PHP, mobile API token | Tidak ada layanan identitas eksternal, tidak ada lock-in vendor | Backend harus menerbitkan token sendiri |
| A4 | Token API | Access 30 menit, refresh 30 hari, hash SHA-256 di tabel `api_tokens`, refresh selalu merotasi | Pencabutan token instan & jejak audit | Tim mobile wajib mengimplementasikan alur refresh + logout |
| A5 | Notifikasi | In-app center + email SMTP; tanpa push real-time | Menghindari ketergantungan layanan push pihak ketiga | Guru/siswa membuka aplikasi untuk melihat pengumuman terbaru |
| A6 | Presensi | Hanya guru; check-in harian & jam mengajar | Fokus disiplin kerja + bukti IA2024; siswa tidak boleh membawa HP | Modul presensi siswa & kartu QR dihapus dari scope |
| A7 | PPDB | Tanpa form; informasi + dokumen unduhan | Pendaftaran tetap manual, tidak membebani panitia | Tidak ada tabel pendaftar/verifikasi/status |
| A8 | Nilai | Hanya nilai & feedback tugas | Batasi kompleksitas & waktu | Tidak ada rapor lengkap di v1 |
| A9 | Akreditasi | Instrumen IA2024 (4 komponen / 14 butir / 59 indikator) | Acuan resmi BAN-PDM | Butir wajib diverifikasi ulang terhadap instrumen resmi |
| A10 | Penyimpanan berkas | Di luar web root (`storage/`), diakses via controller terotorisasi | Mencegah akses publik ilegal | Butuh konfigurasi `.htaccess`/server |
| A11 | SQA | 41 test case baru, reset hasil lama, 3 dokumen SQA direvisi | Hasil uji lama tidak valid | Butuh akses staging dan bukti uji |
| A12 | Jadwal | 4 minggu, 28 hari kerja, scope guard ketat | Target demo & UAT | Penambahan fitur baru harus disetujui terpisah |

> Catatan: keputusan di atas tercatat permanen di `09-keputusan.md` (K-01 s.d. K-18) dan di `PRD.md` §5.1.

---

## 5. Sistem Desain Antarmuka (Design System) — Perlu Disetujui

Identitas visual hijau madrasah yang sudah dipakai dipertahankan, lalu distandarkan agar konsisten di halaman publik, panel admin, dan aplikasi mobile.

### 5.1 Design token

| Token | Nilai | Token | Nilai |
|---|---|---|---|
| Warna utama | `#15803D` | Teks | `#111827` |
| Hijau gelap | `#0B3D1E` | Abu | `#6B7280` |
| Hijau muda | `#4ADE80` | Border | `#E1ECE5` |
| Mint | `#A7F3D0` | Latar lembut | `#F9FAFB` |
| Pale green | `#BBF7D0` | Putih | `#FFFFFF` |

| Token lain | Spesifikasi |
|---|---|
| Tipografi | Plus Jakarta Sans — bobot 400 / 600 / 700 / 800 |
| Radius kartu | 16px |
| Bayangan kartu | `0 12px 30px rgba(15, 61, 30, .10)` |
| Lebar kontainer maks. | 1100px |
| Breakpoint | 360px (mobile), 768px (tablet), 1280px+ (desktop) |

### 5.2 Komponen yang akan distandarkan

Tombol (primary/outline/danger/ghost) · Card · Badge status · Alert · Table · Form control · Modal · Tabs · Pagination · Breadcrumb · Empty state · Flash message sukses/gagal · Indikator pemuatan.

### 5.3 Struktur layout

- **Publik:** header + hero, blok informasi, grid berita/agenda, footer; satu kolom pada mobile.
- **Admin:** sidebar kiri tetap, topbar (profil & notifikasi), breadcrumb dinamis, canvas konten responsif.
- **Mobile (Flutter):** bottom navigation per role (Siswa / Guru), dengan menu yang menyesuaikan hak akses.

### 5.4 Aturan aksesibilitas

Kontras teks minimal 4.5:1 (WCAG 2.1 AA) · target sentuh minimal 44×44px di mobile · setiap status disajikan dengan teks, bukan hanya warna · kontras badge status pada latar terang dan gelap diperiksa.

**Yang perlu diputuskan mitra:** apakah palet dan komponen di atas sudah final, dan apakah ada warna atau identitas lain yang berbeda dari hijau yang selama ini dipakai.

---

## 6. Model Data Ringkas

Sistem memakai **39 tabel dalam 6 domain** (detail lengkap di `PRD.md` §8 dan `06-erd.md`).

| Domain | Tabel inti | Fungsi |
|---|---|---|
| Identitas & Keamanan | `users`, `students`, `teachers`, `api_tokens`, `password_resets`, `activity_logs` | Akun, role, token, reset, audit |
| Konten Web & CMS | `posts`, `pages`, `galleries`, `facilities`, `announcements`, `agendas`, `menus`, `settings`, `contact_messages`, `ppdb_documents`, `media` | Website publik & dokumen PPDB |
| Akademik & Pembelajaran | `academic_years`, `classes`, `subjects`, `class_subjects`, `schedules`, `materials`, `assignments`, `submissions` | Kelas, jadwal, materi, tugas, nilai |
| Presensi Guru | `teacher_attendances` | Check-in harian & jam mengajar |
| Notifikasi | `notifications` | Pusat notifikasi in-app |
| Akreditasi IA2024 | `accreditation_periods`, `accreditation_components`, `accreditation_butirs`, `accreditation_indicators`, `evidence`, `evidence_butir`, `dka_entries`, `indicator_checks`, `main_documents`, `tasks`, `accreditation_results`, `follow_ups` | Instrumen, bukti, DKA, gap, ekspor |

Aturan utama: InnoDB + foreign key `ON DELETE RESTRICT` · PDO prepared statement untuk seluruh query · berkas privat tidak disimpan di dalam `public/` · hash password `password_hash()` (bcrypt) dengan `password_needs_rehash()`.

---

## 7. Kontrak API (yang akan dipakai tim mobile)

| Method | Endpoint | Fungsi |
|---|---|---|
| POST | `/api/v1/auth/login` | Login → access token (30 mnt) + refresh token (30 hari) |
| POST | `/api/v1/auth/refresh` | Rotasi access + refresh token |
| POST | `/api/v1/auth/logout` | Cabut token perangkat |
| GET | `/api/v1/auth/me` | Profil & menu sesuai role |
| GET | `/api/v1/notifications` | Daftar notifikasi + badge belum dibaca |
| POST | `/api/v1/notifications/{id}/read` | Tandai terbaca |
| GET | `/api/v1/dashboard` | Ringkasan sesuai role |
| GET | `/api/v1/schedules` · `/materials` · `/assignments` · `/grades` · `/profile` | Data akademik pengguna |
| POST | `/api/v1/assignments/{id}/submissions` | Kirim tugas |
| GET | `/api/v1/attendance/today` · `/history` | Jadwal & riwayat presensi guru |
| POST | `/api/v1/attendance/check-in` | Check-in kehadiran guru |

- Header wajib: `Authorization: Bearer <access_token>`.
- Bentuk respons seragam: `{ success, message, data, errors }`.
- Endpoint daftar wajib berpaginasi (`page`, `limit`) dan mendukung filter.
- Presensi menolak role selain guru dengan HTTP 403; token invalid/kedaluwarsa/tercabut dengan HTTP 401.

> **Kontrak dikunci pada H-2.** Setelah disetujui, perubahan field wajib dikomunikasikan dan dicatat.

---

## 8. Peran dan Hak Akses (RBAC)

| Peran | Saluran | Hak akses utama |
|---|---|---|
| Publik / Calon Wali | Website | Baca profil, berita, galeri, agenda; unduh dokumen PPDB; kirim pesan kontak |
| Siswa | Mobile | Jadwal, materi, tugas, nilai miliknya. Tidak ada presensi |
| Guru | Mobile & Web | Check-in presensi, materi, tugas, nilai/feedback, unggah bukti akreditasi yang ditugaskan |
| Operator Madrasah | Admin Web | CMS, dokumen PPDB, akun & role, jadwal & kelas, koreksi presensi, input akreditasi |
| Kepala Madrasah | Admin Web | Dashboard agregat, review & persetujuan DKA/bukti, rekap presensi |
| Tim Akreditasi | Admin Web | Bank bukti, pemetaan butir/indikator, penyusunan DKA, gap analysis, ekspor |
| Super Admin | Admin Web | Konfigurasi sistem, audit log, backup basis data, manajemen role, reset kredensial |

**Butuh keputusan mitra:** apakah peran *Super Admin* tetap terpisah atau digabung ke peran *Operator* (kebijakan saat ini: peran `admin` pada tabel `users`, Kapitalisasi role akan dikunci saat migrasi).

---

## 9. Alur Kunci yang Perlu Disetujui

1. **Publik → PPDB:** pengunjung membuka `/ppdb`, membaca syarat & jadwal, mengunduh brosur/formulir lewat endpoint terlindungi. Tidak ada pendaftaran online.
2. **Admin membuat akun guru/siswa** → akun tersimpan di MySQL dengan `must_change_password = 1` → pengguna mengganti password saat login pertama.
3. **Guru login di aplikasi** → menerima access + refresh token (disimpan di penyimpanan aman perangkat) → dashboard menampilkan jadwal hari ini.
4. **Check-in guru** → backend memvalidasi role guru, jadwal milik guru tersebut, tanggal, rentang waktu, dan status akun → mencatat `hadir`/`terlambat` memakai jam server. Check-in ganda ditolak.
5. **Operator mengoreksi presensi** (izin/sakit/dinas luar/alpha) → tercatat di `activity_logs` dan tampak di rekap/export.
6. **Tim akreditasi** → unggah bukti → memetakan bukti ke satu atau lebih butir → susun DKA (maks. 1.000 karakter) → submit ke Kepala Madrasah → status `belum` → `draft` → `review` → `revisi` → `selesai`.
7. **Kepala Madrasah** → melihat dashboard gap butir kosong/belum direview/bukti kedaluwarsa → menyetujui butir → ekspor paket ZIP + rekap DKA + checklist visitasi untuk SISPENA.
8. **Reset password** → dua jalur: tautan email SMTP (berlaku 60 menit, sekali pakai) atau reset instan oleh admin. Menonaktifkan akun langsung mencabut seluruh sesi dan token.

Diagram lengkap: D-01 (arsitektur), D-02 (autentikasi), D-03 (presensi guru), D-04 (akreditasi), D-05 (PPDB), D-07 (use case) di `DIAGRAM.md`.

---

## 10. Kriteria Penerimaan (Acceptance Criteria)

| # | Kriteria |
|---|---|
| 1 | Website publik tampil dinamis dari database; seluruh dokumen PPDB terunduh tanpa error 404/500 |
| 2 | Tidak ada tombol registrasi mandiri; akun guru & siswa login di mobile via API token; token kedaluwarsa/tercabut ditolak; satu akun berlaku web & mobile |
| 3 | Check-in guru berhasil ≥ 98%; penolakan check-in ganda, check-in di luar jadwal, dan akses role siswa 100% akurat |
| 4 | Modul akreditasi memetakan bukti ke 14 butir, DKA dibatasi 1.000 karakter, dashboard gap tampil, ekspor berkas ringkasan SISPENA tepat |
| 5 | Notifikasi tampil di aplikasi dengan badge belum dibaca dan dapat ditandai terbaca; tautan reset password email hanya berlaku sekali |
| 6 | Pass rate SQA ≥ 95% dari 41 skenario uji; nol defect Critical/High; nol akses lintas peran (IDOR) |
| 7 | API p95 ≤ 1.000 ms; check-in ≤ 500 ms rata-rata; Lighthouse Performance ≥ 80, Accessibility ≥ 90; FCP ≤ 2,0 detik, LCP ≤ 2,5 detik |

---

## 11. Jadwal, Milestone, dan Definition of Done

| Minggu | Fokus | Deliverable |
|---|---|---|
| 1 | Fondasi | Skema 39 tabel, PDO, auth & role, layout admin, kontrak API, seed IA2024, CMS publik |
| 2 | Akademik | CRUD kelas/mapel/jadwal, materi, tugas, nilai, endpoint login/refresh, notifikasi in-app |
| 3 | Presensi guru + akreditasi | Check-in, rekap, bank bukti, pemetaan, DKA, dashboard gap, PPDB info + unduhan, ekspor |
| 4 | Stabilisasi | Regresi, audit keamanan & Lighthouse, UAT bersama tim madrasah, laporan SQA, demo |

Milestone kunci: H-2 kontrak API disetujui · H-5 schema + auth web/API berjalan · H-10 CRUD akademik inti selesai · H-15 presensi guru end-to-end · H-18 akreditasi inti + dashboard · H-21 PPDB + ekspor · H-25 regresi + UAT · H-28 laporan SQA + demo.

**Definition of Done per fitur:** berjalan di lokal · validasi server-side ada · role/akses dicek · pesan sukses/error jelas · ada bukti uji (screenshot/log) · test case terkait diperbarui · tidak ada data sensitif di repo.

---

## 12. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi | Kebutuhan mitra |
|---|---|---|---|
| Token API salah desain | Akses data tidak aman | `random_bytes(32)`, hash SHA-256, TTL pendek, rotasi refresh, uji token dicabut | Kontrak API disetujui tepat waktu |
| Tim mobile terlambat | UAT aplikasi tertunda | Kontrak API di H-2, mock server, contoh payload | Konfirmasi repo & jadwal tim mobile |
| Data bukti akreditasi belum lengkap | Dashboard terlihat kosong saat demo | Seeder data contoh (KSP, SK, RPP) | Pengumpulan dokumen fisik |
| SMTP belum siap | Reset password via email gagal | Jalur cadangan: reset instan oleh admin + pencatatan log failure | Penentuan server/akun SMTP |
| Akses staging belum ada | UAT tidak bisa dijalankan | Siapkan environment & kredensial di minggu 1 | Akses server staging |
| Regulasi presensi berubah | Modul presensi harus diubah | Presensi guru yang fleksibel, toleransi jam yang dapat dikonfigurasi | Konfirmasi aturan jam masuk & toleransi |
| Waktu < 1 bulan | Fitur belum tuntas | Scope guard ketat, prioritaskan alur kritis | Persetujuan cepat atas §15 |

---

## 13. Yang Dibutuhkan dari Mitra

| # | Kebutuhan | Batas | Penanggung jawab |
|---|---|---|---|
| M1 | Persetujuan §4–§12 (baseline v1.0) | 3 hari kerja | Mitra-Proyek |
| M2 | Penentuan akun/host SMTP untuk email | H-5 | Operator Madrasah |
| M3 | Akses server staging + domain uji | H-5 | Infra |
| M4 | Konfirmasi aturan jam masuk guru & toleransi keterlambatan | H-7 | Kepala Madrasah |
| M5 | Konfirmasi peran *Super Admin* terpisah atau digabung | H-3 | Mitra-Proyek |
| M6 | Daftar dokumen PPDB yang wajib tersedia + penanggung jawab pembaruan | H-10 | Panitia PPDB |
| M7 | Daftar dokumen bukti akreditasi contoh (KSP, SK, RPP, sertifikat) | H-14 | Tim Akreditasi |
| M8 | Jadwal UAT dan nama peserta UAT | H-18 | Kepala Madrasah |
| M9 | Konfirmasi repo tim mobile dan penanggung jawab teknis | H-2 | Mitra-Proyek |
| M10 | Konfirmasi sumber data awal (import Excel atau input manual) | H-7 | Operator Madrasah |

---

## 14. Pertanyaan Terbuka yang Belum Terjawab

1. Dokumen Word/Google Docs asli untuk 3 dokumen SQA yang perlu direvisi.
2. Server SMTP mana yang dipakai (SMTP hosting, Gmail Workspace, atau mail server sendiri) dan siapa pemilik akunnya.
3. Apakah server staging sudah tersedia, dan siapa yang mengelolanya.
4. Siapa pelaksana UAT dan kapan jadwalnya.
5. Apakah tim mobile memakai repo yang sama atau repo terpisah.
6. Apakah data guru/siswa diimpor dari Excel atau diinput manual.
7. Apakah check-in guru cukup lewat tombol aplikasi, atau perlu validasi lokasi/QR statis.
8. Berapa batas toleransi keterlambatan guru dan jam masuk resmi madrasah.
9. Dokumen PPDB apa saja yang wajib tersedia dan siapa penanggung jawab pembaruannya.
10. Apakah perlu statistik jumlah unduhan dokumen PPDB.

---

## 15. Checklist Persetujuan

Beri tanda **✔** pada kolom yang disetujui, isi *Catatan Mitra* bila ada perubahan.

| # | Item yang disetujui | ✔ | Catatan Mitra |
|---|---|---|---|
| 1 | Arsitektur: PHP MVC + MySQL, session web + API token, tanpa Firebase (§4, A1–A4) | ☐ | |
| 2 | Notifikasi in-app + email SMTP, push ditunda (§4, A5) | ☐ | |
| 3 | Presensi hanya guru, check-in harian & jam mengajar (§4, A6) | ☐ | |
| 4 | PPDB tanpa form, hanya informasi + unduhan (§4, A7) | ☐ | |
| 5 | Nilai hanya tugas/feedback, tanpa rapor lengkap (§4, A8) | ☐ | |
| 6 | Instrumen IA2024 (4 komponen / 14 butir / 59 indikator) sebagai acuan (§4, A9) | ☐ | |
| 7 | Palet warna, tipografi, dan komponen UI (§5) | ☐ | |
| 8 | Struktur layout publik, admin, dan mobile (§5.3) | ☐ | |
| 9 | Model data 39 tabel / 6 domain (§6) | ☐ | |
| 10 | Kontrak API 16 endpoint + aturan token (§7) | ☐ | |
| 11 | Daftar peran dan hak akses (§8) | ☐ | |
| 12 | Alur kunci yang disetujui (§9) | ☐ | |
| 13 | Kriteria penerimaan & target kualitas (§10) | ☐ | |
| 14 | Jadwal 4 minggu dan milestone (§11) | ☐ | |
| 15 | Batas lingkup v1 / yang ditunda (§3) | ☐ | |
| 16 | kebijakan peran Super Admin (§8) | ☐ | |
| 17 | Daftar kebutuhan dari mitra (§13) | ☐ | |

**Kesimpulan:**

- [ ] **DISETUJUI** — lanjut implementasi sesuai baseline v1.0.
- [ ] **DISETUJUI DENGAN CATATAN** — ada perubahan seperti pada kolom *Catatan Mitra*.
- [ ] **BELUM DISETUJUI** — perlu revisi dokumen dan pengajuan ulang.

---

## 16. Rekomendasi Tim Pengembang

Rekomendasi: **setujui baseline v1.0** dengan tiga catatan berikut sebelum tanda tangan:

1. Putuskan **peran Super Admin** (terpisah atau digabung ke Operator) — berdampak pada skema `users.role` dan middleware.
2. Pastikan **SMTP** tersedia paling lambat H-5; bila belum, jalur reset instan oleh admin tetap aktif sebagai cadangan.
3. **Verifikasi nomor butir instrumen IA2024** terhadap dokumen resmi BAN-PDM — nomor butir dipakai sebagai kunci pemetaan bukti.

---

## 17. Riwayat Versi & Tanda Tangan

| Versi | Tanggal | Perubahan | Penyusun |
|---|---|---|---|
| v1.0 | 22 Oktober 2026 | Dokumen awal permintaan persetujuan atas blueprint (PRD v1.2, 12 diagram, 41 test case) | Tim Pengembang |

| Peran | Nama | Tanda tangan | Tanggal |
|---|---|---|---|
| Penyusun — Tim Web |  |  |  |
| Penyusun — Tim Mobile |  |  |  |
| Kepala Madrasah |  |  |  |
| Operator Madrasah |  |  |  |
| Mitra-Proyek |  |  |  |

---

## 18. Lampiran — Daftar Dokumen yang Dikirim

### 18.1 Prioritas baca

| Urutan | Dokumen | Isi | Prioritas baca |
|---|---|---|---|
| 1 | `00-DOKUMEN-PERSETUJUAN-MITRA.md` | Dokumen ini (ringkasan + checklist) | Wajib dibaca |
| 2 | `PRD.md` | Spesifikasi produk lengkap: 7 modul, 39 FR, 14 NFR, 39 tabel, acceptance criteria | Wajib dibaca |
| 3 | `DIAGRAM.md` | 12 diagram Mermaid (D-01…D-12) | Sangat disarankan |
| 4 | `10-perombakan-web.md` | Rancangan detail web: sitemap, modul, keamanan, design system, migrasi | Sangat disarankan |
| 5 | `01-arsitektur.md` … `09-keputusan.md` | Arsitektur, autentikasi, presensi, akreditasi, PPDB, ERD, lingkungan/SQA, jadwal, decision log | Sesuai kebutuhan |
| 6 | `sqa/*` | Test plan, scenario/RTM, 41 test case, KPI, format laporan | Tim QA |

### 18.2 Ringkasan isi lampiran

| File | Isi utama |
|---|---|
| `01-arsitektur.md` | Arsitektur hybrid, komponen, alur data, batasan |
| `02-alur-autentikasi.md` | Login web/mobile, refresh & rotasi token, reset password, pencabutan, notifikasi, aturan keamanan |
| `03-alur-presensi-guru.md` | Check-in harian & jam mengajar, validasi server, rekap, koreksi |
| `04-alur-akreditasi.md` | Periode, bank bukti, pemetaan, DKA, workflow review, gap, ekspor |
| `05-alur-ppdb.md` | Alur informasi & unduhan dokumen, tanpa form |
| `06-erd.md` | ERD lengkap 39 tabel + index, constraint, migrasi |
| `07-lingkungan-dan-strategi-sqa.md` | Konfigurasi lokal/staging/produksi, SMTP, token, data uji, strategi uji |
| `08-rencana-implementasi.md` | Timeline 4 minggu, workstream, milestone, DoD, scope guard |
| `09-keputusan.md` | Decision log K-01…K-18 + pertanyaan terbuka |
| `10-perombakan-web.md` | Audit kondisi saat ini, sitemap, struktur folder, tabel CMS, UX admin, design system, keamanan, migrasi |
| `DIAGRAM.md` | Sumber tunggal 12 diagram Mermaid |
| `sqa/README.md` | Struktur paket SQA |
| `sqa/checklist-test-plan.md` | Checklist test plan |
| `sqa/checklist-test-scenario.md` | Checklist RTM |
| `sqa/checklist-laporan.md` | Checklist laporan |
| `sqa/test-case-baru.md` | 41 test case (auth, API, presensi, akreditasi, PPDB, CMS) |
| `sqa/kpi.md` | KPI dan ambang terima hasil uji |

### 18.3 Daftar diagram

| ID | Diagram | ID | Diagram |
|---|---|---|---|
| D-01 | Arsitektur hybrid | D-07 | Use case |
| D-02 | Alur autentikasi & token | D-08 | Topologi lingkungan uji |
| D-03 | Alur presensi guru | D-09 | Piramida pengujian |
| D-04 | Alur akreditasi | D-10 | Alur bug triage |
| D-05 | Alur info & dokumen PPDB | D-11 | Alur SDLC & QA |
| D-06 | ERD (39 tabel) | D-12 | Deployment / go-live |

---

*Dokumen ini dibuat sebagai bagian dari blueprint `blueprint-madrasahku/`. Perubahan baseline wajib diperbarui pada §17 dan `09-keputusan.md`.*
