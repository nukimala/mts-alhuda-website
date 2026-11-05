<?php
ob_start();
$classes = $data['classes'] ?? [];
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a> / <b>Pengguna &amp; Akun Mobile</b>
</div>

<div class="page-head">
    <div>
        <h1>Kelola Pengguna &amp; Akun Mobile</h1>
        <p><?= (int) $data['stat_guru'] ?> guru &middot; <?= (int) $data['stat_siswa'] ?> siswa &middot; <?= (int) $data['stat_operator'] ?> operator &middot; <?= (int) $data['stat_kepala'] ?> kepala &middot; <?= (int) $data['stat_akreditasi'] ?> tim akreditasi</p>
    </div>
    <div class="actions">
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalTambahPengguna').style.display='grid'">+ Tambah Pengguna</button>
    </div>
</div>

<div class="notice info" style="margin-bottom:18px">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
    <div>
        <b>Satu Akun Terpadu untuk Web &amp; Mobile MADRASAHKU:</b>
        Aplikasi mobile tidak menyediakan pendaftaran mandiri. Akun Guru dan Siswa dibuat langsung di sini oleh Operator. Akun yang dibuat akan langsung tersambung dengan profil guru (NIP) atau profil siswa (NIS &amp; Kelas) agar dapat mengakses jadwal, presensi, dan tugas.
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

