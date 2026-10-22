<?php
declare(strict_types=1);
require dirname(__DIR__) . '/aplicacion/inicio.php';
use Aplicacion\Nucleo\{Sesion, Enrutador, Vista, ExcepcionHttp};
use Aplicacion\Intermediarios\Seguridad;
Seguridad::cabeceras();
try {
    if (version_compare(PHP_VERSION, '8.5.12', '<') || preg_match('/RC|alpha|beta|dev/i', PHP_VERSION)) throw new ExcepcionHttp(503, 'El servidor requiere una versión estable y corregida de PHP 8.5.12 o posterior.');
    Sesion::iniciar();
    (new Enrutador())->despachar();
} catch (Throwable $excepcion) {
    $estado = $excepcion instanceof ExcepcionHttp ? $excepcion->estado : 500;
    if ($estado === 500) error_log((string)$excepcion);
    http_response_code($estado);
    Vista::mostrar('componentes/error', ['titulo' => 'Solicitud no completada', 'estado' => $estado, 'mensaje' => $excepcion instanceof ExcepcionHttp ? $excepcion->getMessage() : 'No pudimos completar la operación. Inténtalo nuevamente más tarde.']);
}
