<?php
/**
 * Archivo: publico/index.php
 * Propósito: Es el punto de entrada de las peticiones web de la aplicación.
 * Funcionamiento: Inicializa el entorno, envía cabeceras, inicia la sesión y ejecuta el enrutador; convierte las excepciones en una pantalla de error.
 */

declare(strict_types=1);
// Carga el arranque común antes de usar clases, configuración o funciones auxiliares.
require dirname(__DIR__) . '/aplicacion/inicio.php';
use Aplicacion\Nucleo\{Sesion, Enrutador, Vista, ExcepcionHttp};
use Aplicacion\Intermediarios\Seguridad;
// Envía las políticas HTTP antes de producir cualquier HTML.
Seguridad::cabeceras();
try {
    if (version_compare(PHP_VERSION, '8.5.12', '<') || preg_match('/RC|alpha|beta|dev/i', PHP_VERSION)) throw new ExcepcionHttp(503, 'El servidor requiere una versión estable y corregida de PHP 8.5.12 o posterior.');
    // Abre la sesión y entrega la petición al controlador que corresponda a la ruta.
    Sesion::iniciar();
    (new Enrutador())->despachar();
} catch (Throwable $excepcion) {
    // Distingue errores públicos de fallos internos; estos últimos se registran y muestran un mensaje general.
    $estado = $excepcion instanceof ExcepcionHttp ? $excepcion->estado : 500;
    if ($estado === 500) error_log((string)$excepcion);
    http_response_code($estado);
    Vista::mostrar('componentes/error', ['titulo' => 'Solicitud no completada', 'estado' => $estado, 'mensaje' => $excepcion instanceof ExcepcionHttp ? $excepcion->getMessage() : 'No pudimos completar la operación. Inténtalo nuevamente más tarde.']);
}
