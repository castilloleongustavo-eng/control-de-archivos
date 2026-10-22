<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
$apply = in_array('--apply', $argv, true);
if ($apply && !in_array('--server-stopped', $argv, true)) { fwrite(STDERR, "Detener Apache y añadir --server-stopped antes de aplicar.\n"); exit(1); }
$pdo = App\Core\Database::get();
$base = str_replace('\\', '/', realpath(STORAGE_ROOT . '/private')) . '/';
$query = $pdo->prepare('SELECT id FROM files WHERE user_id=? AND stored_name=?');
$count = 0;
foreach (glob(STORAGE_ROOT . '/private/*/*') as $file) {
    $real = str_replace('\\', '/', realpath($file) ?: '');
    $owner = basename(dirname($file)); $name = basename($file);
    if (!str_starts_with($real, $base) || !ctype_digit($owner) || !preg_match('/^([a-f0-9]{64})(\.deleting)?$/D', $name, $m)) continue;
    $query->execute([$owner,$m[1]]); $exists = (bool)$query->fetch();
    if (isset($m[2])) {
        $count++;
        echo ($exists ? 'Restaurar ' : 'Purgar ') . $owner . '/' . $name . PHP_EOL;
        if ($apply) {
            $ok = $exists ? (!file_exists(dirname($file) . '/' . $m[1]) && rename($file, dirname($file) . '/' . $m[1])) : unlink($file);
            if (!$ok) throw new RuntimeException('Reconciliación fallida. Inspeccionar manualmente.');
        }
    } elseif (!$exists) {
        $count++; echo 'Carga huérfana: ' . $owner . '/' . $name . PHP_EOL;
        if ($apply && !unlink($file)) throw new RuntimeException('No se pudo purgar la carga huérfana.');
    }
}
foreach ($pdo->query('SELECT * FROM files') as $row) {
    try { (new App\Services\FileStorage())->path($row); }
    catch (Throwable $ex) { $count++; echo 'Metadato sin binario disponible: ' . $row['id'] . ' (requiere revisión manual)' . PHP_EOL; }
}
echo ($apply ? 'Aplicado. ' : 'Solo diagnóstico. ') . "$count incidencias.\n";
