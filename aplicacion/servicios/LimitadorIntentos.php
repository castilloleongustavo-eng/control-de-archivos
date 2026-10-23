<?php
declare(strict_types=1);
namespace Aplicacion\Servicios;
use Aplicacion\Nucleo\ExcepcionHttp;
use Aplicacion\Modelos\IntentoAutenticacion;
/** Aplica el límite de acceso mediante el modelo, sin mezclar SQL con autenticación. */
final class LimitadorIntentos
{
    /** Recibe una categoría local y lanza 429 después de diez intentos en la ventana. */
    public static function registrarIntento(string $accion): void
    {
        $clave = hash('sha256', $accion . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        // Una clave por IP y acción separa registro e ingreso, sin guardar contraseñas ni confiar en cabeceras proxy.
        if (IntentoAutenticacion::registrar($clave) > 10) {
            header('Retry-After: 900');
            throw new ExcepcionHttp(429, 'Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.');
        }
    }
}
