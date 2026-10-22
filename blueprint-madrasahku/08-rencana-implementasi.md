# Rencana Implementasi

Target: fungsional dan dapat didemokan dalam kurang dari 1 bulan. Bukan hardening produksi penuh.

## Prinsip

- Kerjakan alur kritis lebih dulu.
- Kunci kontrak API pada hari ke-2.
- Web dan mobile memakai data yang sama.
- SQA berjalan paralel, bukan di akhir.
- Tidak menambah fitur di luar scope sebelum UAT.

## Timeline 4 minggu

| Minggu | Fokus | Deliverable |
|---|---|---|
| 1 | Fondasi | Schema, PDO, auth/role, layout admin, kontrak API, seed IA2024 |
| 2 | Akademik | CRUD kelas/mapel/jadwal, materi, tugas, nilai, pengumuman, API |
| 3 | Presensi guru + akreditasi | Check-in guru, rekap, bank bukti, mapping, DKA, dashboard, PPDB info + dokumen |
| 4 | Stabilisasi | Regression, UAT, perbaikan, ekspor, laporan SQA, demo |

## Workstream

### Web

- Router admin dan API.
- Auth, session, CSRF, role middleware.
- CRUD akademik.
- Modul akreditasi.
- PPDB info + dokumen unduhan (tanpa form).
- Upload privat.

### Mobile

- Login ke API dengan token milik backend.
- Profil dan menu per role.
- Jadwal, materi, tugas, nilai.
- Presensi kehadiran guru (check-in harian dan jam mengajar).
- Notifikasi in-app (polling ringan).

### SQA

- Revisi Test Plan, Test Scenario/RTM, Laporan.
- Reset status hasil lama.
- Siapkan data uji dan lingkungan staging.
- Eksekusi test case baru.
- Defect log dan bukti.

## Milestone

| Hari | Milestone |
|---|---|
| H-2 | Kontrak API disetujui |
| H-5 | Schema + auth web/API berjalan |
| H-10 | CRUD akademik inti selesai |
| H-15 | Presensi guru berjalan end-to-end |
| H-18 | Akreditasi inti + dashboard |
| H-21 | PPDB info/dokumen + ekspor |
| H-25 | Regression + UAT |
| H-28 | Laporan SQA + demo |

## Definition of Done per fitur

- Kode berjalan di lokal.
- Validasi server-side ada.
- Role/akses dicek.
- Error dan success message jelas.
- Ada bukti uji (screenshot/log).
- Test case terkait diperbarui.
- Tidak ada data sensitif di repo.

## Scope guard

Masuk v1:

- Publik, PPDB info + dokumen, akademik dasar, presensi guru, akreditasi, ekspor, notifikasi.

Ditunda:

- Presensi siswa, rapor lengkap, pembayaran, e-learning penuh, chat, integrasi Dapodik/EMIS, pentest pihak ketiga.

## Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Token API salah desain | Akses tidak aman | Pakai `random_bytes` + hash sha256, TTL pendek, rotasi refresh token, uji token dicabut |
| Tim mobile terlambat | UAT app tertunda | Kontrak API dipercepat, mock server |
| Data akreditasi tidak lengkap | Dashboard kosong | Seed + data contoh, fokus alur |
| Waktu < 1 bulan | Fitur tidak selesai | Scope guard, prioritaskan alur kritis |
| Kredensial staging | Gagal deploy | Siapkan env dan akses di minggu 1 |

## Demo akhir

1. Admin membuat akun guru/siswa.
2. Guru login app, melihat jadwal, melakukan check-in kehadiran.
3. Siswa melihat tugas dan nilai.
4. Operator input bukti akreditasi dan mapping butir.
5. Kepala melihat dashboard kesiapan.
6. Publik melihat info PPDB dan mengunduh dokumen.
7. Admin mengekspor data dan ringkasan akreditasi.
