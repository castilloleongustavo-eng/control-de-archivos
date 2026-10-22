<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database, HttpException};
use App\Models\StoredFile;
final class FileStorage
{
    public function path(array $file): string
    {
        $expected = $file['user_id'] . '/' . $file['stored_name'];
        if (!preg_match('/^[a-f0-9]{64}$/D', $file['stored_name']) || $file['storage_path'] !== $expected) throw new HttpException(404, 'Archivo no disponible.');
        $base = realpath(STORAGE_ROOT . '/private');
        $path = realpath($base . '/' . $expected);
        if ($path === false || !str_starts_with(str_replace('\\', '/', $path), str_replace('\\', '/', $base) . '/') || !is_file($path)) throw new HttpException(404, 'Archivo no disponible.');
        return $path;
    }
    public function upload(array $upload, int $user): void
    {
        if (!is_int($upload['error'] ?? null) || !is_string($upload['tmp_name'] ?? null) || !is_string($upload['name'] ?? null)) throw new HttpException(422, 'Selecciona un archivo válido.');
        if (in_array($upload['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new HttpException(413, 'El archivo supera el máximo de 10 MB.');
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) throw new HttpException(422, 'No se pudo recibir el archivo. Intenta nuevamente.');
        $data = (new UploadValidator())->inspect($upload['tmp_name'], $upload['name']);
        $id = bin2hex(random_bytes(16)); $stored = bin2hex(random_bytes(32));
        $dir = STORAGE_ROOT . '/private/' . $user;
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new \RuntimeException('Cannot create storage');
        // realpath sobre el directorio impide que una unión o enlace salga de storage/private.
        $root = str_replace('\\', '/', realpath(STORAGE_ROOT . '/private')) . '/';
        if (!str_starts_with(str_replace('\\', '/', realpath($dir)) . '/', $root)) throw new \RuntimeException('Unsafe storage directory');
        $target = $dir . '/' . $stored;
        if (!move_uploaded_file($upload['tmp_name'], $target)) throw new \RuntimeException('Cannot move upload');
        try {
            Database::get()->prepare('INSERT INTO files (id,user_id,original_name,stored_name,mime_type,extension,file_size,storage_path) VALUES (?,?,?,?,?,?,?,?)')->execute([$id,$user,$data['original_name'],$stored,$data['mime_type'],$data['extension'],$data['file_size'], $user . '/' . $stored]);
        } catch (\Throwable $ex) {
            if (!unlink($target)) error_log('Orphan upload: ' . $stored);
            throw $ex;
        }
    }
    public function delete(string $id, int $user): void
    {
        $pdo = Database::get(); $pdo->beginTransaction();
        $path = null; $trash = null;
        try {
            $q = $pdo->prepare('SELECT * FROM files WHERE id=? AND user_id=? FOR UPDATE');
            $q->execute([$id,$user]); $file = $q->fetch();
            if (!$file) throw new HttpException(404, 'Archivo no disponible.');
            $path = $this->path($file);
            // Mismo volumen: cambio de nombre reversible mientras la fila está bloqueada.
            $trash = $path . '.deleting';
            if (!rename($path, $trash)) { $trash = null; throw new \RuntimeException('Cannot stage deletion'); }
            $pdo->prepare('DELETE FROM files WHERE id=? AND user_id=?')->execute([$id,$user]);
            $pdo->commit();
        } catch (\Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($trash !== null && is_file($trash) && !rename($trash, $path)) error_log('Pending deletion recovery: ' . basename($trash));
            throw $ex;
        }
        if ($trash !== null && is_file($trash) && !unlink($trash)) error_log('Pending trash cleanup: ' . basename($trash));
    }
}
