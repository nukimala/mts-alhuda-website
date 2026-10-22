# Panduan Google Stitch — UI MTs Al-Huda & MADRASAHKU

Dokumen ini berisi **prompt siap tempel** untuk Google Stitch (stitch.withgoogle.com),
disusun agar hasil UI Stitch konsisten dengan `PRD.md` §9 (Design System) dan
`10-perombakan-web.md` (sitemap & modul).

## Cara pakai

1. **Stitch menghasilkan 1 layar per prompt.** Jangan menempelkan seluruh dokumen;
   tempelkan hanya bagian "Gaya Global" + 1 prompt layar yang mau dibuat.
2. Prompt ditulis dalam **bahasa Inggris** — Stitch paling akurat dengan Inggris.
3. Urutan yang disarankan: buat 1 layar kunci dulu (mis. Admin Login), lalu klik
   **Settings → Theme** dan pastikan warna/font sama sebelum membuat layar berikutnya,
   atau tempelkan ulang blok "Gaya Global" di setiap prompt.
4. Setelah cocok, **Export to Figma** untuk finalisasi, atau salin ke developer Flutter/PHP.
5. Jangan minta Stitch membuat logika/fitur — hanya **tampilan**. Perilaku halaman tetap
   mengikuti `PRD.md` dan `10-perombakan-web.md`.

---

## 0. Gaya Global (tempel di awal SETIAP prompt layar)

```text
Design system:
- Primary green #15803D, dark green #0B3D1E, accents #4ADE80, mint #A7F3D0, pale green #BBF7D0.
- Neutrals: white #FFFFFF background, soft gray #F9FAFB surfaces, text #111827, borders #E1ECE5.
- Font: Plus Jakarta Sans (400 regular, 600 semibold, 700 bold, 800 extrabold).
- Cards: border-radius 16px, soft shadow 0 12px 30px rgba(15,61,30,0.10).
- Overall feel: clean, modern, friendly Islamic school (madrasah) identity, generous whitespace,
  mobile-first, accessible contrast.
```

---

## A. WEB — Panel Admin (desktop layout: sidebar kiri + topbar)

Struktur admin yang dipakai Stitch: **sidebar navigasi tetap di kiri, topbar profil &
notifikasi di atas, breadcrumb, canvas konten responsif.**

### A-1. Admin Login
```text
[Gaya Global] + 
Web login page for a madrasah school admin panel, desktop-first.
Centered card (radius 16px) on soft gray #F9FAFB background with the school logo at top,
fields: Username/Email, Password (with show/hide toggle), a "Ingat saya" checkbox,
primary green #15803D full-width "Masuk" button.
Below the form: a muted help line "Hubungi operator untuk reset password".
Show an inline error banner style example (red) above the fields.
Clean, no images on background, subtle dark-green #0B3D1E footer strip.
```

### A-2. Admin Dashboard
```text
[Gaya Global] +
Admin dashboard for a madrasah website, desktop web layout: fixed left sidebar navigation
(Beranda, Berita, Galeri, Fasilitas, Halaman, Menu, Pengguna, PPDB, Akademik, Presensi,
Akreditasi, Pengaturan) with dark-green #0B3D1E sidebar and white active item, topbar with
search, notification bell with badge, and admin avatar.
Content: welcome header "Selamat datang, Operator", then a row of 4 stat cards
(Berita Terpublikasi, Pesan Kontak Baru, Presensi Guru Hari Ini, Progres Akreditasi %)
each with an icon and small trend text, then a 2-column area: left "Konten Terbaru" list,
right "Perlu Perhatian" list (tugas akreditasi belum selesai, pesan belum dibaca).
```

### A-3. Daftar Berita (pola tabel list — bisa dipakai ulang untuk Galeri/Fasilitas/Halaman/Menu)
```text
[Gaya Global] +
Admin news management page (desktop, sidebar + topbar layout).
Header: page title "Kelola Berita", breadcrumb, and a green primary "Tambah Berita" button.
Toolbar row: search input, status filter dropdown (Semua/Draft/Terbit), sort dropdown.
Content: a clean data table with columns Judul (with small cover thumbnail), Kategori,
Penulis, Status (green pill "Terbit", gray pill "Draft"), Tanggal Terbit, Aksi (edit and
delete icon buttons). Show 6 example rows with realistic Indonesian school news titles.
Include an empty-state variant note. Pagination at bottom.
```

