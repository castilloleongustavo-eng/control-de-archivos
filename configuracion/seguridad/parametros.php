<?php
/**
 * Archivo: configuracion/seguridad/parametros.php
 * Propósito: Centraliza los límites de carga y los tipos de vista previa.
 * Funcionamiento: Devuelve tamaños expresados en bytes y tipos MIME de referencia para las reglas de la aplicación.
 */

declare(strict_types=1);
/** Límites de aplicación compartidos; no sustituyen los límites preprocesamiento de php.ini. */
// El cuerpo HTTP admite más bytes que el documento porque incluye también los campos multipart.
return ['limite_documento_bytes'=>10485760,'limite_cuerpo_bytes'=>12582912,'tipos_vista_previa'=>['application/pdf','image/jpeg','image/png']];
