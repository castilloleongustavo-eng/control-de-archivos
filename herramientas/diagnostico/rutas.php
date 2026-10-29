<?php
/**
 * Archivo: herramientas/diagnostico/rutas.php
 * Propósito: Entrega al lanzador las rutas resueltas de la aplicación.
 * Funcionamiento: Solo por consola, carga el entorno y escribe el mapa RUTAS como JSON para PowerShell.
 */

/** Expone únicamente las rutas al lanzador CLI; nunca credenciales ni información HTTP. */
// Impide ejecutar este diagnóstico como una página web.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__,2).'/aplicacion/inicio.php';
// La salida JSON es el contrato de lectura con herramientas/servidor.ps1.
echo json_encode(RUTAS,JSON_UNESCAPED_SLASHES);