<div class="card">
    <div class="toolbar">
        <form method="get" action="<?= base_url('admin/pengguna') ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <div class="field-inline" style="min-width:220px">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="text" name="q" value="<?= htmlspecialchars($data['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari nama, email, NIP, NIS...">
            </div>

            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="<?= base_url('admin/pengguna') ?>" class="pill <?= empty($data['current_role']) ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Semua</a>
                <a href="<?= base_url('admin/pengguna?role=admin') ?>" class="pill <?= ($data['current_role'] ?? '') === 'admin' ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Admin</a>
                <a href="<?= base_url('admin/pengguna?role=operator') ?>" class="pill <?= ($data['current_role'] ?? '') === 'operator' ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Operator</a>
                <a href="<?= base_url('admin/pengguna?role=kepala') ?>" class="pill <?= ($data['current_role'] ?? '') === 'kepala' ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Kepala</a>
                <a href="<?= base_url('admin/pengguna?role=guru') ?>" class="pill <?= ($data['current_role'] ?? '') === 'guru' ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Guru</a>
                <a href="<?= base_url('admin/pengguna?role=siswa') ?>" class="pill <?= ($data['current_role'] ?? '') === 'siswa' ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Siswa</a>
                <a href="<?= base_url('admin/pengguna?role=akreditasi') ?>" class="pill <?= ($data['current_role'] ?? '') === 'akreditasi' ? 'green' : 'gray' ?>" style="padding:6px 12px;font-size:11.5px;text-decoration:none">Tim Akreditasi</a>
            </div>

            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            <?php if (!empty($data['q']) || !empty($data['current_role'])): ?>
                <a href="<?= base_url('admin/pengguna') ?>" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>

        <div class="right">
            <span class="pill plain gray"><?= count($data['users']) ?> pengguna</span>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Nama &amp; Akun</th>
                    <th>Email &amp; Identitas</th>
                    <th>Role</th>
                    <th>Keterhubungan Profil App</th>
                    <th>Status</th>
                    <th>Terakhir Login</th>
                    <th style="width:130px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['users'])): ?>
                <tr>
                    <td colspan="7" style="padding:36px 16px;text-align:center;color:#6B7280">
                        <b>Tidak ada data pengguna yang sesuai kriteria.</b>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($data['users'] as $u): ?>
                    <tr>
                        <td>
                            <div class="cell-flex">
                                <div class="avatar"><?= htmlspecialchars(strtoupper(mb_substr($u['name'] ?? 'U', 0, 2)), ENT_QUOTES, 'UTF-8') ?></div>
                                <div>
                                    <div class="t-title"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="t-sub"><?= htmlspecialchars($u['username'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if ($u['role'] === 'guru' && !empty($u['nip'])): ?>
                                <div class="t-sub">NIP: <?= htmlspecialchars($u['nip'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php elseif ($u['role'] === 'siswa' && !empty($u['nis'])): ?>
                                <div class="t-sub">NIS: <?= htmlspecialchars($u['nis'], ENT_QUOTES, 'UTF-8') ?> &middot; Kelas: <?= htmlspecialchars($u['class_name'] ?? 'Belum ada kelas', ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $roleBadge = match ($u['role']) {
                                'admin' => 'red',
                                'operator' => 'amber',
                                'kepala' => 'green',
                                'guru' => 'blue',
                                'siswa' => 'purple',
                                'akreditasi' => 'mint',
                                default => 'gray',
                            };
                            ?>
                            <span class="pill <?= $roleBadge ?>"><?= ucfirst(htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8')) ?></span>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'guru'): ?>
                                <?php if (!empty($u['teacher_id'])): ?>
                                    <span class="pill green" title="Data guru tersambung">✓ Guru Terhubung</span>
                                <?php else: ?>
                                    <span class="pill red" title="Belum memiliki data guru">⚠ Belum Terhubung</span>
                                <?php endif; ?>
                            <?php elseif ($u['role'] === 'siswa'): ?>
                                <?php if (!empty($u['student_id'])): ?>
                                    <span class="pill green" title="Data siswa tersambung">✓ Siswa (<?= htmlspecialchars($u['class_name'] ?? 'Tanpa Kelas', ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php else: ?>
                                    <span class="pill red" title="Belum memiliki data siswa">⚠ Belum Terhubung</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="pill plain gray">Staf Madrasah</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $u['is_active'] === 1): ?>
                                <span class="pill green">Aktif</span>
                            <?php else: ?>
                                <span class="pill red">Nonaktif</span>
                            <?php endif; ?>
                            <?php if ((int) ($u['must_change_password'] ?? 0) === 1): ?>
                                <span class="pill amber plain" title="Wajib ganti sandi saat login perdana">Sandi Awal</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= $u['last_login_at'] ? date('d M Y, H.i', strtotime($u['last_login_at'])) : 'Belum pernah' ?></td>
                        <td>
                            <div class="row-actions">
                                <form method="post" action="<?= base_url('admin/pengguna/reset-password/' . $u['id']) ?>" onsubmit="return confirm('Reset kata sandi untuk <?= htmlspecialchars(addslashes($u['name']), ENT_QUOTES, 'UTF-8') ?>?');" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="icon-btn" title="Reset Kata Sandi">
                                        <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                    </button>
                                </form>

                                <form method="post" action="<?= base_url('admin/pengguna/toggle-status/' . $u['id']) ?>" onsubmit="return confirm('Ubah status aktif/nonaktif akun ini?');" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="icon-btn" title="Aktif / Nonaktifkan">
                                        <svg viewBox="0 0 24 24"><path d="M18.4 5.6a9 9 0 1 1-12.8 12.8"/><path d="M3 3l18 18"/></svg>
                                    </button>
                                </form>

                                <form method="post" action="<?= base_url('admin/pengguna/delete/' . $u['id']) ?>" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars(addslashes($u['name']), ENT_QUOTES, 'UTF-8') ?>?');" style="display:inline">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="icon-btn" title="Hapus" style="color:#DC2626">
                                        <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div id="modalTambahPengguna" class="modal-overlay" style="display:none">
    <div class="modal-mock" style="max-width:540px">
        <div class="modal-head">
            <h3>Tambah Pengguna &amp; Akun Mobile</h3>
            <button type="button" class="icon-btn" onclick="document.getElementById('modalTambahPengguna').style.display='none'">&times;</button>
        </div>
        <form method="post" action="<?= base_url('admin/pengguna/store') ?>">
            <?= Csrf::field() ?>
            <div class="modal-body">
                <div class="field">
                    <label for="modal_name">Nama Lengkap <span class="req">*</span></label>
                    <input type="text" id="modal_name" name="name" required placeholder="Contoh: Ahmad Fauzi, S.Pd.">
                </div>

                <div class="grid grid-2">
                    <div class="field">
                        <label for="modal_email">Alamat Email <span class="req">*</span></label>
                        <input type="email" id="modal_email" name="email" required placeholder="nama@mtsalhuda.sch.id">
                    </div>

                    <div class="field">
                        <label for="modal_username">Username (opsional)</label>
                        <input type="text" id="modal_username" name="username" placeholder="Default dari email">
                    </div>
                </div>

                <div class="field">
                    <label for="modal_role">Peran / Role Pengguna <span class="req">*</span></label>
                    <select id="modal_role" name="role" onchange="updateRoleExtraFields(this.value)">
                        <option value="guru" selected>Guru (Aplikasi &amp; Web)</option>
                        <option value="siswa">Siswa (Aplikasi MADRASAHKU)</option>
                        <option value="operator">Operator Madrasah (Web Admin)</option>
                        <option value="kepala">Kepala Madrasah (Web Admin)</option>
                        <option value="akreditasi">Tim Akreditasi IA2024</option>
                        <option value="admin">Administrator (Super Admin)</option>
                    </select>
                </div>

                <!-- Bagian Khusus Guru -->
                <div id="guruExtraField">
                    <div class="grid grid-2">
                        <div class="field">
                            <label for="modal_nip">NIP Guru</label>
                            <input type="text" id="modal_nip" name="nip" placeholder="Contoh: 19850101 201001 1 004">
                        </div>
                        <div class="field">
                            <label for="modal_nuptk">NUPTK Guru</label>
                            <input type="text" id="modal_nuptk" name="nuptk" placeholder="Nomor NUPTK">
                        </div>
                    </div>
                    <div class="field">
                        <label for="modal_specialty">Spesialisasi Mata Pelajaran</label>
                        <input type="text" id="modal_specialty" name="subject_specialty" placeholder="Contoh: Matematika, Fikih, IPA">
                    </div>
                </div>

                <!-- Bagian Khusus Siswa -->
                <div id="siswaExtraField" style="display:none">
                    <div class="grid grid-2">
                        <div class="field">
                            <label for="modal_nis">Nomor Induk Siswa (NIS)</label>
                            <input type="text" id="modal_nis" name="nis" placeholder="Otomatis jika dikosongkan">
                        </div>
                        <div class="field">
                            <label for="modal_nisn">NISN Siswa</label>
                            <input type="text" id="modal_nisn" name="nisn" placeholder="Nomor NISN">
                        </div>
                    </div>
                    <div class="grid grid-2">
                        <div class="field">
                            <label for="modal_class_id">Rombongan Belajar / Kelas</label>
                            <select id="modal_class_id" name="class_id">
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?> (Tingkat <?= (int) $c['level'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="modal_gender">Jenis Kelamin</label>
                            <select id="modal_gender" name="gender">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="modal_password">Kata Sandi Awal</label>
                    <input type="text" id="modal_password" name="password" value="Contoh#AlHuda2026">
                    <div class="hint">Pengguna akan diwajibkan mengganti kata sandi saat pertama kali masuk ke aplikasi.</div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('modalTambahPengguna').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateRoleExtraFields(role) {
    var guruDiv = document.getElementById('guruExtraField');
    var siswaDiv = document.getElementById('siswaExtraField');
    if (role === 'guru') {
        guruDiv.style.display = 'block';
        siswaDiv.style.display = 'none';
    } else if (role === 'siswa') {
        guruDiv.style.display = 'none';
        siswaDiv.style.display = 'block';
    } else {
        guruDiv.style.display = 'none';
        siswaDiv.style.display = 'none';
    }
}
</script>

<?php
$data['content'] = ob_get_clean();
require_once __DIR__ . '/../../layouts/admin.php';
