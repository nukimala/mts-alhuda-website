<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$year = $data['year'] ?? [];
$classes = $data['classes'] ?? [];
$selectedClassId = (int) ($data['selected_class_id'] ?? 0);
$schedules = $data['schedules'] ?? [];

$dayNames = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
];

// Group schedules by day
$byDay = [];
for ($d = 1; $d <= 6; $d++) {
    $byDay[$d] = [];
}
foreach ($schedules as $s) {
    $byDay[(int) $s['day_of_week']][] = $s;
}
?>

<main>
    <section class="page-hero-clean">
        <div class="container">
            <div class="hero-label">Standar Mutu Pembelajaran</div>
            <h1>Program Akademik &amp; <span>Jadwal Pelajaran</span></h1>
            <p>
                Penyelenggaraan kurikulum terpadu (Kurikulum Merdeka dan Kepesantrenan Salafiyah) yang terstruktur, transparan, dan berorientasi pada pembentukan akhlak mulia.
            </p>
        </div>
    </section>

    <!-- KURIKULUM & PROGRAM -->
    <section style="padding: 56px 0 32px; background: #FFFFFF;">
        <div class="container">
            <div class="section-head centered">
                <div class="section-pretitle">Kurikulum Berkelanjutan</div>
                <h2 class="section-title">Integrasi Kurikulum Terpadu</h2>
                <p class="section-subtitle">
                    Mengharmonisasikan standar kompetensi nasional Kemenag dengan khazanah keilmuan kepesantrenan.
                </p>
            </div>

            <div class="pillars-grid">
                <article class="pillar-item">
                    <span class="pillar-badge">Kurikulum Nasional</span>
                    <h3 class="pillar-heading">Kurikulum Merdeka &amp; P5RA</h3>
                    <p class="pillar-desc">
                        Pembelajaran berbasis nalar kritis, eksplorasi sains terapan, serta penguatan Profil Pelajar Pancasila dan Rahmatan lil Alamin.
                    </p>
                </article>

                <article class="pillar-item">
                    <span class="pillar-badge">Kepesantrenan</span>
                    <h3 class="pillar-heading">Pendalaman Kitab Kuning</h3>
                    <p class="pillar-desc">
                        Pembelajaran materi keagamaan mendalam: Mabadi Fiqih, Safinatun Najah, Aqidatul Awam, tata bahasa Arab Jurumiyah, serta tahsin Al-Qur'an.
                    </p>
                </article>

                <article class="pillar-item">
                    <span class="pillar-badge">Kompetensi Abad 21</span>
                    <h3 class="pillar-heading">Literasi Digital &amp; IT Komputer</h3>
                    <p class="pillar-desc">
                        Praktik terarah di laboratorium komputer madrasah, penguasaan aplikasi perkantoran, internet sehat, dan pembiasaan sistem digital madrasah.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- JADWAL PELAJARAN PUBLIK -->
    <section id="jadwal" style="padding: 44px 0 72px; background: #F8FAFC; border-top: 1px solid var(--border);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div class="section-pretitle">Transparansi Akademik</div>
                    <h2 class="section-title" style="margin: 0;">Jadwal Pembelajaran Mingguan</h2>
                    <p style="font-size: 13.5px; color: #64748B; margin: 4px 0 0;">
                        Tahun Ajaran <?= htmlspecialchars($year['name'] ?? '2026/2027') ?> &middot; Akses jadwal resmi per rombongan belajar
                    </p>
                </div>

                <!-- Pilihan Kelas -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php foreach ($classes as $c): ?>
                        <a href="<?= url('akademik?kelas=' . $c['id'] . '#jadwal') ?>" 
                           class="btn <?= (int) $c['id'] === $selectedClassId ? 'btn-primary' : 'btn-outline' ?>"
                           style="min-height: 38px; padding: 7px 16px; font-size: 13px;">
                            Kelas <?= htmlspecialchars($c['name']) ?> (Tk. <?= $c['level'] ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabel Jadwal Pelajaran -->
            <div style="background: #FFFFFF; border-radius: 14px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-sm);">
                <?php if (empty($schedules)): ?>
                    <div style="padding: 48px 20px; text-align: center; color: #64748B;">
                        <b style="color: #0F172A; font-size: 15px; display: block; margin-bottom: 6px;">Jadwal pelajaran untuk kelas ini belum diinput atau sedang diperbarui.</b>
                        <p style="font-size: 13.5px; margin: 0;">Silakan pilih rombongan belajar (kelas) lainnya pada tombol pilihan di atas.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                            <thead>
                                <tr style="background: #F8FAFC; border-bottom: 1px solid var(--border); color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <th style="padding: 14px 20px; width: 120px;">Hari</th>
                                    <th style="padding: 14px 20px; width: 160px;">Waktu</th>
                                    <th style="padding: 14px 20px;">Mata Pelajaran</th>
                                    <th style="padding: 14px 20px;">Guru Pengampu</th>
                                    <th style="padding: 14px 20px; width: 140px;">Ruangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($d = 1; $d <= 6; $d++): ?>
                                    <?php if (!empty($byDay[$d])): ?>
                                        <?php foreach ($byDay[$d] as $idx => $sch): ?>
                                            <tr style="border-bottom: 1px solid #F1F5F9;">
                                                <?php if ($idx === 0): ?>
                                                    <td rowspan="<?= count($byDay[$d]) ?>" style="padding: 14px 20px; font-weight: 800; color: #15803D; background: #F0FDF4; vertical-align: middle; border-right: 1px solid var(--border);">
                                                        <span style="background: #DCFCE7; padding: 4px 10px; border-radius: 6px; display: inline-block;">
                                                            <?= $dayNames[$d] ?>
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                                <td style="padding: 14px 20px; font-weight: 700; color: #334155;">
                                                    <?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?> WIB
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <strong style="color: #0F172A; font-size: 14px;"><?= htmlspecialchars($sch['subject_name']) ?></strong>
                                                    <span style="font-size: 12px; color: #64748B; margin-left: 6px;">(<?= htmlspecialchars($sch['subject_code']) ?>)</span>
                                                </td>
                                                <td style="padding: 14px 20px; color: #334155;">
                                                    <?= htmlspecialchars($sch['teacher_name']) ?>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <span style="background: #F1F5F9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                                        <?= htmlspecialchars($sch['room'] ?? 'Ruang Kelas') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- GURU PIKET & PEMBIASAAN PAGI RESMI -->
            <div style="margin-top: 36px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                <!-- Card Guru Piket -->
                <div style="background: #FFFFFF; border-radius: 14px; border: 1px solid var(--border); padding: 24px 28px; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                        <span style="font-size: 20px;">📋</span>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F172A;">Jadwal Guru Piket Mingguan</h3>
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 13.5px; display: flex; flex-direction: column; gap: 10px;">
                        <li style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #E2E8F0;">
                            <strong style="color: #15803D; width: 80px;">Senin:</strong>
                            <span style="color: #334155; text-align: right; flex: 1;">Subiyatin, S.Si. &bull; Agus Setiawan, S.Pd. &bull; Sodikin Muslimin, S.Pd.</span>
                        </li>
                        <li style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #E2E8F0;">
                            <strong style="color: #15803D; width: 80px;">Selasa:</strong>
                            <span style="color: #334155; text-align: right; flex: 1;">Farid Kurniawan N, SH &bull; Lilik Rahmawati, S.Pd. &bull; Agus Setiawan, S.Pd.</span>
                        </li>
                        <li style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #E2E8F0;">
                            <strong style="color: #15803D; width: 80px;">Rabu:</strong>
                            <span style="color: #334155; text-align: right; flex: 1;">Drs. Sarmin &bull; Agus Setiawan, S.Pd. &bull; Agus Budi Irawan, S.Pd.</span>
                        </li>
                        <li style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #E2E8F0;">
                            <strong style="color: #15803D; width: 80px;">Kamis:</strong>
                            <span style="color: #334155; text-align: right; flex: 1;">Agus Setiawan, S.Pd. &bull; Ulfatul Khumaydah, S.H.I &bull; Agus Budi Irawan, S.Pd.</span>
                        </li>
                        <li style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px dashed #E2E8F0;">
                            <strong style="color: #15803D; width: 80px;">Jum'at:</strong>
                            <span style="color: #334155; text-align: right; flex: 1;">M. Musthofa, S.Pd.I &bull; Farid Kurniawan N, SH &bull; Siti Amiroh, S.Pd.I</span>
                        </li>
                        <li style="display: flex; justify-content: space-between;">
                            <strong style="color: #15803D; width: 80px;">Sabtu:</strong>
                            <span style="color: #334155; text-align: right; flex: 1;">Setyo Jatmiko, S.Pd. &bull; Sodikin Muslimin, S.Pd. &bull; Agus Budi Irawan, S.Pd.</span>
                        </li>
                    </ul>
                </div>

                <!-- Card Pembiasaan Karakter & Tata Tertib -->
                <div style="background: #FFFFFF; border-radius: 14px; border: 1px solid var(--border); padding: 24px 28px; box-shadow: var(--shadow-sm); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                            <span style="font-size: 20px;">🕌</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0F172A;">Pembiasaan Harian Santri</h3>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px; color: #475569;">
                            <div style="background: #F8FAFC; padding: 12px 14px; border-radius: 8px; border-left: 3px solid #15803D;">
                                <strong style="color: #0F172A; display: block; margin-bottom: 2px;">Jam ke-0 (06.45 - 07.00 WIB)</strong>
                                Pembiasaan Sholawat Nariyah dan Asmaul Husna setiap pagi bersama guru jam pertama.
                            </div>
                            <div style="background: #F8FAFC; padding: 12px 14px; border-radius: 8px; border-left: 3px solid #0284C7;">
                                <strong style="color: #0F172A; display: block; margin-bottom: 2px;">Jum'at Berkah &amp; Sabtu Sehat</strong>
                                Pembacaan Tahlil &amp; Surat Yasin setiap Jum'at pagi; Senam Kebugaran Jasmani dan Shalat Dhuha berjamaah setiap Sabtu pagi.
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B;">
                        Berlaku efektif mulai tanggal <strong>2 November 2026</strong> &middot; Mengetahui: Kepala Madrasah <strong>M. Abdul Haris Jauhari, S.Pd.I</strong> &amp; Waka Kurikulum <strong>Siti Amiroh, S.Pd.I</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