### A-4. Form Berita (pola form create/edit — dipakai ulang untuk Galeri/Fasilitas/Halaman/PPDB)
```text
[Gaya Global] +
Admin create/edit news form page (desktop, sidebar + topbar layout).
Two-column layout: left main column with fields Judul, Ringkasan (textarea with character
counter), Isi Berita (rich text editor toolbar mock), right column card with: Status select
(Draft/Terbit), Tanggal Terbit (date picker), Kategori select, Cover image upload dropzone
showing a preview image, and a sticky bottom bar with "Simpan" (primary green) and
"Batal" (outline) buttons. Include validation error example under Judul field.
```

### A-5. Manajemen Pengguna
```text
[Gaya Global] +
Admin user management page (desktop, sidebar + topbar layout).
Title "Kelola Pengguna", green "Tambah Pengguna" button.
Toolbar: search by name/email, role filter chips (Semua, Admin, Operator, Kepala, Guru,
Tim Akreditasi), status filter (Aktif/Nonaktif).
Table columns: Nama (with avatar initials), Email, Role (colored pill), Status
(green dot "Aktif" / gray dot "Nonaktif"), Terakhir Login, Aksi (edit, reset password,
activate/deactivate icons).
Also show a small modal design: "Buat Pengguna Baru" with fields Nama, Email, Role,
generate temp password checkbox, and a note "user wajib ganti password saat login pertama".
```

### A-6. Info & Dokumen PPDB (tanpa form pendaftaran)
```text
[Gaya Global] +
Admin PPDB management page (desktop, sidebar + topbar layout).
Top: an editable info card "Info PPDB" with title and rich text description
(Jadwal PPDB, Syarat Pendaftaran) and a Save button.
Below: "Dokumen Unduhan" section with a "Tambah Dokumen" button and a table:
Nama Dokumen (e.g. Brosur PPDB, Formulir Pendaftaran Manual, Tata Cara), Tipe file
icon (PDF), Ukuran, Urutan (drag handle), Status toggle Aktif, jumlah unduhan,
Aksi (edit, delete). Note banner: "Halaman publik hanya menyediakan informasi dan dokumen
unduhan — tidak ada formulir pendaftaran online."
```

### A-7. Akademik — Kelola Jadwal
```text
[Gaya Global] +
Admin school schedule management page (desktop, sidebar + topbar layout).
Title "Jadwal Pelajaran", filters: Tahun Ajaran select, Kelas select, day tabs
(Senin–Sabtu). Content: a clean timetable grid per class: columns = time slots,
rows = days, each cell is a small card showing subject name, teacher name, and room,
color-coded by subject using soft green/mint/pale palette. Include "Tambah Jadwal"
button and an edit-cell modal sketch (Kelas, Mata Pelajaran, Guru, Hari, Jam Mulai,
Jam Selesai, Ruang).
```

### A-8. Presensi Guru — Rekap & Koreksi (admin)
```text
[Gaya Global] +
Admin teacher attendance recap page (desktop, sidebar + topbar layout).
Title "Presensi Guru", filters: date picker (default today), teacher search, status filter.
Summary row: 3 stat cards (Hadir, Izin/ Sakit, Alpa) today.
Table columns: Nama Guru (avatar initials), Jadwal Mengajar (subject + class),
Check-in time, Check-out time, Status (green "Hadir" / yellow "Izin" / red "Alpa" pills),
Catatan, Aksi (koreksi icon). Include a "Koreksi Presensi" modal sketch with reason field
and confirmation. Note: attendance is teacher-only, no student attendance in this system.
```

