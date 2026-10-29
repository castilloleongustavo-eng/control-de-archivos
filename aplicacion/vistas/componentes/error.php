<?php
/**
 * Archivo: aplicacion/vistas/componentes/error.php
 * Propósito: Presenta un error con su estado y mensaje público.
 * Funcionamiento: Recibe estado y mensaje desde la entrada web y ofrece un enlace de regreso según la sesión existente.
 */
 /** Presenta únicamente el estado y mensaje público; las trazas técnicas se guardan en el registro privado. */ ?>
<?php /* Presenta estado y mensaje público escapados; el enlace de regreso depende de la sesión. */ ?>
<div class="pagina-error"><span class="sobretitulo">SOLICITUD NO COMPLETADA</span><div class="codigo-error"><?= escapar($estado) ?></div><h2>No pudimos completar esta acción</h2><p class="texto-secundario"><?= escapar($mensaje) ?></p><a href="<?= isset($_SESSION['usuario']) ? '/documentos' : '/ingresar' ?>" class="btn btn-primary">Volver a <?= isset($_SESSION['usuario']) ? 'mis archivos' : 'iniciar sesión' ?></a></div>
