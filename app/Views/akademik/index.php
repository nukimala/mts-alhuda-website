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
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
