<?php
ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Presensi Guru &amp; Mobile App</b>
</div>

<div class="page-head">
    <div>
        <h1>Rekap Presensi Guru &amp; Mobile App</h1>
        <p>Tanggal: <b><?= date('l, d F Y', strtotime($data['date'])) ?></b> &middot; Total <?= (int) $data['total_teachers'] ?> guru terdaftar &middot; Sinkronisasi Waktu Server &amp; Mobile App</p>
    </div>
    <div class="actions">
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalKoreksiPresensi').style.display='grid'">+ Koreksi / Catat Presensi</button>
    </div>
</div>

<div class="notice info" style="margin-bottom:18px">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
    <div>
        <b>Kolaborasi Mobile App MADRASAHKU &amp; Web Admin:</b> Guru melakukan check-in mandiri saat tiba di madrasah melalui tombol presensi di aplikasi Flutter. Operator memantau secara real-time dan dapat melakukan koreksi manual jika guru dinas luar, izin, atau terkendala smartphone.
    </div>
</div>

<?php if (!empty($data['flash'])): ?>
    <div class="notice success" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
        <div><?= htmlspecialchars($data['flash'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($data['error'])): ?>
    <div class="notice warn" style="margin-bottom:16px">
        <svg viewBox="0 0 24 24"><path d="M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <div><?= htmlspecialchars($data['error'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="grid grid-4" style="margin-bottom:18px">
    <div class="card stat">
        <div class="stat-icon"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></div>
        <div>
            <small>Hadir Tepat Waktu</small>
            <strong><?= (int) $data['stat_hadir'] ?></strong>
            <div class="hint"><?= $data['total_teachers'] > 0 ? round(($data['stat_hadir'] / $data['total_teachers']) * 100, 1) : 0 ?>% dari seluruh guru</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon amber"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <div>
            <small>Terlambat</small>
            <strong><?= (int) $data['stat_terlambat'] ?></strong>
            <div class="hint">Check-in lewat batas toleransi</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon blue"><svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg></div>
        <div>
            <small>Izin / Sakit / Dinas</small>
            <strong><?= (int) $data['stat_izin'] ?></strong>
            <div class="hint">Tercatat resmi</div>
        </div>
    </div>
    <div class="card stat">
        <div class="stat-icon red"><svg viewBox="0 0 24 24"><path d="M18.4 5.6a9 9 0 1 1-12.8 12.8"/><path d="M3 3l18 18"/></svg></div>
        <div>
            <small>Belum Check-in / Alpa</small>
            <strong><?= (int) $data['stat_belum'] ?></strong>
            <div class="hint">Belum ada data masuk</div>
        </div>
    </div>
</div>

<div class="card">
    <form method="get" action="<?= base_url('admin/presensi') ?>" class="toolbar">
        <div class="field-inline">
            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            <input type="date" name="date" value="<?= htmlspecialchars($data['date'], ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()">
        </div>

        <div class="field-inline">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="hadir" <?= ($data['status'] ?? '') === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                <option value="terlambat" <?= ($data['status'] ?? '') === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                <option value="izin" <?= ($data['status'] ?? '') === 'izin' ? 'selected' : '' ?>>Izin</option>
                <option value="sakit" <?= ($data['status'] ?? '') === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                <option value="dinas" <?= ($data['status'] ?? '') === 'dinas' ? 'selected' : '' ?>>Dinas</option>
                <option value="alpa" <?= ($data['status'] ?? '') === 'alpa' ? 'selected' : '' ?>>Alpa</option>
            </select>
        </div>

        <div class="field-inline" style="min-width:200px">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama atau NIP guru...">
        </div>

        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
        <?php if (!empty($data['q']) || !empty($data['status'])): ?>
            <a href="<?= base_url('admin/presensi?date=' . $data['date']) ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>

        <div class="right">
            <span class="pill plain gray"><?= count($data['records']) ?> guru</span>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Guru Pengampu</th>
                    <th>Jam Datang</th>
                    <th>Jam Pulang</th>
                    <th>Status Presensi</th>
                    <th>Sumber / Metode</th>
                    <th>Catatan &amp; GPS</th>
                    <th style="width:70px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['records'])): ?>
                <tr>
                    <td colspan="7" style="padding:32px 16px;text-align:center;color:#6B7280">
                        <b>Belum ada data guru terdaftar.</b>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['records'] as $r): ?>
                    <tr>
                        <td>
                            <div class="cell-flex">
                                <div class="avatar"><?= htmlspecialchars(strtoupper(mb_substr($r['teacher_name'] ?? 'G', 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
                                <div>
                                    <div class="t-title"><?= htmlspecialchars($r['teacher_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub"><?= htmlspecialchars($r['nip'] ?? 'NIP: —', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($r['check_in_at'])): ?>
                                <b><?= date('H.i', strtotime($r['check_in_at'])) ?> WIB</b>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['check_out_at'])): ?>
                                <b><?= date('H.i', strtotime($r['check_out_at'])) ?> WIB</b>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $st = $r['status'] ?? null;
                            if ($st === 'hadir'): ?>
                                <span class="pill green">Hadir</span>
                            <?php elseif ($st === 'terlambat'): ?>
                                <span class="pill amber">Terlambat</span>
                            <?php elseif (in_array($st, ['izin', 'sakit', 'dinas'], true)): ?>
                                <span class="pill blue"><?= ucfirst($st) ?></span>
                            <?php elseif ($st === 'alpa'): ?>
                                <span class="pill red">Alpa</span>
                            <?php else: ?>
                                <span class="pill gray">Belum Masuk</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($r['method'] ?? '') === 'self'): ?>
                                <span class="pill green plain" title="Presensi mandiri via Aplikasi Mobile MADRASAHKU">📱 Mobile App</span>
                            <?php elseif (($r['method'] ?? '') === 'operator'): ?>
                                <span class="pill amber plain" title="Koreksi manual oleh Operator di Panel Web">🖥️ Operator Web</span>
                            <?php elseif (($r['method'] ?? '') === 'system'): ?>
                                <span class="pill gray plain" title="Otomatis sistem">⚙️ Sistem</span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="tiny muted"><?= htmlspecialchars($r['note'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <button type="button" class="icon-btn" title="Koreksi Presensi" onclick="bukaModalKoreksi(<?= $r['teacher_id'] ?>, '<?= htmlspecialchars(addslashes($r['teacher_name']), ENT_QUOTES, 'UTF-8') ?>', '<?= $r['status'] ?? 'hadir' ?>', '<?= !empty($r['check_in_at']) ? date('H:i', strtotime($r['check_in_at'])) : '07:00' ?>', '<?= !empty($r['check_out_at']) ? date('H:i', strtotime($r['check_out_at'])) : '' ?>', '<?= htmlspecialchars(addslashes($r['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Koreksi Presensi -->
<div id="modalKoreksiPresensi" class="modal-overlay" style="display:none">
    <div class="modal-mock">
        <div class="modal-head">
            <h3>Pencatatan / Koreksi Presensi Guru</h3>
            <button type="button" class="icon-btn" onclick="document.getElementById('modalKoreksiPresensi').style.display='none'">&times;</button>
        </div>
        <form method="post" action="<?= base_url('admin/presensi/correct') ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($data['date'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="modal-body">
                <div class="field">
                    <label for="kor_teacher_id">Pilih Guru <span class="req">*</span></label>
                    <select id="kor_teacher_id" name="teacher_id" required>
                        <?php foreach (($data['teachers'] ?? $data['guru'] ?? []) as $t): ?>
                            <option value="<?= $t['teacher_id'] ?>"><?= htmlspecialchars($t['teacher_name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Tanggal Presensi</label>
                    <input type="text" value="<?= date('d F Y', strtotime($data['date'])) ?>" readonly style="background:#F9FAFB">
                </div>

                <div class="field">
                    <label for="kor_status">Status Presensi <span class="req">*</span></label>
                    <select id="kor_status" name="status">
                        <option value="hadir">Hadir (Tepat Waktu)</option>
                        <option value="terlambat">Terlambat</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                        <option value="dinas">Dinas Luar / Pelatihan</option>
                        <option value="alpa">Alpa / Tanpa Keterangan</option>
                    </select>
                </div>

                <div class="grid grid-2">
                    <div class="field">
                        <label for="kor_check_in">Jam Masuk (WIB)</label>
                        <input type="time" id="kor_check_in" name="check_in_time" value="07:00">
                    </div>
                    <div class="field">
                        <label for="kor_check_out">Jam Pulang (WIB)</label>
                        <input type="time" id="kor_check_out" name="check_out_time">
                    </div>
                </div>

                <div class="field">
                    <label for="kor_note">Catatan / Alasan Koreksi</label>
                    <input type="text" id="kor_note" name="note" placeholder="Contoh: Mengikuti MGMP Kemenag / Sakit ada surat">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('modalKoreksiPresensi').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Presensi</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalKoreksi(teacherId, teacherName, status, checkIn, checkOut, note) {
    document.getElementById('kor_teacher_id').value = teacherId;
    document.getElementById('kor_status').value = status;
    document.getElementById('kor_check_in').value = checkIn;
    document.getElementById('kor_check_out').value = checkOut;
    document.getElementById('kor_note').value = note;
    document.getElementById('modalKoreksiPresensi').style.display = 'grid';
}
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
