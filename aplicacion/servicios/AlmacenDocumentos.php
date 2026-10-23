<?php
declare(strict_types=1);
namespace Aplicacion\Servicios;
use Aplicacion\Nucleo\{Conexion, ExcepcionHttp};
use Aplicacion\Modelos\Documento;
use Aplicacion\Validadores\ValidadorDocumento;
/** Coordina disco privado y metadatos con compensación de fallos; las consultas viven en Documento. */
final class AlmacenDocumentos
{
    /** Valida metadatos y realpath; devuelve una ruta contenida en el almacenamiento privado o lanza 404. */
    public function ruta(array $documento): string
    {
        $esperado = $documento['id_usuario'] . '/' . $documento['nombre_almacenado'];
        // La ruta se reconstruye desde datos validados; no se acepta una ruta arbitraria enviada por el navegador.
        if (!preg_match('/^[a-f0-9]{64}$/D', $documento['nombre_almacenado']) || $documento['ruta_relativa'] !== $esperado) throw new ExcepcionHttp(404, 'Archivo no disponible.');
        $base = realpath(RUTAS['documentos']);
        $ruta = realpath($base . '/' . $esperado);
        if ($ruta === false || !str_starts_with(str_replace('\\', '/', $ruta), str_replace('\\', '/', $base) . '/') || !is_file($ruta)) throw new ExcepcionHttp(404, 'Archivo no disponible.');
        return $ruta;
    }
    /** Recibe la carga nativa y el propietario; valida contenido, genera nombres aleatorios y compensa fallos SQL. */
    public function subir(array $carga, int $usuario): void
    {
        if (!is_int($carga['error'] ?? null) || !is_string($carga['tmp_name'] ?? null) || !is_string($carga['name'] ?? null)) throw new ExcepcionHttp(422, 'Selecciona un archivo válido.');
        if (in_array($carga['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new ExcepcionHttp(413, 'El archivo supera el máximo de 10 MB.');
        if ($carga['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($carga['tmp_name'])) throw new ExcepcionHttp(422, 'No se pudo recibir el archivo. Intenta nuevamente.');
        $datos = (new ValidadorDocumento())->inspeccionar($carga['tmp_name'], $carga['name']);
        // El ID identifica el registro; el nombre físico independiente evita exponer nombres originales en disco.
        $identificador = bin2hex(random_bytes(16)); $nombreAlmacenado = bin2hex(random_bytes(32));
        $directorio = RUTAS['documentos'] . '/' . $usuario;
        if (!is_dir($directorio) && !mkdir($directorio, 0700, true)) throw new \RuntimeException('No se pudo crear el directorio privado');
        // realpath sobre el directorio impide que una unión o enlace salga del directorio privado configurado.
        $raiz = str_replace('\\', '/', realpath(RUTAS['documentos'])) . '/';
        if (!str_starts_with(str_replace('\\', '/', realpath($directorio)) . '/', $raiz)) throw new \RuntimeException('Directorio privado fuera de la ubicación permitida');
        $destino = $directorio . '/' . $nombreAlmacenado;
        if (!move_uploaded_file($carga['tmp_name'], $destino)) throw new \RuntimeException('No se pudo guardar la carga');
        try {
            Documento::crear([$identificador,$usuario,$datos['nombre_original'],$nombreAlmacenado,$datos['tipo_mime'],$datos['extension'],$datos['tamano_bytes'], $usuario . '/' . $nombreAlmacenado]);
        } catch (\Throwable $excepcion) {
            // Si falla MySQL después de mover el archivo, se compensa la operación retirando esa carga.
            if (!unlink($destino)) error_log('Carga huérfana: ' . $nombreAlmacenado);
            throw $excepcion;
        }
    }
    /** Recibe ID y propietario; aparta el binario, confirma la transacción y restaura el archivo ante fallos. */
    public function eliminar(string $identificador, int $usuario): void
    {
        $conexion = Conexion::obtener(); $conexion->beginTransaction();
        // «apartado» permite recuperar el binario si la transacción SQL no llega a confirmarse.
        $ruta = null; $apartado = null;
        try {
            $documento = Documento::bloquearPropietario($identificador,$usuario);
            if (!$documento) throw new ExcepcionHttp(404, 'Archivo no disponible.');
            $ruta = $this->ruta($documento);
            // Mismo volumen: cambio de nombre reversible mientras la fila está bloqueada.
            $apartado = $ruta . '.eliminando';
            if (!rename($ruta, $apartado)) { $apartado = null; throw new \RuntimeException('No se pudo preparar la eliminación'); }
            Documento::eliminar($identificador,$usuario);
            $conexion->commit();
        } catch (\Throwable $excepcion) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            if ($apartado !== null && is_file($apartado) && !rename($apartado, $ruta)) error_log('Restauración pendiente: ' . basename($apartado));
            throw $excepcion;
        }
        if ($apartado !== null && is_file($apartado) && !unlink($apartado)) error_log('Limpieza pendiente: ' . basename($apartado));
    }
}
