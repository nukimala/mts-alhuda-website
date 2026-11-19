<?php require_once __DIR__ . '/../../helpers.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Website resmi MTS Al-Huda Gondang Nganjuk: Madrasah Tsanawiyah Terakreditasi A Unggul">
    <title><?= htmlspecialchars($data['title'] ?? 'MTS Al-Huda Gondang (Akreditasi Unggul)') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/style.css?v=' . (file_exists(__DIR__ . '/../../../public/css/style.css') ? filemtime(__DIR__ . '/../../../public/css/style.css') : '2.1')) ?>">
</head>
<body>
