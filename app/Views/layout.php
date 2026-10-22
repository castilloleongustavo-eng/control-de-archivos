<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($title ?? 'Inicio') ?> · ADMOSA</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
<?php $signedIn = isset($_SESSION['user']); ?>
<a class="skip-link" href="#main">Saltar al contenido</a>
<?php if ($signedIn): ?>
<aside class="sidebar">
    <a class="brand" href="/"><span class="brand-symbol">A<span></span></span><span>ADMOSA<small>FILE MANAGER</small></span></a>
    <div class="workspace-label">ESPACIO PERSONAL</div>
    <nav aria-label="Navegación principal">
        <a class="nav-item <?= ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'active' : '' ?>" href="/"><span aria-hidden="true">◫</span> Resumen</a>
        <a class="nav-item <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/files') && !str_contains($_SERVER['REQUEST_URI'], 'upload') ? 'active' : '' ?>" href="/files"><span aria-hidden="true">▤</span> Mis archivos</a>
        <a class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'upload') ? 'active' : '' ?>" href="/files/upload"><span aria-hidden="true">↥</span> Subir archivo</a>
    </nav>
    <div class="private-note"><span class="secure-dot"></span><strong>Solo para ti</strong><p>Tus documentos están en tu espacio privado.</p></div>
    <div class="profile"><span class="avatar"><?= e(mb_strtoupper(mb_substr($_SESSION['user']['username'],0,1))) ?></span><div><strong><?= e($_SESSION['user']['username']) ?></strong><small>Cuenta personal</small></div></div>
    <form action="/logout" method="post" class="logout"><?= csrf() ?><button type="submit">↪ &nbsp; Cerrar sesión</button></form>
</aside>
<div class="app-shell">
<header class="topbar"><span>Mi espacio <span class="breadcrumb-separator">/</span> <strong><?= e($title) ?></strong></span><span class="privacy-badge"><span class="secure-dot"></span> Almacenamiento privado</span></header>
<main id="main" class="workspace">
<?php if ($flash): ?><div class="alert alert-<?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?>
<?= $content ?>
<footer class="workspace-footer"><span>ADMOSA File Manager</span><span>Tu información, en su lugar.</span></footer>
</main></div>
<?php else: ?>
<main id="main" class="auth-shell">
    <section class="auth-story"><a class="brand" href="/login"><span class="brand-symbol">A<span></span></span><span>ADMOSA<small>FILE MANAGER</small></span></a><div class="story-content"><span class="eyebrow">TU ESPACIO. TUS ARCHIVOS.</span><h1>Todo en orden.<br>Todo bajo<br>tu control<span>.</span></h1><p>Un lugar personal para guardar, encontrar y administrar tus documentos con tranquilidad.</p><div class="file-illustration" aria-hidden="true"><div class="illustration-line"></div><div class="illustration-doc"><span class="doc-label">ADMOSA / DOCUMENTOS</span><i></i><i></i><i></i><div class="doc-check">✓</div></div><span class="illustration-pill">● &nbsp; Espacio privado</span></div></div><small>Organización simple. Acceso seguro.</small></section>
    <section class="auth-form-panel"><div class="auth-form-wrap"><?php if ($flash): ?><div class="alert alert-<?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></div><?php endif; ?><?= $content ?></div><small class="auth-footer">ADMOSA · Gestión de archivos por usuario</small></section>
</main>
<?php endif; ?>
</body></html>
