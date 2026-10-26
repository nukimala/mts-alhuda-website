<?php
/**
 * Formulir berita — dipakai untuk menulis baru dan menyunting.
 *
 * Mengikuti layar Stitch "Edit Berita": kolom isi di kiri, kartu Publikasi /
 * Gambar Cover / SEO di kanan.
 *
 * Editor teks di sini textarea biasa, bukan editor WYSIWYG. Alasannya:
 * toolbar WYSIWYG tanpa pustaka pihak ketiga hanya bisa memakai
 * contenteditable, dan contenteditable menghasilkan HTML yang tidak konsisten
 * antar browser — termasuk membuang tag saat ditempel dari Word. Textarea
 * dengan HTML langsung lebih jujur: apa yang diketik itulah yang disimpan.
 * Tombol bantuan format di bawah tetap membantu penulis yang belum biasa.
 */

$post = $data['post'] ?? null;
$old = $data['old'] ?? [];
$errors = $data['errors'] ?? [];
$canWrite = (bool) ($data['canWrite'] ?? true);

$isEdit = $post !== null;
$id = $isEdit ? (int) $post['id'] : 0;

$action = $isEdit
    ? base_url('admin/berita/' . $id)
    : base_url('admin/berita');

$coverPath = (string) ($old['cover_path'] ?? '');
$hasCover = $coverPath !== '' && Media::exists($coverPath);

$siteHost = (string) ($_SERVER['HTTP_HOST'] ?? 'mts-alhuda.shulkhimaulana.my.id');

$field = static function (string $key) use ($errors): string {
    return isset($errors[$key]) ? ' has-error' : '';
};

ob_start();
?>

<div class="breadcrumb">
    <a href="<?= base_url('admin') ?>">Panel Admin</a>
    <span class="sep">/</span>
    <a href="<?= base_url('admin/berita') ?>">Berita</a>
    <span class="sep">/</span>
    <span class="current"><?= $isEdit ? 'Sunting' : 'Tulis Baru' ?></span>
