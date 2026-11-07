<?php

require_once __DIR__ . '/../Support/Database.php';

class BeritaController
{
    public function index(): void
    {
        $pdo = Database::connection();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 6;
        $offset = ($page - 1) * $perPage;
        $q = trim($_GET['q'] ?? '');

        $where = ["p.status = 'published'", "p.deleted_at IS NULL"];
        $params = [];

        if ($q !== '') {
            $where[] = "(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM berita p WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT p.*, u.name as author_name 
                                FROM berita p 
                                LEFT JOIN pengguna u ON u.id = p.author_id 
                                WHERE {$whereSql} 
                                ORDER BY p.is_featured DESC, p.published_at DESC 
                                LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        $totalPages = max(1, (int) ceil($total / $perPage));

        $data = [
            'title' => 'Berita, Prestasi & Pengumuman | MTs Al-Huda Gondang',
            'posts' => $posts,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'q' => $q,
        ];

        require_once __DIR__ . '/../Views/berita/index.php';
    }

    public function detail(string $slug = ''): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare("SELECT p.*, u.name as author_name 
                                FROM berita p 
                                LEFT JOIN pengguna u ON u.id = p.author_id 
                                WHERE p.slug = ? AND p.status = 'published' AND p.deleted_at IS NULL 
                                LIMIT 1");
        $stmt->execute([$slug]);
        $post = $stmt->fetch();

        if (!$post) {
            http_response_code(404);
            require_once __DIR__ . '/../Views/errors/404.php';
            return;
        }

        // Tambah view counter
        try {
            $pdo->prepare("UPDATE berita SET views_count = views_count + 1, views = views + 1 WHERE id = ?")->execute([$post['id']]);
        } catch (Throwable $e) {
            $pdo->prepare("UPDATE berita SET views_count = views_count + 1 WHERE id = ?")->execute([$post['id']]);
        }

        // Berita terbaru lainnya
        $otherStmt = $pdo->prepare("SELECT * FROM berita WHERE id != ? AND status = 'published' AND deleted_at IS NULL ORDER BY published_at DESC LIMIT 3");
        $otherStmt->execute([$post['id']]);
        $otherPosts = $otherStmt->fetchAll();

        $data = [
            'title' => $post['title'] . ' | MTs Al-Huda Gondang',
            'post' => $post,
            'other_posts' => $otherPosts,
        ];

        require_once __DIR__ . '/../Views/berita/detail.php';
    }
}
