<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= escapar($titulo ?? 'Inicio') ?> · Documentos</title>
    <link rel="stylesheet" href="/recursos/estilos/bootstrap.min.css">
    <link rel="stylesheet" href="/recursos/estilos/aplicacion.css">
    <script src="/recursos/scripts/aplicacion.js" defer></script>
</head>
<body>
<?php $autenticado = isset($_SESSION['usuario']); ?>
<a class="saltar-contenido" href="#contenido-principal">Saltar al contenido</a>
<?php if ($autenticado): ?>
<aside class="barra-lateral">
    <a class="marca" href="/"><span class="simbolo-marca">D<span></span></span><span>Documentos<small>ESPACIO PERSONAL</small></span></a>
    <div class="etiqueta-espacio">ESPACIO PERSONAL</div>
    <nav aria-label="Navegación principal">
        <a class="enlace-navegacion <?= ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'active' : '' ?>" href="/"><span aria-hidden="true">◫</span> Resumen</a>
        <a class="enlace-navegacion <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/documentos') && !str_contains($_SERVER['REQUEST_URI'], 'subir') ? 'active' : '' ?>" href="/documentos"><span aria-hidden="true">▤</span> Mis archivos</a>
        <a class="enlace-navegacion <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'subir') ? 'active' : '' ?>" href="/documentos/subir"><span aria-hidden="true">↥</span> Subir archivo</a>
    </nav>
    <div class="nota-privacidad"><span class="indicador-seguridad"></span><strong>Solo para ti</strong><p>Tus documentos están en tu espacio privado.</p></div>
    <div class="perfil"><span class="avatar"><?= escapar(mb_strtoupper(mb_substr($_SESSION['usuario']['nombre_usuario'],0,1))) ?></span><div><strong><?= escapar($_SESSION['usuario']['nombre_usuario']) ?></strong><small>Cuenta personal</small></div></div>
    <form action="/salir" method="post" class="cerrar-sesion"><?= campoCsrf() ?><button type="submit">↪ &nbsp; Cerrar sesión</button></form>
</aside>
<div class="estructura-aplicacion">
<header class="barra-superior"><span>Mi espacio <span class="separador-navegacion">/</span> <strong><?= escapar($titulo) ?></strong></span><span class="etiqueta-privacidad"><span class="indicador-seguridad"></span> Almacenamiento privado</span></header>
<main id="contenido-principal" class="espacio">
<?php if ($aviso): ?><div class="alert alert-<?= escapar($aviso[1]) ?>" role="status"><?= escapar($aviso[0]) ?></div><?php endif; ?>
<?= $contenido ?>
<footer class="pie-espacio"><span>Documentos</span><span>Tu información, en su lugar.</span></footer>
</main></div>
<?php else: ?>
<main id="contenido-principal" class="pantalla-autenticacion">
    <section class="presentacion-autenticacion"><a class="marca" href="/ingresar"><span class="simbolo-marca">D<span></span></span><span>Documentos<small>ESPACIO PERSONAL</small></span></a><div class="contenido-presentacion"><span class="sobretitulo">TU ESPACIO. TUS ARCHIVOS.</span><h1>Todo en orden.<br>Todo bajo<br>tu control<span>.</span></h1><p>Un lugar personal para guardar, encontrar y administrar tus documentos con tranquilidad.</p><div class="ilustracion-documento" aria-hidden="true"><div class="linea-ilustracion"></div><div class="documento-ilustracion"><span class="etiqueta-documento">MI ESPACIO / DOCUMENTOS</span><i></i><i></i><i></i><div class="documento-confirmado">✓</div></div><span class="etiqueta-ilustracion">● &nbsp; Espacio privado</span></div></div><small>Organización simple. Acceso seguro.</small></section>
    <section class="panel-autenticacion"><div class="contenedor-autenticacion"><?php if ($aviso): ?><div class="alert alert-<?= escapar($aviso[1]) ?>" role="status"><?= escapar($aviso[0]) ?></div><?php endif; ?><?= $contenido ?></div><small class="pie-autenticacion">Documentos · Gestión de archivos por usuario</small></section>
</main>
<?php endif; ?>
</body></html>
