# KPI Pengujian

| Metrik | Target | Cara ukur | Bukti |
|---|---:|---|---|
| Pass rate | ≥95% | Passed / total executed | Rekap test case |
| Critical defect terbuka | 0 | Defect log | Defect log |
| High defect terbuka | 0 saat exit | Defect log | Defect log |
| Cakupan modul | 100% modul wajib | RTM | RTM |
| Cakupan requirement | 100% requirement punya test case | RTM | RTM |
| Akses lintas role | 0 temuan | Uji role/IDOR | Screenshot/log API |
| Check-in success rate | ≥98% | Check-in valid / total check-in | Log presensi guru |
| API p95 | ≤1 detik | Postman/Newman | Laporan performa |
| FCP | ≤2,0 detik | Lighthouse | Screenshot |
| LCP | ≤2,5 detik | Lighthouse | Screenshot |
| Performance score | ≥80 | Lighthouse | Screenshot |
| Accessibility score | ≥90 | Lighthouse | Screenshot |
| Akurasi ekspor akreditasi | 100% | Bandingkan data sumber vs ekspor | File ekspor |
| Waktu penyelesaian UAT | Sesuai jadwal | Jadwal vs aktual | Berita acara |

## Aturan pelaporan

- Hitung hanya test case yang benar-benar dieksekusi.
- `Blocked` tidak dihitung sebagai pass.
- Defect `Critical` otomatis menggagalkan exit criteria.
- Perbaikan harus di-retest dan dicatat.
- Angka final diambil dari build staging terakhir yang diuji.
