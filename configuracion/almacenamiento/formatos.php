<?php
/**
 * Archivo: configuracion/almacenamiento/formatos.php
 * Propósito: Define las extensiones y tipos MIME admitidos.
 * Funcionamiento: Devuelve el catálogo compartido por la validación de archivos y las opciones del filtro de documentos.
 */

declare(strict_types=1);
/** Correspondencia MIME/extensión permitida: documentos habituales sin formatos ejecutables. */
// Cada clave es una extensión permitida y cada valor es el MIME que debe corresponder al contenido real.
return ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','txt'=>'text/plain','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
