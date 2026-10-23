<?php /** Marca compartida por el título y el menú; se escapa al incorporarla al HTML. */ $nombreAplicacion = getenv('NOMBRE_APLICACION') ?: 'Control de archivos'; ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <link rel="icon" type="image/svg+xml" href="/recursos/iconos/documentos.svg">
    <title><?= escapar($titulo ?? 'Inicio') ?> · <?= escapar($nombreAplicacion) ?></title>
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
        <a class="marca" href="/documentos" aria-label="Mis documentos"><span class="simbolo-marca"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#carpeta"></use></svg></span><span class="texto-navegacion"><?= escapar($nombreAplicacion) ?></span></a>
        <button type="button" class="boton-icono cerrar-navegacion" id="cerrar-navegacion" aria-label="Cerrar menú"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#cerrar"></use></svg></button>
    </div>
    <nav aria-label="Navegación principal">
        <a class="enlace-navegacion <?= (($_SERVER['REQUEST_URI'] ?? '') === '/' || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/documentos')) && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'subir') ? 'active' : '' ?>" href="/documentos" aria-label="Mis documentos" title="Mis documentos"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#documento"></use></svg><span class="texto-navegacion">Mis documentos</span></a>
        <a class="enlace-navegacion <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'subir') ? 'active' : '' ?>" href="/documentos/subir" aria-label="Subir documento" title="Subir documento"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#subir"></use></svg><span class="texto-navegacion">Subir documento</span></a>
    </nav>
    <div class="pie-navegacion">
        <button type="submit" form="formulario-salir" class="enlace-navegacion salir-navegacion" aria-label="Cerrar sesión" title="Cerrar sesión"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#salir"></use></svg><span class="texto-navegacion">Cerrar sesión</span></button>
    </div>
</aside>
<button type="button" class="fondo-navegacion" id="fondo-navegacion" aria-label="Cerrar menú" tabindex="-1" hidden></button>
<div class="estructura-aplicacion" id="estructura-aplicacion">
<header class="barra-superior">
    <div class="seccion-actual"><button type="button" class="boton-icono" id="alternar-navegacion" aria-label="Contraer menú" aria-controls="navegacion-lateral" aria-expanded="true"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#menu"></use></svg></button><div><strong><?= escapar($titulo) ?></strong></div></div>
    <div class="acciones-cabecera">
        <div class="perfil"><span class="avatar"><?= escapar(mb_strtoupper(mb_substr($_SESSION['usuario']['nombre_usuario'],0,1))) ?></span><div><strong><?= escapar($_SESSION['usuario']['nombre_usuario']) ?></strong></div></div>
        <form action="/salir" method="post" class="cerrar-sesion" id="formulario-salir"><?= campoCsrf() ?><button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#salir"></use></svg><span>Cerrar sesión</span></button></form>
    </div>
</header>
<main id="contenido-principal" class="espacio">
<?php if ($aviso): ?><div class="alert alert-<?= escapar($aviso[1]) ?>" role="status"><?= escapar($aviso[0]) ?></div><?php endif; ?>
<?= $contenido ?>
</main></div>
<?php else: ?>
<main id="contenido-principal" class="pantalla-autenticacion">
    <section class="panel-autenticacion" aria-label="Acceso a Documentos">
        <div class="emblema-acceso" aria-hidden="true"><svg class="icono-interfaz"><use href="/recursos/iconos/interfaz.svg#carpeta"></use></svg></div>
        <div class="contenedor-autenticacion"><?php if ($aviso): ?><div class="alert alert-<?= escapar($aviso[1]) ?>" role="status"><?= escapar($aviso[0]) ?></div><?php endif; ?><?= $contenido ?></div>
    </section>
</main>
<?php endif; ?>
</body></html>
