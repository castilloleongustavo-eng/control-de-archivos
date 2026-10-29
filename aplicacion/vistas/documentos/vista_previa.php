<?php
/**
 * Archivo: aplicacion/vistas/documentos/vista_previa.php
 * Propósito: Selecciona el visor del documento autorizado.
 * Funcionamiento: Muestra imágenes directamente, PDF mediante PDF.js y TXT o archivos Office mediante el lector local en JavaScript.
 */
 /** El tipo MIME selecciona el lector. Todas las URLs del contenido pasan por rutas que verifican sesión y propietario. */ ?>
<?php /* Datos del documento y acciones para volver al listado o descargar el original. */ ?>
<div class="encabezado-pagina"><div><h1 class="titulo-previa"><?= escapar($documento['nombre_original']) ?></h1><p class="texto-secundario"><?= escapar(strtoupper($documento['extension'])) ?> · <?= escapar(tamanoLegible((int)$documento['tamano_bytes'])) ?> · <?= escapar(date('d/m/Y', strtotime($documento['fecha_carga']))) ?></p></div><div class="acciones-previa"><a href="/documentos" class="btn btn-outline-secondary">Volver</a><a href="/documentos/<?= escapar($documento['id_documento']) ?>/descargar" class="btn btn-primary"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#descargar"></use></svg> Descargar</a></div></div>
<section class="panel-previa">
<?php /* Imágenes: el navegador solicita el contenido mediante una ruta que comprueba la propiedad. */ ?>
<?php if (in_array($documento['tipo_mime'], ['image/jpeg','image/png'], true)): ?><img class="imagen-previa" src="/documentos/<?= escapar($documento['id_documento']) ?>/ver" alt="Vista previa de <?= escapar($documento['nombre_original']) ?>">
<?php /* PDF: navegación entre páginas y un canvas que dibuja vista_pdf.js. */ ?>
<?php elseif ($documento['tipo_mime'] === 'application/pdf'): ?>
<div class="visor-pdf" id="visor-pdf" data-origen="/documentos/<?= escapar($documento['id_documento']) ?>/ver">
    <div class="barra-pdf"><button class="btn btn-sm btn-outline-secondary" id="pdf-anterior" type="button" disabled>← Anterior</button><span id="contador-paginas" aria-live="polite">Cargando documento…</span><button class="btn btn-sm btn-outline-secondary" id="pdf-siguiente" type="button" disabled>Siguiente →</button></div>
    <p id="estado-pdf" class="texto-secundario ayuda-previa" role="status">Preparando la vista previa del documento.</p>
    <div class="contenedor-lienzo-pdf"><canvas id="lienzo-pdf" role="img" aria-label="Página del documento PDF"></canvas></div>
</div>
<script type="module" src="/recursos/scripts/vista_pdf.js"></script>

<?php /* TXT, DOCX y XLSX: JavaScript obtiene el archivo y construye elementos de lectura locales. */ ?>
<?php else: ?>
<div id="visor-documento" data-origen="/documentos/<?= escapar($documento['id_documento']) ?>/ver" data-formato="<?= escapar($documento['extension']) ?>" aria-busy="true">
    <p id="estado-documento" class="texto-secundario" role="status">Cargando documento…</p>
    <div id="controles-documento" class="controles-documento" hidden></div>
    <div id="contenido-documento" class="contenido-documento"></div>
</div>
<script type="module" src="/recursos/scripts/vista_documento.js"></script>
<?php endif; ?>
</section>
