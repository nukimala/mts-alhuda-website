# Revisi SQA

Folder ini berisi panduan revisi tiga dokumen SQA lama agar sesuai scope baru: website publik + akademik + akreditasi, web PHP/MySQL, mobile Flutter, API token milik backend (tanpa Firebase), dan presensi kehadiran guru.

## Dokumen sumber

| Dokumen | File lama | Versi baru |
|---|---|---|
| Test Plan | `TEST_PLAN A2_Website_MTs_Al-Huda_MADRASAHKU.pdf` | v0.3 |
| Test Scenario/Test Case | `1791237007_A_Kelompok_2_Muhammad-Nur_Shulkhi-Maulana_Test_Scenario__Test_Case.pdf` | v2 |
| Laporan SQA | `1789942457_kelompokA2_GolonganA.pdf` | v2 |

## Kebijakan revisi

1. PDF lama diarsipkan, tidak ditimpa.
2. Semua status hasil lama direset menjadi `Not Executed`.
3. Semua temuan lama ditandai `Closed - Out of Scope` jika tidak relevan.
4. Test case baru ditambahkan untuk modul baru.
5. Diagram baru dimasukkan ke dokumen revisi.
6. Bukti eksekusi disimpan per test case.

## Checklist file

- [checklist-test-plan.md](checklist-test-plan.md)
- [checklist-test-scenario.md](checklist-test-scenario.md)
- [checklist-laporan.md](checklist-laporan.md)
- [test-case-baru.md](test-case-baru.md)
- [kpi.md](kpi.md)

## Modul yang wajib diuji

- Publik/CMS
- Auth dan akun
- Akademik
- Presensi guru
- Akreditasi
- PPDB info + dokumen
- API mobile
- Keamanan/role
- Non-fungsional ringan

## Bukti minimum

- Screenshot hasil per test case.
- Log API/Postman.
- Rekaman singkat alur kritis.
- Defect log dengan severity.
- Rekap pass/fail/blocked.
- Tanda tangan/approval UAT.
