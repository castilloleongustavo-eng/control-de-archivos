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
        // EXTR_SKIP evita reemplazar variables locales. El aviso se consume una sola vez tras una redirección.
        $aviso = $_SESSION['aviso'] ?? null; unset($_SESSION['aviso']);
        ob_start(); require RUTAS['vistas'] . '/' . $plantilla . '.php';
        // El búfer captura la vista específica y la estructura común incorpora después ese contenido.
        $contenido = ob_get_clean(); require RUTAS['vistas'] . '/componentes/estructura.php';
    }
}
