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
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Standar Proses &amp; Mutu Pembelajaran
            </div>
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin-bottom: 12px;">
                Program Akademik &amp; <span>Jadwal Pelajaran</span>
            </h1>
            <p style="color: rgba(255,255,255,.85); max-width: 680px; font-size: 14.5px; line-height: 1.6;">
                Penyelenggaraan kurikulum terpadu (Kurikulum Merdeka &amp; Kepesantrenan Salafiyah) yang terstruktur, transparan, dan berorientasi pada pembentukan akhlak mulia.
            </p>
        </div>
    </section>

    <!-- KURIKULUM & PROGRAM -->
    <section class="page-section" style="padding: 50px 0 30px; background: #ffffff;">
        <div class="container">
            <div style="text-align: center; max-width: 680px; margin: 0 auto 36px;">
                <h2 style="font-size: 24px; font-weight: 800; color: #0F172A; margin-bottom: 8px;">
                    Integrasi Kurikulum Terpadu
                </h2>
                <p style="font-size: 14px; color: #64748B;">
                    Mengharmonisasikan standar kompetensi nasional dengan keilmuan khas pesantren.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 22px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">📚</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Kurikulum Merdeka</h3>
                    <p style="font-size: 13px; color: #475569; line-height: 1.55; margin: 0;">Pembelajaran berbasis proyek (P5RA) yang mengasah nalar kritis, kolaborasi, dan kemandirian siswa.</p>
                </div>
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 22px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">🕌</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Pendidikan Kepesantrenan</h3>
                    <p style="font-size: 13px; color: #475569; line-height: 1.55; margin: 0;">Kajian kitab kuning dasar (Safinah, Aqidatul Awam, Jurumiyah), tahsin, dan hafalan Juz 'Amma.</p>
                </div>
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 22px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">🔬</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Literasi Digital &amp; IT</h3>
                    <p style="font-size: 13px; color: #475569; line-height: 1.55; margin: 0;">Praktik komputer di laboratorium, internet sehat, serta presensi mandiri santri via aplikasi mobile.</p>
                </div>
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 22px;">
                    <div style="font-size: 28px; margin-bottom: 8px;">🏆</div>
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 6px;">Bimbingan Prestasi</h3>
                    <p style="font-size: 13px; color: #475569; line-height: 1.55; margin: 0;">Klinik belajar intensif untuk persiapan kompetisi sains madrasah (KSM) dan kejuaraan seni islami.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- JADWAL PELAJARAN PUBLIK (INTERAKTIF & AKREDITASI READY) -->
    <section id="jadwal" style="padding: 40px 0 70px; background: #F8FAFC; border-top: 1px solid #E2E8F0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <div style="font-size: 11.5px; font-weight: 700; color: #15803D; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;">
                        Transparansi Akademik
                    </div>
                    <h2 style="font-size: 24px; font-weight: 800; color: #0F172A; margin: 0;">
                        Jadwal Pembelajaran Mingguan Siswa
                    </h2>
                    <p style="font-size: 13px; color: #64748B; margin-top: 4px;">
                        Tahun Ajaran <?= htmlspecialchars($year['name'] ?? '2026/2027') ?> • Akses jadwal resmi santri per rombongan belajar
                    </p>
                </div>

                <!-- Pilihan Kelas (Tab Pills) -->
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <?php foreach ($classes as $c): ?>
                        <a href="<?= url('akademik?kelas=' . $c['id'] . '#jadwal') ?>" 
                           style="padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: all .15s ease; <?= (int) $c['id'] === $selectedClassId ? 'background:#15803D;color:#fff;box-shadow:0 2px 6px rgba(21,128,61,.2)' : 'background:#fff;color:#475569;border:1px solid #CBD5E1' ?>">
                            Kelas <?= htmlspecialchars($c['name']) ?> (Tk. <?= $c['level'] ?>)
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabel Jadwal Pelajaran -->
            <div style="background: #ffffff; border-radius: 14px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.04);">
                <?php if (empty($schedules)): ?>
                    <div style="padding: 48px 20px; text-align: center; color: #64748B;">
                        <span style="font-size: 32px; display: block; margin-bottom: 8px;">📅</span>
                        <b style="color: #0F172A; font-size: 15px;">Jadwal pelajaran untuk kelas ini sedang dalam penyusunan.</b>
                        <p style="font-size: 13px; margin-top: 4px;">Silakan pilih kelas lainnya di atas.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                            <thead>
                                <tr style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; color: #475569; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em;">
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
                                                    <td rowspan="<?= count($byDay[$d]) ?>" style="padding: 14px 20px; font-weight: 800; color: #15803D; background: #F0FDF4; vertical-align: middle; border-right: 1px solid #E2E8F0;">
                                                        <span style="background: #DCFCE7; padding: 4px 10px; border-radius: 6px; display: inline-block;">
                                                            <?= $dayNames[$d] ?>
                                                        </span>
                                                    </td>
                                                <?php endif; ?>
                                                <td style="padding: 14px 20px; font-weight: 700; color: #334155;">
                                                    ⏱️ <?= date('H:i', strtotime($sch['start_time'])) ?> – <?= date('H:i', strtotime($sch['end_time'])) ?> WIB
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <b style="color: #0F172A; font-size: 14px;"><?= htmlspecialchars($sch['subject_name']) ?></b>
                                                    <span style="font-size: 11.5px; color: #64748B; margin-left: 6px;">(<?= htmlspecialchars($sch['subject_code']) ?>)</span>
                                                </td>
                                                <td style="padding: 14px 20px; color: #334155;">
                                                    👤 <?= htmlspecialchars($sch['teacher_name']) ?>
                                                </td>
                                                <td style="padding: 14px 20px;">
                                                    <span style="background: #F1F5F9; color: #475569; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
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