### A-9. Akreditasi IA2024 — Dashboard Kesiapan
```text
[Gaya Global] +
Accreditation readiness dashboard (desktop, sidebar + topbar layout), for Indonesian
madrasah IA2024 instrument (4 components, 14 butir, 59 indicators).
Title "Kesiapan Akreditasi", period selector at top.
Content: 4 component cards showing circular progress percentage per component
(Komponen 1–4), below them a gap-analysis table: columns Butir, Indikator count,
Bukti mapped (count), DKA status (green "Selesai" / yellow "Revisi" / gray "Kosong"),
progress bar per row. Right side: a panel "Bukti Menjelang Kadaluarsa" list with
warning icon. Overall progress ring at the top right.
```

### A-10. Akreditasi IA2024 — Bank Bukti & Mapping
```text
[Gaya Global] +
Accreditation evidence bank page (desktop, sidebar + topbar layout).
Title "Bank Bukti Akreditasi", green "Unggah Bukti" button.
Toolbar: search, filter by component (Komponen 1–4 dropdown), filter by butir.
Table: Judul Bukti, Tipe File (PDF/image icon), Tanggal Unggah, Masa Berlaku (valid_until,
warning color if soon), Status (green "Valid" / yellow "Menjelang Kadaluarsa" / red
"Kadaluarsa"), Mapping count ("3 butir"), Aksi.
Include a mapping modal sketch: an evidence on the left, checklist of butirs
(Komponen > Butir > Indikator tree with checkboxes) on the right, and Save button.
```

### A-11. Akreditasi IA2024 — Editor DKA
```text
[Gaya Global] +
DKA (Deskripsi Kinerja Asesi) editor page (desktop, sidebar + topbar layout).
Header: butir selector (component dropdown + butir dropdown), indicator chips below it.
Main: large textarea card for DKA text with a 1000-character limit — show a character
counter "842 / 1000" near the limit, and a remaining-character progress bar turning
yellow near the max. Right panel: list of mapped evidence for this butir (small cards
with file icon, title, valid-until date, remove button) and an "Tambah Bukti" button.
Bottom bar: status select (Draft / Review / Disetujui Kepala), Save button,
and an approval card for Kepala Madrasah with Approve/Revisi buttons and comment field.
```

### A-12. Pengaturan Situs
```text
[Gaya Global] +
Site settings admin page (desktop, sidebar + topbar layout).
Title "Pengaturan Situs". Grouped sections as cards: Identitas Madrasah (nama, alamat,
telepon, email, logo upload), Media Sosial (repeatable link rows), SEO default
(meta title, description), Kontak & Peta (embed map placeholder), Pengumuman berjalan
ticker toggle. Each card has its own Save button. Clean settings list layout.
```

---

## B. WEB — Halaman Publik (opsional; tampilan lama sudah ada, pakai untuk menyegarkan)

### B-1. Beranda Publik
```text
[Gaya Global] +
Public homepage for MTs Al-Huda Gondang, an Indonesian Islamic junior high school
(madrasah tsanawiyah). Mobile-first, responsive to desktop.
Sections in order: sticky top navbar with logo + menu (Beranda, Profil, Akademik,
Fasilitas, Galeri, Berita, Kontak) + green "Info PPDB" button; hero with headline,
subtext, 2 CTAs, and a school photo; stats strip (siswa, guru, alumni); welcome section;
program akademik cards; fasilitas cards with photos; agenda list; latest news 3-card
grid; PPDB callout banner (green gradient, "Unduh Dokumen PPDB" button — no online form);
footer with contact, social links, and dark-green background.
```

### B-2. Halaman PPDB Publik
```text
[Gaya Global] +
Public PPDB information page for a madrasah (mobile-first responsive).
Hero strip with title "PPDB MTs Al-Huda" and short subtitle.
Sections: Info & Jadwal PPDB (timeline cards of dates), Syarat Pendaftaran (checklist),
Alur Pendaftaran (3-step horizontal stepper: Unduh Dokumen → Isi Manual → Datang ke
Sekolah), and a "Dokumen Unduhan" card list: each row has a PDF icon, document name,
file size, and a green download button. Prominent note banner: "Pendaftaran dilakukan
secara manual di sekolah — tidak ada formulir online." Footer contact card.
```

