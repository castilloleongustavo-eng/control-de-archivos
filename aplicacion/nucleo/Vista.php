<?php
declare(strict_types=1);
namespace Aplicacion\Nucleo;
/** Compone plantillas internas con la estructura común, manteniendo SQL fuera del HTML. */
final class Vista
{
    /** Recibe una plantilla elegida por el controlador y datos de presentación escapados al usar. */
    public static function mostrar(string $plantilla, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $aviso = $_SESSION['aviso'] ?? null; unset($_SESSION['aviso']);
        ob_start(); require RUTAS['vistas'] . '/' . $plantilla . '.php';
        $contenido = ob_get_clean(); require RUTAS['vistas'] . '/componentes/estructura.php';
    }
}
