<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <link rel="icon" type="image/svg+xml" href="/recursos/iconos/documentos.svg">
    <title><?= escapar($titulo ?? 'Inicio') ?> · Documentos</title>
    <link rel="stylesheet" href="/recursos/estilos/bootstrap.min.css">
    <link rel="stylesheet" href="/recursos/estilos/aplicacion.css">
    <script src="/recursos/scripts/aplicacion.js" defer></script>
    <script src="/recursos/scripts/navegacion.js" defer></script>
</head>
<body>
<?php $autenticado = isset($_SESSION['usuario']); ?>
<a class="saltar-contenido" href="#contenido-principal">Saltar al contenido</a>
<?php if ($autenticado): ?>
<aside class="barra-lateral" id="navegacion-lateral" aria-label="Menú principal">
    <div class="cabecera-navegacion">
        <a class="marca" href="/" aria-label="Documentos, inicio"><span class="simbolo-marca"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#carpeta"></use></svg></span><span class="texto-navegacion">Documentos<small>ESPACIO PERSONAL</small></span></a>
        <button type="button" class="boton-icono cerrar-navegacion" id="cerrar-navegacion" aria-label="Cerrar menú"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#cerrar"></use></svg></button>
    </div>
    <div class="etiqueta-espacio texto-navegacion">MI ESPACIO</div>
    <nav aria-label="Navegación principal">
        <a class="enlace-navegacion <?= ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'active' : '' ?>" href="/" aria-label="Inicio" title="Inicio"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#inicio"></use></svg><span class="texto-navegacion">Inicio</span></a>
        <a class="enlace-navegacion <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/documentos') && !str_contains($_SERVER['REQUEST_URI'], 'subir') ? 'active' : '' ?>" href="/documentos" aria-label="Mis documentos" title="Mis documentos"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#documento"></use></svg><span class="texto-navegacion">Mis documentos</span></a>
        <a class="enlace-navegacion <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'subir') ? 'active' : '' ?>" href="/documentos/subir" aria-label="Subir documento" title="Subir documento"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#subir"></use></svg><span class="texto-navegacion">Subir documento</span></a>
        <a class="enlace-navegacion enlace-almacenamiento" href="/#informacion-almacenamiento" aria-label="Información de almacenamiento" title="Información de almacenamiento"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#almacenamiento"></use></svg><span class="texto-navegacion">Almacenamiento</span></a>
    </nav>
    <div class="pie-navegacion">
        <button type="submit" form="formulario-salir" class="enlace-navegacion salir-navegacion" aria-label="Cerrar sesión" title="Cerrar sesión"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#salir"></use></svg><span class="texto-navegacion">Cerrar sesión</span></button>
        <div class="nota-privacidad" title="Tus documentos están en tu espacio privado."><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#escudo"></use></svg><div class="texto-navegacion"><strong>Solo para ti</strong><p>Tus documentos están en tu espacio privado.</p></div></div>
        <span class="firma-navegacion texto-navegacion">Un lugar para lo importante.</span>
    </div>
</aside>
<button type="button" class="fondo-navegacion" id="fondo-navegacion" aria-label="Cerrar menú" tabindex="-1" hidden></button>
<div class="estructura-aplicacion" id="estructura-aplicacion">
<header class="barra-superior">
    <div class="seccion-actual"><button type="button" class="boton-icono" id="alternar-navegacion" aria-label="Contraer menú" aria-controls="navegacion-lateral" aria-expanded="true"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#menu"></use></svg></button><div><span class="ruta-seccion">Mi espacio</span><strong><?= escapar($titulo) ?></strong></div></div>
    <div class="acciones-cabecera">
        <span class="etiqueta-privacidad"><span class="indicador-seguridad"></span> Espacio privado</span>
        <div class="perfil"><span class="avatar"><?= escapar(mb_strtoupper(mb_substr($_SESSION['usuario']['nombre_usuario'],0,1))) ?></span><div><strong><?= escapar($_SESSION['usuario']['nombre_usuario']) ?></strong><small>Cuenta personal</small></div></div>
        <form action="/salir" method="post" class="cerrar-sesion" id="formulario-salir"><?= campoCsrf() ?><button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#salir"></use></svg><span>Cerrar sesión</span></button></form>
    </div>
</header>
<main id="contenido-principal" class="espacio">
<?php if ($aviso): ?><div class="alert alert-<?= escapar($aviso[1]) ?>" role="status"><?= escapar($aviso[0]) ?></div><?php endif; ?>
<?= $contenido ?>
<footer class="pie-espacio"><span>Documentos</span><span>Tu información, en su lugar.</span></footer>
</main></div>
<?php else: ?>
<main id="contenido-principal" class="pantalla-autenticacion">
    <section class="presentacion-autenticacion" aria-label="Bienvenido a Documentos">
        <div class="cabecera-acceso"><a class="marca" href="/ingresar"><span class="simbolo-marca"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#carpeta"></use></svg></span><span>Documentos<small>ESPACIO PERSONAL</small></span></a><span class="acceso-privado"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#candado"></use></svg> Acceso personal</span></div>
        <div class="contenido-presentacion"><span class="sobretitulo">MENOS BÚSQUEDAS. MÁS ORDEN.</span><h1>Tu espacio. Todo en orden.</h1><p>Guarda lo importante y encuentra tus documentos en un solo lugar.</p></div>
        <div class="decoracion-acceso" aria-hidden="true"><svg><use href="/recursos/iconos/interfaz.svg#carpeta"></use></svg></div>
    </section>
    <section class="panel-autenticacion"><div class="contenedor-autenticacion"><?php if ($aviso): ?><div class="alert alert-<?= escapar($aviso[1]) ?>" role="status"><?= escapar($aviso[0]) ?></div><?php endif; ?><?= $contenido ?></div><small class="pie-autenticacion"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#escudo"></use></svg> Documentos · Tu espacio personal</small></section>
</main>
<?php endif; ?>
</body></html>