---

## C. MOBILE — Aplikasi MADRASAHKU (Flutter, Material 3)

Konteks: guru adalah pengguna utama (presensi, materi, tugas), siswa melihat jadwal/
materi/tugas/nilai. Login memakai satu akun yang sama dengan web.

### C-1. Login Mobile
```text
[Gaya Global] +
Mobile app login screen (Material 3, Flutter), Android phone frame, for MADRASAHKU —
a madrasah school app. Logo of the madrasah centered at top, app name MADRASAHKU,
fields Username/Email and Password with show/hide, primary green "Masuk" button,
muted helper text "Gunakan akun yang sama dengan website madrasah".
Include error state banner example. Clean minimal, soft background #F9FAFB.
```

### C-2. Dashboard Guru
```text
[Gaya Global] +
Mobile home dashboard for a teacher (guru) in MADRASAHKU app, Material 3, Android phone.
Top greeting "Assalamu'alaikum, Ustadz Ahmad" with avatar and notification bell with badge.
Prominent card: "Presensi Hari Ini" — shows today's teaching schedule (first subject,
class, time) and a large green button "Check-in" (or state "Sudah check-in 07:12" with
green check icon). Below: quick-action grid 2x2 (Jadwal, Materi, Tugas, Notifikasi),
then "Tugas Menunggu Dinilai" horizontal card list (subject, class, count), and today's
schedule timeline list with check-in status chips.
```

### C-3. Presensi Guru — Check-in Jam Mengajar (LAYAR KUNCI)
```text
[Gaya Global] +
Mobile teacher attendance check-in screen, Material 3, Android phone frame.
This is the core screen: teacher checks in daily and before each teaching session.
Top: date header "Kamis, 22 Oktober 2026" with server time note "Waktu mengikuti server".
Card: "Kehadiran Hari Ini" — big status chip (green "Sudah Check-in 07:12").
Below: list of today's teaching sessions as cards: subject, class, time range, room;
each card shows session status: green "Sudah Check-in", or a green button
"Check-in Jam Mengajar" (disabled/gray if outside the time window, with helper text
"Belum masuk jam mengajar"). Include a success state example with a check animation
placeholder and "Check-out" button for the last session. No QR codes, no student
attendance anywhere.
```

### C-4. Riwayat Presensi Guru
```text
[Gaya Global] +
Mobile teacher attendance history screen, Material 3, Android phone.
Month summary at top: 3 stat chips (Hadir, Izin, Alpa) with counts.
A simple bar/calendar strip for the current month with green/yellow/red day dots.
Below: grouped list by date; each entry row: date, sessions taught (e.g. "3 sesi"),
status pill, small chevron to expand detail (per-session times). Filter chips on top
(Bulan, Semua, Hadir, Izin).
```

### C-5. Jadwal Pelajaran (guru & siswa)
```text
[Gaya Global] +
Mobile weekly schedule screen, Material 3, Android phone.
Day tabs at top (Senin–Sabtu, today highlighted green). List of class session cards:
time range, subject name bold, class name, room, teacher (if student view) or classes
(if teacher view). Empty state for a day with no schedule: friendly illustration
placeholder and text "Tidak ada jadwal". Clean card list, mint accents.
```

### C-6. Materi (daftar & detail)
```text
[Gaya Global] +
Mobile learning materials screen, Material 3, Android phone.
Top: subject filter chips. List of material cards: title, description preview, class,
publish date, PDF file icon with file size, green "Unduh" button. Detail variant:
full card with title, description, attached file row with download icon, and
"published by" footer. Offline indicator chip example ("Tersedia offline") after download.
```

