<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/navbar.php';

$perHalaman = 9;

// ---------- DATA ----------
// Struktur sama dengan admin Kelola Galeri. Controller cukup mengirim:
//   $data['items']  => tiap baris: id, title, image_path, album, caption, sort_order, is_active
//   $data['albums'] => daftar nama album (opsional, kalau kosong diambil dari items)
// Kalau controller belum mengirim $data['items'], dipakai DUMMY di bawah ini.
if (!isset($data['items'])) {
    $data = [
        'albums' => ['Akademik & Literasi', 'Ekstrakurikuler Pramuka', 'Kesehatan & Olahraga', 'Kesenian & Prestasi', 'Paguyuban & Komite', 'Seni & Religi'],
        'items'  => [
            ['id' => 1, 'title' => 'Aktivitas Belajar dan Memperkaya Khazanah Ilmu',            'image_path' => 'assets/images/Belajar di Perpustakaan.jpg',                    'album' => 'Akademik & Literasi',     'caption' => 'Santri tekun mengkaji buku referensi di perpustakaan madrasah.',          'sort_order' => 1, 'is_active' => 1],
            ['id' => 2, 'title' => 'Penampilan Seni Hadrah Santri MTs Al-Huda',                 'image_path' => 'assets/images/Grup Sholawat  MTS Al-Huda.jpg',                 'album' => 'Seni & Religi',           'caption' => 'Grup hadrah santri membawakan sholawat dalam acara madrasah.',            'sort_order' => 2, 'is_active' => 1],
            ['id' => 3, 'title' => 'Upacara Pembukaan Jambore Cabang Kontingen Regu Penggalang', 'image_path' => 'assets/images/Mengikuti Upacara Pembukaan Jambore Cabang.jpg', 'album' => 'Ekstrakurikuler Pramuka', 'caption' => 'Kontingen Pramuka MTs Al-Huda mengikuti upacara pembukaan jambore cabang.', 'sort_order' => 3, 'is_active' => 1],
            ['id' => 4, 'title' => 'Semarak Senam Bersama Dewan Asatidz dan Seluruh Santri',     'image_path' => 'assets/images/Senam Bersama.jpg',                              'album' => 'Kesehatan & Olahraga',    'caption' => 'Senam kebugaran jasmani bersama guru dan seluruh santri.',                'sort_order' => 4, 'is_active' => 1],
            ['id' => 5, 'title' => 'Rapat Pleno Silaturahmi Paguyuban Wali Santri MTs Al-Huda',  'image_path' => 'assets/images/Pertemuan Wali Santri.jpg',                      'album' => 'Paguyuban & Komite',      'caption' => 'Sinergi hangat antara pimpinan madrasah dan wali santri.',                'sort_order' => 5, 'is_active' => 1],
            ['id' => 6, 'title' => 'Harmoni Nada & Formasi Korps Drumband Gita Al-Huda',        'image_path' => 'assets/images/Drumband.jpg',                                   'album' => 'Kesenian & Prestasi',     'caption' => 'Unit korps drumband MTs Al-Huda tampil dengan formasi yang kompak.',      'sort_order' => 6, 'is_active' => 1],
        ],
    ];
}

// Hanya foto aktif, urut berdasarkan sort_order
$items = array_values(array_filter($data['items'], fn($it) => (int) ($it['is_active'] ?? 0) === 1));
usort($items, fn($a, $b) => (int) $a['sort_order'] <=> (int) $b['sort_order']);

// Foto tanpa album ditampilkan sebagai "Umum" (sama seperti di admin)
foreach ($items as &$it) { if (empty($it['album'])) $it['album'] = 'Umum'; }
unset($it);

// Daftar tombol filter: album dari controller (urutan sama dengan dropdown admin), hanya yang punya foto
$jumlah = array_count_values(array_column($items, 'album'));
$daftarAlbum = $data['albums'] ?? array_keys($jumlah);
if (isset($jumlah['Umum']) && !in_array('Umum', $daftarAlbum, true)) $daftarAlbum[] = 'Umum';
$daftarAlbum = array_values(array_filter($daftarAlbum, fn($a) => !empty($jumlah[$a])));

function gx_h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
?>