</div>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" id="post-form">
    <?= Csrf::field() ?>

    <div class="page-head">
        <div>
            <h1><?= $isEdit ? 'Sunting Berita' : 'Tulis Berita Baru' ?></h1>
            <p>
                <?php if ($isEdit): ?>
                    Terakhir diubah <?= e(Format::dateTime((string) ($post['updated_at'] ?? ''))) ?>
                    oleh <?= e((string) ($post['author_name'] ?? 'penulis')) ?>
                <?php else: ?>
                    Berita akan tampil di website setelah statusnya Terbit
                <?php endif; ?>
            </p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="<?= base_url('admin/berita') ?>">Batal</a>
            <?php if ($canWrite): ?>
                <button class="btn btn-primary" type="submit">
                    <span class="mi mi-sm">save</span>
                    <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Berita' ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="notice notice-danger">
            <span class="mi mi-sm">error</span>
            <span>
                <strong>Ada <?= count($errors) ?> bagian yang perlu diperbaiki.</strong>
                Lihat tanda merah di bawah. Isian Anda tidak hilang.
            </span>
        </div>
    <?php endif; ?>

    <?php if ($isEdit && ($old['status'] ?? '') === 'published'): ?>
        <div class="notice notice-info">
            <span class="mi mi-sm">visibility</span>
            <span>
                Berita ini sedang <strong>tayang</strong> di website.
                <a href="<?= base_url('berita/' . $post['slug']) ?>" target="_blank" rel="noopener">Lihat halaman publiknya</a>.
            </span>
        </div>
    <?php endif; ?>

    <div class="form-grid">
        <!-- Kolom kiri: isi berita -->
        <div class="form-grid-main">
            <div class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Isi Berita</h2>
                        <p class="card-sub">Bahasa Indonesia</p>
                    </div>
                    <span class="mi" style="color:var(--green)">article</span>
                </div>

                <div class="field<?= $field('title') ?>">
                    <div class="field-label-row">
                        <label for="title">Judul Berita <span class="req">*</span></label>
                        <span class="field-counter" data-counter-for="title" data-max="120">0 / 120</span>
                    </div>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?= e((string) ($old['title'] ?? '')) ?>"
                        maxlength="200"
                        data-max="120"
                        placeholder="Contoh: Santri MTs Al-Huda Raih Juara 1 Olimpiade Matematika"
                        <?= $canWrite ? '' : 'readonly' ?>
                        required>
                    <?php if (isset($errors['title'])): ?>
                        <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['title']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">Judul yang jelas membantu orang menemukan berita ini di mesin pencari.</p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $field('slug') ?>">
                    <label for="slug">Slug URL</label>
                    <div class="slug-input">
                        <span class="slug-prefix">/berita/</span>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            value="<?= e((string) ($old['slug'] ?? '')) ?>"
                            placeholder="dibuat-otomatis-dari-judul"
                            <?= $canWrite ? '' : 'readonly' ?>>
                    </div>
                    <?php if (isset($errors['slug'])): ?>
                        <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['slug']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">
                            Bagian akhir alamat halaman. Kosongkan untuk dibuat otomatis dari judul.
                            <?php if ($isEdit): ?>
                                <br>Mengubah slug akan mengubah alamat berita — tautan lama menjadi tidak berlaku.
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $field('excerpt') ?>">
                    <div class="field-label-row">
                        <label for="excerpt">Ringkasan Berita</label>
                        <span class="field-counter" data-counter-for="excerpt" data-max="400">0 / 400</span>
                    </div>
                    <textarea
                        id="excerpt"
                        name="excerpt"
                        rows="3"
                        maxlength="400"
                        placeholder="Satu atau dua kalimat yang merangkum isi berita. Tampil di daftar berita dan beranda."
                        <?= $canWrite ? '' : 'readonly' ?>><?= e((string) ($old['excerpt'] ?? '')) ?></textarea>
                    <?php if (isset($errors['excerpt'])): ?>
                        <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['excerpt']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $field('content') ?>">
                    <div class="field-label-row">
                        <label for="content">Isi Berita <span class="req">*</span></label>
                        <span class="field-hint" style="margin:0">
                            <span class="mi mi-sm">code</span> HTML didukung
                        </span>
                    </div>

                    <div class="editor-toolbar" data-editor-toolbar>
                        <button type="button" data-wrap="strong" title="Tebal"><b>B</b></button>
                        <button type="button" data-wrap="em" title="Miring"><i>I</i></button>
                        <button type="button" data-wrap="u" title="Garis bawah"><u>U</u></button>
                        <span class="toolbar-sep"></span>
                        <button type="button" data-block="h2" title="Sub-judul">H2</button>
                        <button type="button" data-block="h3" title="Sub-sub-judul">H3</button>
                        <button type="button" data-wrap="blockquote" title="Kutipan">
                            <span class="mi mi-sm">format_quote</span>
                        </button>
                        <button type="button" data-block="ul" title="Daftar poin">
                            <span class="mi mi-sm">format_list_bulleted</span>
                        </button>
                        <button type="button" data-block="ol" title="Daftar nomor">
                            <span class="mi mi-sm">format_list_numbered</span>
                        </button>
                        <span class="toolbar-sep"></span>
                        <button type="button" data-insert="<p></p>" title="Paragraf baru">¶</button>
                        <button type="button" data-insert="<a href=&quot;https://&quot;>teks tautan</a>" title="Tautan">
                            <span class="mi mi-sm">link</span>
                        </button>
                    </div>

                    <textarea
                        id="content"
                        name="content"
                        class="editor-area"
                        rows="16"
                        placeholder="&lt;p&gt;Tulis isi berita di sini...&lt;/p&gt;"
                        <?= $canWrite ? '' : 'readonly' ?>><?= e((string) ($old['content'] ?? '')) ?></textarea>

                    <div class="editor-foot">
                        <span data-word-count>0 kata</span>
                        <span>HTML siap tayang</span>
                    </div>

                    <?php if (isset($errors['content'])): ?>
                        <p class="field-error"><span class="mi mi-sm">info</span> <?= e($errors['content']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: publikasi, cover, SEO -->
        <div class="form-grid-side">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Publikasi</h2>
                    <span class="mi" style="color:var(--green)">schedule_send</span>
                </div>

                <div class="field<?= $field('status') ?>">
                    <label for="status">Status Penerbitan</label>
                    <select id="status" name="status" <?= $canWrite ? '' : 'disabled' ?>>
                        <?php foreach (Post::STATUSES as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= ($old['status'] ?? 'draft') === $key ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['status'])): ?>
                        <p class="field-error"><?= e($errors['status']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">Draft tidak terlihat pengunjung. Terbit langsung tayang.</p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $field('published_at') ?>">
                    <label for="published_at">Jadwal / Tanggal Terbit</label>
                    <input
                        type="datetime-local"
                        id="published_at"
                        name="published_at"
                        value="<?= e((string) ($old['published_at'] ?? '')) ?>"
                        <?= $canWrite ? '' : 'readonly' ?>>
                    <?php if (isset($errors['published_at'])): ?>
                        <p class="field-error"><?= e($errors['published_at']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">
                            Kosongkan untuk memakai waktu sekarang saat berstatus Terbit.
                            Tanggal di masa depan pada status Draft berfungsi sebagai jadwal.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="field<?= $field('category') ?>">
                    <label for="category">Kategori Konten</label>
                    <select id="category" name="category" <?= $canWrite ? '' : 'disabled' ?>>
                        <?php foreach (Post::CATEGORIES as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= ($old['category'] ?? 'umum') === $key ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="check-row" style="margin-top:var(--sp-3)">
                    <input
                        type="checkbox"
                        id="is_featured"
                        name="is_featured"
                        value="1"
                        <?= ($old['is_featured'] ?? '0') === '1' ? 'checked' : '' ?>
                        <?= $canWrite ? '' : 'disabled' ?>
                        style="width:16px;height:16px">
                    <label for="is_featured">Tampilkan sebagai berita utama di beranda</label>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Gambar Cover</h2>
                    <span class="card-sub">Rasio 16:9</span>
                </div>

                <?php if ($hasCover): ?>
                    <div class="cover-preview">
                        <img src="<?= base_url($coverPath) ?>" alt="Cover berita saat ini">
                    </div>
                    <?php if ($canWrite): ?>
                        <div class="check-row" style="margin-top:var(--sp-2)">
                            <input type="checkbox" id="remove_cover" name="remove_cover" value="1"
                                   style="width:16px;height:16px">
                            <label for="remove_cover">Hapus gambar cover ini</label>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($canWrite): ?>
                    <label class="dropzone" for="cover" style="margin-top:var(--sp-2)">
                        <span class="mi mi-lg" style="color:var(--green)">add_photo_alternate</span>
                        <span class="dropzone-title" data-cover-name>
                            <?= $hasCover ? 'Ganti gambar cover' : 'Pilih gambar cover' ?>
                        </span>
                        <span class="dropzone-hint">JPG, PNG, atau WEBP &middot; maksimal 3 MB</span>
                        <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
                    </label>
                <?php endif; ?>

                <?php if (isset($errors['cover'])): ?>
                    <p class="field-error"><?= e($errors['cover']) ?></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Optimasi SEO</h2>
                    <span class="mi" style="color:var(--green)">manage_search</span>
                </div>

                <div class="field">
                    <div class="field-label-row">
                        <label for="meta_title">Meta Title</label>
                        <span class="field-counter" data-counter-for="meta_title" data-max="70">0 / 70</span>
                    </div>
                    <input
                        type="text"
                        id="meta_title"
                        name="meta_title"
                        value="<?= e((string) ($old['meta_title'] ?? '')) ?>"
                        maxlength="200"
                        placeholder="Kosongkan untuk memakai judul berita"
                        <?= $canWrite ? '' : 'readonly' ?>>
                </div>

                <div class="field">
                    <div class="field-label-row">
                        <label for="meta_description">Meta Description</label>
                        <span class="field-counter" data-counter-for="meta_description" data-max="160">0 / 160</span>
                    </div>
                    <textarea
                        id="meta_description"
                        name="meta_description"
                        rows="3"
                        maxlength="320"
                        placeholder="Kosongkan untuk memakai ringkasan berita"
                        <?= $canWrite ? '' : 'readonly' ?>><?= e((string) ($old['meta_description'] ?? '')) ?></textarea>
                </div>

                <div class="seo-preview">
                    <span class="seo-preview-label">Pratinjau hasil pencarian</span>
                    <div class="seo-preview-url mono"><?= e($siteHost) ?> › berita › <span data-seo-slug><?= e((string) ($old['slug'] ?? 'slug-berita')) ?></span></div>
                    <div class="seo-preview-title" data-seo-title><?= e((string) ($old['meta_title'] ?? '') !== '' ? (string) $old['meta_title'] : (string) ($old['title'] ?? 'Judul berita')) ?></div>
                    <p class="seo-preview-desc" data-seo-desc><?= e(Format::excerpt((string) (($old['meta_description'] ?? '') !== '' ? $old['meta_description'] : ($old['excerpt'] ?? '')), 160)) ?></p>
                </div>
            </div>

            <?php if ($canWrite): ?>
                <div class="card card-flat">
                    <div class="form-actions" style="padding-top:0;margin-top:0;border-top:0">
                        <button class="btn btn-primary btn-block" type="submit">
                            <span class="mi mi-sm">save</span>
                            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Berita' ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('post-form');
        if (!form) {
            return;
        }

        var title = document.getElementById('title');
        var slug = document.getElementById('slug');
        var content = document.getElementById('content');
        var excerpt = document.getElementById('excerpt');
        var metaTitle = document.getElementById('meta_title');
        var metaDesc = document.getElementById('meta_description');

        // --- penghitung karakter -------------------------------------------
        function bindCounter(id, max) {
            var input = document.getElementById(id);
            var output = document.querySelector('[data-counter-for="' + id + '"]');
            if (!input || !output) {
                return;
            }

            function render() {
                var length = input.value.length;
                output.textContent = length + ' / ' + max;
                output.classList.toggle('is-over', length > max);
            }

            input.addEventListener('input', render);
            render();
        }

        bindCounter('title', 120);
        bindCounter('excerpt', 400);
        bindCounter('meta_title', 70);
        bindCounter('meta_description', 160);

        // --- hitung kata ----------------------------------------------------
        var wordCount = document.querySelector('[data-word-count]');
        if (content && wordCount) {
            var countWords = function () {
                var text = content.value.replace(/<[^>]*>/g, ' ').trim();
                var words = text === '' ? 0 : text.split(/\s+/).length;
                wordCount.textContent = words.toLocaleString('id-ID') + ' kata';
            };
            content.addEventListener('input', countWords);
            countWords();
        }

        // --- pratinjau SEO --------------------------------------------------
        var seoTitle = document.querySelector('[data-seo-title]');
        var seoDesc = document.querySelector('[data-seo-desc]');
        var seoSlug = document.querySelector('[data-seo-slug]');

        function syncSeo() {
            if (seoTitle) {
                var value = (metaTitle && metaTitle.value.trim()) || (title && title.value.trim()) || 'Judul berita';
                seoTitle.textContent = value;
            }

            if (seoDesc) {
                var desc = (metaDesc && metaDesc.value.trim()) || (excerpt && excerpt.value.trim()) || '';
                seoDesc.textContent = desc.length > 160 ? desc.slice(0, 157) + '…' : desc;
            }

            if (seoSlug) {
                var slugValue = (slug && slug.value.trim()) || 'slug-berita';
                seoSlug.textContent = slugValue;
            }
        }

        [title, excerpt, metaTitle, metaDesc, slug].forEach(function (el) {
            if (el) {
                el.addEventListener('input', syncSeo);
            }
        });
        syncSeo();

        // --- slug otomatis dari judul --------------------------------------
        if (title && slug) {
            var slugTouched = slug.value.trim() !== '';

            slug.addEventListener('input', function () {
                slugTouched = true;
                syncSeo();
            });

            title.addEventListener('input', function () {
                if (slugTouched) {
                    return;
                }
                slug.value = title.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '')
                    .slice(0, 180);
                syncSeo();
            });
        }

        // --- tombol bantuan format -----------------------------------------
        function insertAtCursor(field, before, after, placeholder) {
            var start = field.selectionStart;
            var end = field.selectionEnd;
            var selected = field.value.slice(start, end) || placeholder || '';

            field.value = field.value.slice(0, start) + before + selected + after + field.value.slice(end);

            var caret = start + before.length;
            field.focus();
            field.setSelectionRange(caret, caret + selected.length);
            field.dispatchEvent(new Event('input'));
        }

        document.querySelectorAll('[data-editor-toolbar] [data-wrap]').forEach(function (button) {
            button.addEventListener('click', function () {
                var tag = button.getAttribute('data-wrap');
                insertAtCursor(content, '<' + tag + '>', '</' + tag + '>', 'teks');
            });
        });

        document.querySelectorAll('[data-editor-toolbar] [data-block]').forEach(function (button) {
            button.addEventListener('click', function () {
                var tag = button.getAttribute('data-block');
                var map = {
                    h2: ['<h2>', '</h2>', 'Sub-judul'],
                    h3: ['<h3>', '</h3>', 'Sub-sub-judul'],
                    ul: ['<ul>\n    <li>', '</li>\n</ul>', 'Poin pertama'],
                    ol: ['<ol>\n    <li>', '</li>\n</ol>', 'Poin pertama']
                };
                var parts = map[tag] || ['<p>', '</p>', 'Teks'];
                insertAtCursor(content, parts[0], parts[1], parts[2]);
            });
        });

        document.querySelectorAll('[data-editor-toolbar] [data-insert]').forEach(function (button) {
            button.addEventListener('click', function () {
                var html = button.getAttribute('data-insert');
                insertAtCursor(content, html, '', '');
            });
        });

        // --- nama berkas cover ---------------------------------------------
        var coverInput = document.getElementById('cover');
        var coverName = document.querySelector('[data-cover-name]');

        if (coverInput && coverName) {
            coverInput.addEventListener('change', function () {
                var file = coverInput.files && coverInput.files[0];
                coverName.textContent = file ? file.name : 'Pilih gambar cover';
            });
        }

        // --- cegah kirim ganda ---------------------------------------------
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                setTimeout(function () {
                    button.disabled = true;
                    button.classList.add('is-disabled');
                }, 10);
            }
        });
    })();
</script>

<?php
$data['content'] = ob_get_clean();

require_once __DIR__ . '/../../layouts/admin.php';
