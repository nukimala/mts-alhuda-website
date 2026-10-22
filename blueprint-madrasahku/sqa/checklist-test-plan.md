# Checklist Revisi Test Plan

Target versi: **v0.3**

## Identitas

- [ ] Judul dan versi diperbarui.
- [ ] Tanggal dan penyusun diperbarui.
- [ ] Riwayat revisi ditambahkan.
- [ ] Tujuan pengujian disesuaikan dengan scope baru.

## Ruang lingkup

- [ ] Scope lama (static web + Firebase/Firestore) dihapus atau ditandai tidak berlaku.
- [ ] Scope baru: publik, akademik, presensi guru, akreditasi, PPDB info + dokumen.
- [ ] Web PHP/MySQL disebut eksplisit.
- [ ] Flutter + REST API disebut eksplisit.
- [ ] Autentikasi API token milik backend disebut eksplisit; Firebase/FCM/Firestore tidak boleh muncul.
- [ ] Out of scope ditulis: presensi siswa, rapor lengkap, pembayaran, form PPDB, verifikasi pendaftar, integrasi Dapodik/EMIS, pentest.

## Arsitektur dan lingkungan

- [ ] Diagram D-01 dimasukkan.
- [ ] Diagram D-08 dimasukkan.
- [ ] Tabel lingkungan lokal/staging/produksi ditambahkan.
- [ ] Kebutuhan data uji ditulis.
- [ ] Kebutuhan akun dan role ditulis.
- [ ] Kebutuhan konfigurasi SMTP (reset password/email pengumuman) ditulis.

## Strategi

- [ ] Diagram D-09 dimasukkan.
- [ ] Level unit/integration/E2E ditulis.
- [ ] Jenis pengujian fungsional, integrasi, role/security, regression, UAT ditulis.
- [ ] Performance ringan dan accessibility ditulis.
- [ ] Diagram D-10 bug triage dimasukkan.
- [ ] Severity dan target perbaikan ditulis.

## Jadwal dan peran

- [ ] Jadwal 4 minggu disesuaikan.
- [ ] Peran QA, developer web, developer mobile, operator, guru, kepala ditulis.
- [ ] Kebutuhan UAT dan reviewer ditulis.

## Entry/exit criteria

- [ ] Entry: build staging siap, data uji siap, akun siap.
- [ ] Exit: tidak ada Critical/High terbuka.
- [ ] Exit: pass rate minimal 95%.
- [ ] Exit: alur kritis lulus.
- [ ] Exit: bukti dan laporan lengkap.

## Risiko

- [ ] Risiko token API salah desain ditambahkan.
- [ ] Risiko SMTP belum siap ditambahkan.
- [ ] Risiko tim mobile dan kontrak API ditambahkan.
- [ ] Risiko data akreditasi tidak lengkap ditambahkan.
- [ ] Risiko waktu < 1 bulan ditambahkan.

## Tools dan deliverable

- [ ] Postman/Newman.
- [ ] PHPUnit/skrip uji.
- [ ] Flutter test.
- [ ] Lighthouse.
- [ ] Spreadsheet RTM/defect.
- [ ] Daftar deliverable: test plan, test case, RTM, defect log, laporan, bukti.