<style>
.gx-section{background:#e9f9ef}
.gx-filter{display:flex;flex-wrap:wrap;justify-content:flex-start;gap:10px;margin-bottom:28px}
.gx-chip{display:inline-flex;align-items:center;justify-content:center;gap:6px;flex:0 0 auto;width:auto;height:36px;padding:0 14px;border-radius:999px;border:0;background:#fff;color:#2b3a31;font:inherit;font-size:13px;line-height:1;white-space:nowrap;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.08);transition:box-shadow .15s}
.gx-chip:hover{box-shadow:0 3px 10px rgba(0,0,0,.14)}
.gx-chip.active{background:#14532d;color:#fff;font-weight:600}
.gx-chip .gx-n{display:none;min-width:20px;height:20px;padding:0 6px;border-radius:999px;background:#fff;color:#166534;font-size:12px;font-weight:700;align-items:center;justify-content:center}
.gx-chip.active .gx-n{display:inline-flex}
.gx-chip:focus-visible,.gx-card:focus-visible,.gx-pg button:focus-visible{outline:2px solid #0ea5e9;outline-offset:2px}

.gx-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
@media (max-width:900px){.gx-grid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:560px){.gx-grid{grid-template-columns:1fr}}

.gx-card{position:relative;display:block;width:100%;padding:0;border:0;border-radius:16px;overflow:hidden;aspect-ratio:4/3;background:#cfe9d8;cursor:zoom-in;text-align:left;font:inherit;color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.12)}
.gx-card.is-hidden{display:none}
.gx-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .35s}
.gx-card:hover img{transform:scale(1.05)}
.gx-card::after{content:"";position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.82) 0%,rgba(0,0,0,.45) 38%,rgba(0,0,0,0) 65%)}
.gx-badge{position:absolute;top:12px;left:12px;z-index:2;background:#fff;color:#166534;font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.gx-info{position:absolute;left:0;right:0;bottom:0;z-index:2;padding:14px 16px}
.gx-title{font-size:15px;font-weight:700;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:3px}
.gx-cap{font-size:12px;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

.gx-empty{display:none;text-align:center;padding:48px 0;color:#4b6b57}
.gx-foot{display:flex;flex-wrap:wrap;gap:14px;align-items:center;justify-content:space-between;margin-top:32px;font-size:13px;color:#3f5a49}
.gx-pg{display:flex;align-items:center;gap:8px}
.gx-pg button{min-width:38px;height:38px;padding:0 12px;border:0;border-radius:10px;background:#fff;color:#2b3a31;font:inherit;font-size:14px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.gx-pg button:hover:not(:disabled):not(.active){box-shadow:0 3px 10px rgba(0,0,0,.14)}
.gx-pg button.active{background:#166534;color:#fff;font-weight:700}
.gx-pg button:disabled{color:#9aab9f;cursor:default;box-shadow:none;background:transparent}
.gx-pg .gx-dots{color:#6b7a70;padding:0 2px}

.gx-dialog{border:0;border-radius:16px;padding:0;max-width:820px;width:94%;box-shadow:0 20px 50px rgba(0,0,0,.35);overflow:hidden}
.gx-dialog::backdrop{background:rgba(0,0,0,.65)}
.gx-dialog img{display:block;width:100%;max-height:68vh;object-fit:contain;background:#111}
.gx-meta{padding:16px 20px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.gx-meta .gx-tag{display:inline-block;background:#dcfce7;color:#166534;font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px}
.gx-meta h3{font-size:17px;line-height:1.35;margin:8px 0 4px}
.gx-meta p{font-size:14px;color:#5d6d63;line-height:1.5}
.gx-close{padding:8px 16px;border-radius:8px;border:1px solid #d9e2dc;background:#fff;font:inherit;font-size:13px;cursor:pointer;flex-shrink:0}
</style>

<main>
    <!-- Hero: disamakan dengan halaman Berita -->
    <section class="page-hero" style="background: linear-gradient(135deg, #0B3D1E 0%, #15803D 100%); color: #fff; padding: 48px 0 40px;">
        <div class="container">
            <div class="section-label" style="color: #4ADE80; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 11.5px; margin-bottom: 8px;">
                Galeri Foto &amp; Dokumentasi
            </div>
            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin-bottom: 12px;">Momen &amp; <span style="color: #4ADE80;">Kegiatan</span> Madrasah</h1>
            <p style="color: rgba(255,255,255,.85); max-width: 640px; font-size: 14.5px; text-wrap: balance;">
                Dokumentasi berbagai kegiatan belajar, ibadah, dan pengembangan diri siswa MTs Al-Huda Gondang.
            </p>
        </div>
    </section>

    <section class="page-section gx-section">
        <div class="container">

            <div class="gx-filter" role="group" aria-label="Filter album galeri">
                <button type="button" class="gx-chip active" data-filter="semua">Semua <span class="gx-n" id="gxBadge"><?= count($items) ?></span></button>
                <?php foreach ($daftarAlbum as $alb): ?>
                    <button type="button" class="gx-chip" data-filter="<?= gx_h($alb) ?>"><?= gx_h($alb) ?> <span class="gx-n"></span></button>
                <?php endforeach; ?>
            </div>

            <div class="gx-grid" id="gxGrid">
                <?php foreach ($items as $it):
                    $src = base_url($it['image_path']);
                ?>
                    <button type="button" class="gx-card" data-album="<?= gx_h($it['album']) ?>"
                            data-src="<?= gx_h($src) ?>"
                            data-judul="<?= gx_h($it['title']) ?>"
                            data-caption="<?= gx_h($it['caption'] ?? '') ?>"
                            data-label="<?= gx_h($it['album']) ?>"
                            aria-label="Perbesar foto: <?= gx_h($it['title']) ?>">
                        <img src="<?= gx_h($src) ?>" alt="<?= gx_h($it['title']) ?>" loading="lazy">
                        <span class="gx-badge"><?= gx_h($it['album']) ?></span>
                        <span class="gx-info">
                            <span class="gx-title" style="display:-webkit-box"><?= gx_h($it['title']) ?></span>
                            <?php if (!empty($it['caption'])): ?>
                                <span class="gx-cap" style="display:block"><?= gx_h($it['caption']) ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <p class="gx-empty" id="gxEmpty">Belum ada foto pada album ini.</p>

            <div class="gx-foot">
                <span id="gxInfo"></span>
                <nav class="gx-pg" id="gxPg" aria-label="Halaman galeri"></nav>
            </div>
        </div>
    </section>
</main>

<dialog class="gx-dialog" id="gxDialog">
    <img id="gxImg" alt="">
    <div class="gx-meta">
        <div>
            <span class="gx-tag" id="gxTag"></span>
            <h3 id="gxTitle"></h3>
            <p id="gxCap"></p>
        </div>
        <button type="button" class="gx-close" id="gxClose">Tutup</button>
    </div>
</dialog>

<script>
(function () {
    const PER = <?= (int) $perHalaman ?>;
    const cards = [...document.querySelectorAll('.gx-card')];
    const chips = [...document.querySelectorAll('.gx-chip')];
    const info  = document.getElementById('gxInfo');
    const pg    = document.getElementById('gxPg');
    const empty = document.getElementById('gxEmpty');
    const grid  = document.getElementById('gxGrid');
    let album = 'semua', page = 1;

    function pageList(cur, total) {
        if (total <= 7) return [...Array(total)].map((_, i) => i + 1);
        const s = new Set([1, total, cur - 1, cur, cur + 1].filter(n => n >= 1 && n <= total));
        const arr = [...s].sort((a, b) => a - b), out = [];
        arr.forEach((n, i) => { if (i && n - arr[i - 1] > 1) out.push('…'); out.push(n); });
        return out;
    }

    function render(scroll) {
        const list  = cards.filter(c => album === 'semua' || c.dataset.album === album);
        const total = list.length;
        const pages = Math.max(1, Math.ceil(total / PER));
        page = Math.min(page, pages);
        const start = (page - 1) * PER, end = Math.min(start + PER, total);

        cards.forEach(c => c.classList.add('is-hidden'));
        list.slice(start, end).forEach(c => c.classList.remove('is-hidden'));

        chips.forEach(ch => {
            const on = ch.dataset.filter === album;
            ch.classList.toggle('active', on);
            const n = ch.querySelector('.gx-n');
            if (on) n.textContent = total;
        });

        empty.style.display = total ? 'none' : 'block';
        info.textContent = total ? 'Menampilkan ' + (start + 1) + '–' + end + ' dari ' + total + ' dokumentasi' : '';

        pg.innerHTML = '';
        pg.style.display = total ? 'flex' : 'none';
        const mk = (txt, p, opts = {}) => {
            const b = document.createElement('button');
            b.type = 'button'; b.textContent = txt;
            if (opts.disabled) b.disabled = true;
            if (opts.active) { b.classList.add('active'); b.setAttribute('aria-current', 'page'); }
            b.addEventListener('click', () => { page = p; render(true); });
            pg.appendChild(b);
        };
        mk('‹ Sebelumnya', page - 1, { disabled: page === 1 });
        pageList(page, pages).forEach(n => {
            if (n === '…') { const s = document.createElement('span'); s.className = 'gx-dots'; s.textContent = '…'; pg.appendChild(s); }
            else mk(n, n, { active: n === page });
        });
        mk('Selanjutnya ›', page + 1, { disabled: page === pages });

        if (scroll) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    chips.forEach(ch => ch.addEventListener('click', () => { album = ch.dataset.filter; page = 1; render(false); }));
    render(false);

    // Lightbox
    const dlg = document.getElementById('gxDialog');
    cards.forEach(c => c.addEventListener('click', () => {
        const img = document.getElementById('gxImg');
        img.src = c.dataset.src; img.alt = c.dataset.judul;
        document.getElementById('gxTag').textContent   = c.dataset.label;
        document.getElementById('gxTitle').textContent = c.dataset.judul;
        document.getElementById('gxCap').textContent   = c.dataset.caption;
        dlg.showModal();
    }));
    document.getElementById('gxClose').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>