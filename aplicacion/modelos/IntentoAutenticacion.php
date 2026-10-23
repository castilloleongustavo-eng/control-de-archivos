<?php
declare(strict_types=1);
namespace Aplicacion\Modelos;
use Aplicacion\Nucleo\Conexion;
/** Persiste contadores por IP/categoría; no guarda la contraseña ni el usuario intentado. */
final class IntentoAutenticacion
{
    /** Incrementa de forma atómica una ventana de 15 minutos y devuelve su contador actual. */
    public static function registrar(string $clave): int
    {
        $conexion = Conexion::obtener();
        // Se retiran ventanas vencidas; el incremento atómico evita perder intentos concurrentes.
        $conexion->prepare('DELETE FROM intentos_autenticacion WHERE inicio_ventana < ?')->execute([time() - 900]);
        $conexion->prepare('INSERT INTO intentos_autenticacion (clave,contador,inicio_ventana) VALUES (?,1,?) ON DUPLICATE KEY UPDATE contador=contador+1')->execute([$clave, time()]);
        $consulta = $conexion->prepare('SELECT contador FROM intentos_autenticacion WHERE clave=?');
        $consulta->execute([$clave]); return (int)$consulta->fetchColumn();
    }
}
