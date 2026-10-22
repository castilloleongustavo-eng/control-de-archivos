<?php
declare(strict_types=1);
namespace Aplicacion\Controladores;
use Aplicacion\Nucleo\{Vista, Sesion, ExcepcionHttp};
use Aplicacion\Intermediarios\Seguridad;
use Aplicacion\Modelos\Documento;
use Aplicacion\Servicios\AlmacenDocumentos;
/** Coordina el espacio personal con modelos, almacenamiento y vistas sin realizar SQL. */
final class ControladorDocumentos
{
    /** Recibe el modo resumen; obtiene exclusivamente datos del usuario autenticado y presenta la plantilla. */
    public function indice(bool $resumen = false): void
    {
        $usuario = Seguridad::usuario();
        $busqueda = mb_substr(trim((string)($_GET['busqueda'] ?? '')), 0, 180);
        $tipo = substr((string)($_GET['tipo'] ?? ''), 0, 5);
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        Vista::mostrar('documentos/listado', ['titulo' => $resumen ? 'Resumen' : 'Mis archivos', 'resumen' => $resumen, 'documentos' => Documento::listar($usuario,$busqueda,$tipo,$pagina), 'estadisticas' => Documento::estadisticas($usuario), 'busqueda' => $busqueda, 'tipo' => $tipo, 'pagina' => $pagina]);
    }
    /** Comprueba sesión y muestra el formulario de carga protegido con CSRF. */
    public function formularioCarga(): void { Seguridad::usuario(); Vista::mostrar('documentos/carga', ['titulo' => 'Subir archivo']); }
    /** Recibe la carga nativa y el propietario; valida contenido, genera nombres aleatorios y compensa fallos SQL. */
    public function subir(): void
    {
        $usuario = Seguridad::usuario();
        (new AlmacenDocumentos())->subir($_FILES['documento'] ?? [], $usuario);
        Sesion::notificar('Tu archivo se guardó correctamente.', 'success'); redirigir('/documentos');
    }
    /** Recibe ID; verifica propietario y existencia antes de mostrar la vista previa. */
    public function vistaPrevia(string $identificador): void
    {
        $documento = Documento::delPropietario($identificador, Seguridad::usuario());
        if (!$documento) throw new ExcepcionHttp(404, 'Archivo no disponible.');
        (new AlmacenDocumentos())->ruta($documento);
        Vista::mostrar('documentos/vista_previa', ['titulo' => 'Vista previa', 'documento' => $documento]);
    }
    /** Recibe ID y modo de descarga; autoriza antes de abrir el binario y envía cabeceras seguras. */
    public function transmitir(string $identificador, bool $descargar): void
    {
        $documento = Documento::delPropietario($identificador, Seguridad::usuario());
        if (!$documento) throw new ExcepcionHttp(404, 'Archivo no disponible.');
        $ruta = (new AlmacenDocumentos())->ruta($documento);
        $recurso = fopen($ruta, 'rb');
        if ($recurso === false) throw new ExcepcionHttp(404, 'Archivo no disponible.');
        $enLinea = !$descargar && in_array($documento['tipo_mime'], ['application/pdf','image/jpeg','image/png'], true);
        header('Content-Type: ' . $documento['tipo_mime']);
        header("Content-Disposition: " . ($enLinea ? 'inline' : 'attachment') . "; filename=\"archivo." . $documento['extension'] . "\"; filename*=UTF-8''" . rawurlencode($documento['nombre_original']));
        header('Content-Length: ' . fstat($recurso)['size']);
        // El PDF puede tener contenido activo: sandbox sin scripts ni navegación.
        header("Content-Security-Policy: sandbox; default-src 'none'; frame-ancestors 'self'");
        header('X-Frame-Options: SAMEORIGIN');
        session_write_close(); fpassthru($recurso); fclose($recurso);
    }
    /** Recibe ID y propietario; aparta el binario, confirma la transacción y restaura el archivo ante fallos. */
    public function eliminar(string $identificador): void
    {
        (new AlmacenDocumentos())->eliminar($identificador, Seguridad::usuario());
        Sesion::notificar('El archivo se eliminó correctamente.', 'success'); redirigir('/documentos');
    }
}
