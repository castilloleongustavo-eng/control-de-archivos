<?php
/**
 * Archivo: aplicacion/vistas/documentos/carga.php
 * Propósito: Presenta la pantalla para subir un documento.
 * Funcionamiento: Envía un archivo mediante multipart/form-data a /documentos/subir e incluye CSRF; el servidor realiza la validación definitiva.
 */
 /** El formulario conserva el envío multipart y CSRF. La validación definitiva del archivo se realiza en el servidor. */ ?>
<?php /* Título de la pantalla y enlace de regreso al listado. */ ?>
<div class="encabezado-pagina"><div><h1>Subir archivo</h1></div><a href="/documentos" class="enlace-volver">← Volver a mis archivos</a></div>
<?php /* Panel de carga: formulario multipart capaz de enviar el binario del archivo. */ ?>
<div class="estructura-carga"><section class="panel-carga"><form action="/documentos/subir" method="post" enctype="multipart/form-data" id="formulario-carga"><?= campoCsrf() ?><?php /* Límite orientativo del formulario; PHP y ValidadorDocumento verifican el tamaño real. */ ?>
<input type="hidden" name="MAX_FILE_SIZE" value="10485760"><?php /* Zona de selección y arrastre: los identificadores conectan estos controles con aplicacion.js. */ ?>
<div class="zona-carga" id="zona-carga"><div class="icono-carga" aria-hidden="true"><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#subir"></use></svg></div><label class="visually-hidden selector-archivo" for="documento">Seleccionar archivo</label><input class="form-control mt-3" id="documento" name="documento" type="file" required title="PDF, JPG, PNG, TXT, DOCX o XLSX; máximo 10 MB" accept=".pdf,.jpg,.jpeg,.png,.txt,.docx,.xlsx"></div><?php /* Estado accesible con el nombre y tamaño del archivo elegido. */ ?>
<div class="archivo-seleccionado" id="archivo-seleccionado" role="status" aria-live="polite">Ningún archivo seleccionado.</div><?php /* Cancelar vuelve al listado; Guardar envía el documento y el token al controlador. */ ?>
<div class="acciones-carga"><a class="btn btn-outline-secondary" href="/documentos">Cancelar</a><button class="btn btn-primary" type="submit" id="guardar-documento">Guardar archivo <svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#subir"></use></svg></button></div></form></section></div>
