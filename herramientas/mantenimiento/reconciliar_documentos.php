<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 2) . '/aplicacion/inicio.php';
$aplicar = in_array('--aplicar', $argv, true);
if ($aplicar && !in_array('--servidor-detenido', $argv, true)) { fwrite(STDERR, "Detener Apache y añadir --servidor-detenido antes de aplicar.\n"); exit(1); }
$conexion = Aplicacion\Nucleo\Conexion::obtener();
$base = str_replace('\\', '/', realpath(RUTAS['documentos'])) . '/';
$cantidad = 0;
foreach (glob(RUTAS['documentos'] . '/*/*') as $documento) {
    $real = str_replace('\\', '/', realpath($documento) ?: '');
    $propietario = basename(dirname($documento)); $nombre = basename($documento);
    if (!str_starts_with($real, $base) || !ctype_digit($propietario) || !preg_match('/^([a-f0-9]{64})(\.eliminando)?$/D', $nombre, $m)) continue;
    $existe = Aplicacion\Modelos\Documento::existeNombre((int)$propietario,$m[1]);
    if (isset($m[2])) {
        $cantidad++;
        echo ($existe ? 'Restaurar ' : 'Purgar ') . $propietario . '/' . $nombre . PHP_EOL;
        if ($aplicar) {
            $correcto = $existe ? (!file_exists(dirname($documento) . '/' . $m[1]) && rename($documento, dirname($documento) . '/' . $m[1])) : unlink($documento);
            if (!$correcto) throw new RuntimeException('Reconciliación fallida. Inspeccionar manualmente.');
        }
    } elseif (!$existe) {
        $cantidad++; echo 'Carga huérfana: ' . $propietario . '/' . $nombre . PHP_EOL;
        if ($aplicar && !unlink($documento)) throw new RuntimeException('No se pudo purgar la carga huérfana.');
    }
}
foreach (Aplicacion\Modelos\Documento::paraMantenimiento() as $fila) {
    try { (new Aplicacion\Servicios\AlmacenDocumentos())->ruta($fila); }
    catch (Throwable $excepcion) { $cantidad++; echo 'Metadato sin binario disponible: ' . $fila['id_documento'] . ' (requiere revisión manual)' . PHP_EOL; }
}
echo ($aplicar ? 'Aplicado. ' : 'Solo diagnóstico. ') . "$cantidad incidencias.\n";
