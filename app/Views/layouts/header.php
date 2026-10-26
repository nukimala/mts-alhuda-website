<?php
require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../../Support/Content.php';

$siteName = Content::setting('site_name', 'MTs Al-Huda');
$siteSub = Content::setting('site_subtitle', 'Gondang');
$metaTitle = Content::setting('meta_title', $siteName . ' ' . $siteSub);
$metaDesc = Content::setting('meta_description', 'Madrasah Tsanawiyah Al-Huda Gondang.');

$pageTitle = $data['title'] ?? $metaTitle;
$pageDesc = $data['meta_description'] ?? $metaDesc;
$ogImage = $data['og_image'] ?? base_url('assets/images/LOGO_MTS 1.png');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDesc) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <link rel="icon" href="<?= base_url('assets/images/LOGO_MTS 1.png') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">

    <link rel="stylesheet" href="<?= base_url('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/base.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/components.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/public.css') ?>">
</head>
<body>
