<div class="page-heading"><div><span class="eyebrow">VISTA PREVIA</span><h1 class="preview-title"><?= e($file['original_name']) ?></h1><p class="muted"><?= e(strtoupper($file['extension'])) ?> · <?= e(bytes((int)$file['file_size'])) ?> · <?= e(date('d/m/Y', strtotime($file['created_at']))) ?></p></div><div class="preview-actions"><a href="/files" class="btn btn-outline-secondary">Volver</a><a href="/files/<?= e($file['id']) ?>/download" class="btn btn-primary">↓ Descargar</a></div></div>
<section class="preview-panel">
<?php if (in_array($file['mime_type'], ['image/jpeg','image/png'], true)): ?><img class="preview-image" src="/files/<?= e($file['id']) ?>/view" alt="Vista previa de <?= e($file['original_name']) ?>">
<?php elseif ($file['mime_type'] === 'application/pdf'): ?>
<div class="pdf-viewer" id="pdf-viewer" data-source="/files/<?= e($file['id']) ?>/view">
    <div class="pdf-toolbar"><button class="btn btn-sm btn-outline-secondary" id="pdf-previous" type="button" disabled>← Anterior</button><span id="pdf-page-count" aria-live="polite">Cargando documento…</span><button class="btn btn-sm btn-outline-secondary" id="pdf-next" type="button" disabled>Siguiente →</button></div>
    <p id="pdf-status" class="muted preview-tip" role="status">Preparando la vista previa del documento.</p>
    <div class="pdf-canvas-wrap"><canvas id="pdf-canvas" role="img" aria-label="Página del documento PDF"></canvas></div>
</div>
<script type="module" src="/assets/js/pdf-preview.js"></script>
<p class="muted preview-tip">Para buscar texto o usar un lector de pantalla, descarga el PDF y ábrelo con tu lector habitual.</p>
<?php else: ?><div class="empty-state"><div class="file-icon" aria-hidden="true"><?= e(strtoupper($file['extension'])) ?></div><h2>Tu documento está listo</h2><p>Descárgalo para abrirlo con la aplicación correspondiente.</p><a href="/files/<?= e($file['id']) ?>/download" class="btn btn-primary">Descargar documento</a></div><?php endif; ?>
</section>
