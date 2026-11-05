<?php
ob_start();

$dayNames = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
];

$dayIcons = [
    1 => '☀️',
    2 => '📖',
    3 => '✏️',
    4 => '🔬',
    5 => '🕌',
    6 => '⚽',
];

// Group schedules by day
$schedulesByDay = [];
for ($d = 1; $d <= 6; $d++) {
    $schedulesByDay[$d] = [];
}
foreach (($data['schedules'] ?? []) as $s) {
    $schedulesByDay[(int) $s['day_of_week']][] = $s;
}

// Calculate summary stats
$totalSchedules = count($data['schedules'] ?? []);
$uniqueSubjects = count(array_unique(array_column($data['schedules'] ?? [], 'subject_id')));
$uniqueTeachers = count(array_unique(array_column($data['schedules'] ?? [], 'teacher_id')));
$activeDays = count(array_filter($schedulesByDay, fn($dayList) => !empty($dayList)));
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Jadwal Pelajaran</b>
</div>

<div class="page-head">
    <div>
        <h1>Jadwal Pelajaran Mingguan</h1>
        <p>Matriks jadwal pelajaran kelas dengan validasi anti-bentrok guru dan ruang belajar</p>
    </div>
    <div class="actions">
        <button type="button" class="btn btn-primary btn-sm" onclick="openModalTambah()">
            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Jadwal Baru
        </button>
    </div>
</div>