### C-7. Tugas — Siswa Mengumpulkan
```text
[Gaya Global] +
Mobile assignments screen for a student, Material 3, Android phone.
Two filter tabs: "Menunggu" (badge count) and "Selesai".
Waiting list: assignment cards with subject, title, deadline countdown chip
(red if < 24h "Tenggat besok", green "Tersisa 3 hari"), max score, and a green
"Kumpulkan" button. Submission flow variant: upload sheet — file picker card,
chosen file row with remove icon, note field, submit button, and a success screen
with green check and "Terkumpul 12:30, menunggu dinilai".
```

### C-8. Penilaian Tugas — Guru (mengisi nilai & feedback)
```text
[Gaya Global] +
Mobile grading screen for a teacher, Material 3, Android phone.
Header: assignment title, class, deadline, submitted count "Terumpul 18/24".
List of student submissions: student name, submitted time, on-time/late chip,
file attachment row. Grading variant: student submission view with file preview row,
a score input field (numeric, max score hint), a multi-line feedback textarea, and
green "Simpan Nilai" button. Already-graded state: score shown in a green pill with
feedback text below, and an "Ubah Nilai" outline button.
```

### C-9. Nilai & Feedback — Siswa
```text
[Gaya Global] +
Mobile grades screen for a student, Material 3, Android phone.
List of graded assignments grouped by subject: subject header, assignment title,
score shown as a green pill "85 / 100" (yellow pill if below 75), teacher feedback
in a soft mint card with a quote icon, graded date. Empty state for subjects with
no graded work yet.
```

### C-10. Notifikasi In-App
```text
[Gaya Global] +
Mobile notifications center screen, Material 3, Android phone.
Top: title "Notifikasi" with "Tandai semua dibaca" text button.
List of notification rows: leading icon by type (assignment = book icon,
announcement = megaphone, attendance = check icon, system = info icon), bold title,
body preview, relative time ("2 menit lalu"), unread rows with a green dot and
soft mint background. Tapping opens detail; include one expanded example with a
"Kunjungi halaman" link chip. Empty state: bell illustration and "Belum ada notifikasi".
Note: notifications are in-app polling only — no push notification UI.
```

### C-11. Profil & Menu
```text
[Gaya Global] +
Mobile profile screen, Material 3, Android phone.
Top card: avatar, name, role chip (Guru/Siswa), NIP/NIS, email.
Menu list rows with icons: Edit Profil, Ganti Password, Notifikasi (with badge count),
Jadwal, Presensi (guru only), Riwayat Presensi (guru only), Materi, Tugas, Bantuan,
Keluar (red text with logout icon). Version footer "MADRASAHKU v1.0".
```

---

## Tips agar hasil Stitch sesuai harapan

| Masalah umum | Solusi |
|---|---|
| Warna/font berubah antar layar | Tempel blok **Gaya Global** di setiap prompt; atau tetapkan Theme di Settings Stitch sebelum generate |
| Stitch menambah fitur tidak ada (QR, form PPDB online, push notif) | Tambahkan kalimat larangan eksplisit, mis. "No QR codes", "no online registration form", "in-app notifications only, no push" |
| Layar terlalu ramai | Tambahkan "generous whitespace, minimal decoration, max 6 primary sections" |
| Bahasa hasil campur | Tambahkan "All UI text in Bahasa Indonesia" ke prompt jika teks harus berbahasa Indonesia |
| Butuh varian state (empty/error/loading) | Minta satu varian per prompt, mis. "...show the empty state variant" — jangan gabung semua state di satu layar |
| Hasil belum cocok | Gunakan tombol revisi Stitch dengan instruksi pendek spesifik ("make the check-in button larger, move stat chips below the date header") |

## Prioritas pembuatan (disarankan)

1. **C-3 Presensi Guru Check-in** — layar inti aplikasi mobile.
2. **A-2 Admin Dashboard** + **A-1 Admin Login** — fondasi panel web.
3. **A-3/A-4** — pola tabel+form yang dipakai ulang di banyak modul.
4. **A-9/A-10/A-11** — modul akreditasi IA2024 (pembeda utama sistem).
5. **C-2/C-10/C-11** — pelengkap mobile.
6. B-1/B-2 — hanya jika ingin menyegarkan halaman publik (tampilan lama sudah ada).
