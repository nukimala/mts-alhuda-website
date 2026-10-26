<?php

class BeritaController
{
    /** Jumlah berita per halaman. */
    private const PER_PAGE = 9;

    public function index(): void
    {
        $total = Content::postCount();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = (int) ($_GET['halaman'] ?? 1);
        $page = max(1, min($pages, $page));

        $data = [
            'title'   => $page > 1
                ? 'Berita & Artikel — Halaman ' . $page . ' | MTs Al-Huda Gondang'
                : 'Berita & Artikel | MTs Al-Huda Gondang',
            'posts'   => Content::posts([
                'limit'  => self::PER_PAGE,
                'offset' => ($page - 1) * self::PER_PAGE,
            ]),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
        ];

        require_once __DIR__ . '/../Views/berita/index.php';
    }

    public function detail(string $slug = ''): void
    {
        $post = Content::post($slug);

        // Slug tidak dikenal atau berita belum terbit: jangan tampilkan
        // halaman kosong, langsung 404 yang benar.
        if ($post === null) {
            (new ErrorController())->notFound();
            return;
        }

        $data = [
            'title'   => $post['title'] . ' | MTs Al-Huda Gondang',
            'post'    => $post,
            'related' => Content::relatedPosts((int) $post['id'], 3),
        ];

        require_once __DIR__ . '/../Views/berita/detail.php';
    }
}