<?php if (!empty($data['flash'])): ?>
    <div class="notice ok">
        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        <div><?= htmlspecialchars($data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Stat Summary -->
<div class="grid grid-4" style="margin-bottom:20px">
    <div class="card stat">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <div>
            <small>Total Sesi Belajar</small>
            <strong><?= $totalSchedules ?> Jam</strong>
            <div class="hint">Pada kelas terpilih</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon blue">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
        <div>
            <small>Mata Pelajaran</small>
            <strong><?= $uniqueSubjects ?> Mapel</strong>
            <div class="hint">Agama, umum, &amp; mulok</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon" style="background:#DCFCE7;color:#15803D">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <small>Guru Terjadwal</small>
            <strong><?= $uniqueTeachers ?> Pendidik</strong>
            <div class="hint">Pengampu di kelas ini</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
            <small>Hari Efektif</small>
            <strong><?= $activeDays ?> Hari / Pekan</strong>
            <div class="hint">Senin s.d. Sabtu</div>
        </div>
    </div>
</div>

<!-- Class & Year Filter Selector Card -->
<div class="card" style="margin-bottom:20px">
    <form method="get" action="<?= base_url('admin/jadwal') ?>" class="toolbar" style="padding:14px 18px">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="field-inline" style="background:#F8FAFC">
                <span style="font-size:11.5px;font-weight:700;color:var(--gray)">TAHUN:</span>
                <select name="year_id" onchange="this.form.submit()" style="font-weight:700;color:var(--text);cursor:pointer">
                    <?php foreach (($data['academic_years'] ?? []) as $ay): ?>
                        <option value="<?= $ay['id'] ?>" <?= (int) $data['current_year_id'] === (int) $ay['id'] ? 'selected' : '' ?>>
                            Tahun Ajaran <?= htmlspecialchars($ay['name'], ENT_QUOTES, 'UTF-8') ?> <?= (int) $ay['is_active'] === 1 ? '★' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-inline" style="background:#F0FDF4;border-color:#BBF7D0">
                <span style="font-size:11.5px;font-weight:700;color:var(--green)">KELAS:</span>
                <select name="class_id" onchange="this.form.submit()" style="font-weight:800;color:#15803D;cursor:pointer">
                    <?php foreach (($data['classes'] ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (int) $data['current_class_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            Kelas <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> (Tingkat <?= $c['level'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="right">
            <div style="display:flex;background:#F1F5F9;padding:3px;border-radius:8px;gap:2px">
                <button type="button" id="btnModeVisual" class="btn btn-sm" onclick="switchJadwalMode('visual')" style="background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.08);padding:5px 12px;font-weight:700;color:var(--text)">
                    <svg viewBox="0 0 24 24" style="width:14px;height:14px"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Matriks Mingguan
                </button>
                <button type="button" id="btnModeTabel" class="btn btn-sm btn-ghost" onclick="switchJadwalMode('tabel')" style="padding:5px 12px">
                    <svg viewBox="0 0 24 24" style="width:14px;height:14px"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Tabel Daftar
                </button>
            </div>
        </div>
    </form>
</div>

<!-- VIEW 1: Matriks Visual Timetable Mingguan (6 Kolom Hari) -->
<div id="modeVisual" style="margin-bottom:28px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;align-items:start">
        <?php for ($d = 1; $d <= 6; $d++): ?>
            <?php $daySchedules = $schedulesByDay[$d]; ?>
            <div class="card" style="display:flex;flex-direction:column;border-top:3px solid <?= !empty($daySchedules) ? 'var(--green)' : 'var(--border)' ?>;">
                <!-- Header Hari -->
                <div style="padding:12px 14px;background:#F8FAFC;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:6px">
                        <span style="font-size:16px"><?= $dayIcons[$d] ?></span>
                        <b style="font-size:14px;color:var(--text)"><?= $dayNames[$d] ?></b>
                    </div>
                    <span class="pill plain <?= !empty($daySchedules) ? 'green' : 'gray' ?>" style="font-size:10.5px">
                        <?= count($daySchedules) ?> Jam
                    </span>
                </div>

                <!-- Daftar Slot Pelajaran Hari Ini -->
                <div style="padding:12px;display:flex;flex-direction:column;gap:10px;min-height:140px;background:#FAFAFA">
                    <?php if (empty($daySchedules)): ?>
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 10px;text-align:center;color:var(--gray)">
                            <span style="font-size:24px;opacity:.4;margin-bottom:4px">☕</span>
                            <span style="font-size:12px;font-weight:600">Tidak ada jadwal</span>
                            <span style="font-size:11px;color:#94A3B8">Hari libur / kegiatan santri</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($daySchedules as $sch): ?>
                            <div style="background:#ffffff;border:1px solid #E2E8F0;border-left:4px solid var(--green);border-radius:8px;padding:10px 12px;box-shadow:0 1px 3px rgba(0,0,0,.04);position:relative">
                                <!-- Jam & Ruang -->
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                                    <span style="font-size:11.5px;font-weight:800;color:#15803D;background:#DCFCE7;padding:2px 6px;border-radius:4px;display:inline-flex;align-items:center;gap:4px">
                                        ⏱️ <?= date('H:i', strtotime($sch['start_time'])) ?> - <?= date('H:i', strtotime($sch['end_time'])) ?>
                                    </span>
                                    <span class="pill plain gray" style="font-size:10.5px">
                                        <?= htmlspecialchars($sch['room'] ?? 'Ruang Kelas', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <!-- Mata Pelajaran -->
                                <div style="font-size:13.5px;font-weight:800;color:var(--text);line-height:1.3;margin-bottom:4px">
                                    <?= htmlspecialchars($sch['subject_name'], ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <!-- Guru Pengampu & Aksi Hapus -->
                                <div style="display:flex;align-items:center;justify-content:space-between;border-top:1px dashed #E2E8F0;padding-top:6px;margin-top:6px">
                                    <div style="font-size:11.5px;color:var(--gray);display:flex;align-items:center;gap:5px">
                                        <span>👤</span> <?= htmlspecialchars($sch['teacher_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <form method="post" action="<?= base_url('admin/jadwal/delete/' . $sch['id']) ?>" onsubmit="return confirm('Hapus jam pelajaran ini?');" style="margin:0">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="class_id" value="<?= $data['current_class_id'] ?>">
                                        <input type="hidden" name="year_id" value="<?= $data['current_year_id'] ?>">
                                        <button type="submit" style="background:none;border:none;cursor:pointer;color:#DC2626;padding:2px;font-size:12px" title="Hapus Jadwal">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Footer Card: Tombol Cepat Tambah Jam -->
                <div style="padding:8px 12px;background:#F8FAFC;border-top:1px solid var(--border);text-align:center">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openModalTambah(<?= $d ?>)" style="width:100%;font-size:11.5px;color:var(--green);font-weight:700;padding:4px">
                        + Tambah Jam <?= $dayNames[$d] ?>
                    </button>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- VIEW 2: Tabel Lengkap -->
<div id="modeTabel" class="card" style="display:none;margin-bottom:28px">
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th style="width:110px">Hari</th>
                    <th style="width:150px">Waktu</th>
                    <th>Mata Pelajaran</th>
                    <th>Guru Pengampu</th>
                    <th>Ruang</th>
                    <th style="width:70px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['schedules'])): ?>
                <tr>
                    <td colspan="6" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <b>Belum ada jadwal yang diatur untuk kelas ini.</b>
                    </td>
                </tr>
            <?php else: ?>
                <?php for ($d = 1; $d <= 6; $d++): ?>
                    <?php if (!empty($schedulesByDay[$d])): ?>
                        <?php foreach ($schedulesByDay[$d] as $idx => $sch): ?>
                            <tr>
                                <?php if ($idx === 0): ?>
                                    <td rowspan="<?= count($schedulesByDay[$d]) ?>" style="font-weight:800;background:#F8FAFC;vertical-align:middle;border-right:1px solid var(--border)">
                                        <span class="pill green"><?= $dayNames[$d] ?></span>
                                    </td>
                                <?php endif; ?>
                                <td><b><?= date('H:i', strtotime($sch['start_time'])) ?> – <?= date('H:i', strtotime($sch['end_time'])) ?></b></td>
                                <td>
                                    <div class="t-title"><?= htmlspecialchars($sch['subject_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub"><?= htmlspecialchars($sch['subject_code'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><?= htmlspecialchars($sch['teacher_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="pill gray plain"><?= htmlspecialchars($sch['room'] ?? 'Ruang Kelas', ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <form method="post" action="<?= base_url('admin/jadwal/delete/' . $sch['id']) ?>" onsubmit="return confirm('Hapus sesi jadwal ini?');" style="display:inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="class_id" value="<?= $data['current_class_id'] ?>">
                                        <input type="hidden" name="year_id" value="<?= $data['current_year_id'] ?>">
                                        <button type="submit" class="icon-btn" title="Hapus Jadwal" style="color:#DC2626;width:28px;height:28px">
                                            <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endfor; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Jadwal -->
<div id="modalTambahJadwal" class="modal-overlay" style="display:none">
    <div class="modal-mock">
        <div class="modal-head">
            <h3>Tambah Sesi Jadwal Pelajaran</h3>
            <button type="button" class="icon-btn" onclick="closeModalTambah()">&times;</button>
        </div>
        <form method="post" action="<?= base_url('admin/jadwal/store') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="year_id" value="<?= $data['current_year_id'] ?>">
            <input type="hidden" name="class_id" value="<?= $data['current_class_id'] ?>">

            <div class="modal-body">
                <div class="field">
                    <label>Target Kelas</label>
                    <input type="text" value="<?php
                        foreach (($data['classes'] ?? []) as $c) {
                            if ((int) $c['id'] === (int) $data['current_class_id']) {
                                echo 'Kelas ' . htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') . ' (Tingkat ' . $c['level'] . ')';
                                break;
                            }
                        }
                    ?>" readonly style="background:#F8FAFC;font-weight:700">
                </div>

                <div class="field">
                    <label for="modal_day">Hari Pembelajaran <span class="req">*</span></label>
                    <select id="modal_day" name="day_of_week" required>
                        <option value="1">Senin</option>
                        <option value="2">Selasa</option>
                        <option value="3">Rabu</option>
                        <option value="4">Kamis</option>
                        <option value="5">Jumat</option>
                        <option value="6">Sabtu</option>
                    </select>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="start_time">Jam Mulai <span class="req">*</span></label>
                        <input type="time" id="start_time" name="start_time" value="07:00" required>
                    </div>
                    <div class="field">
                        <label for="end_time">Jam Selesai <span class="req">*</span></label>
                        <input type="time" id="end_time" name="end_time" value="08:20" required>
                    </div>
                </div>

                <div class="field">
                    <label for="modal_subject">Mata Pelajaran <span class="req">*</span></label>
                    <select id="modal_subject" name="subject_id" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <?php foreach (($data['subjects'] ?? []) as $sub): ?>
                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($sub['code'], ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="modal_teacher">Guru Pengampu <span class="req">*</span></label>
                    <select id="modal_teacher" name="teacher_id" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach (($data['teachers'] ?? []) as $tch): ?>
                            <option value="<?= $tch['id'] ?>"><?= htmlspecialchars($tch['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="modal_room">Ruangan / Tempat</label>
                    <input type="text" id="modal_room" name="room" value="Ruang Kelas" placeholder="Contoh: R-01, Lab Komputer, Lapangan">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModalTambah()">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchJadwalMode(mode) {
    const visual = document.getElementById('modeVisual');
    const tabel = document.getElementById('modeTabel');
    const btnVisual = document.getElementById('btnModeVisual');
    const btnTabel = document.getElementById('btnModeTabel');

    if (mode === 'visual') {
        visual.style.display = 'block';
        tabel.style.display = 'none';
        btnVisual.style.background = '#fff';
        btnVisual.style.boxShadow = '0 1px 2px rgba(0,0,0,.08)';
        btnVisual.style.color = 'var(--text)';
        btnVisual.style.fontWeight = '700';
        btnTabel.style.background = 'transparent';
        btnTabel.style.boxShadow = 'none';
        btnTabel.style.color = 'var(--gray)';
        btnTabel.style.fontWeight = '500';
    } else {
        visual.style.display = 'none';
        tabel.style.display = 'block';
        btnTabel.style.background = '#fff';
        btnTabel.style.boxShadow = '0 1px 2px rgba(0,0,0,.08)';
        btnTabel.style.color = 'var(--text)';
        btnTabel.style.fontWeight = '700';
        btnVisual.style.background = 'transparent';
        btnVisual.style.boxShadow = 'none';
        btnVisual.style.color = 'var(--gray)';
        btnVisual.style.fontWeight = '500';
    }
}

function openModalTambah(dayNum) {
    if (dayNum) {
        document.getElementById('modal_day').value = dayNum;
    }
    document.getElementById('modalTambahJadwal').style.display = 'grid';
}

function closeModalTambah() {
    document.getElementById('modalTambahJadwal').style.display = 'none';
}
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
