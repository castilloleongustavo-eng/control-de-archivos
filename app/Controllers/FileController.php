<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{View, Session, HttpException};
use App\Middleware\Security;
use App\Models\StoredFile;
use App\Services\FileStorage;
final class FileController
{
    public function index(bool $dashboard = false): void
    {
        $user = Security::user();
        $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 180);
        $type = substr((string)($_GET['type'] ?? ''), 0, 5);
        $page = max(1, (int)($_GET['page'] ?? 1));
        View::render('files', ['title' => $dashboard ? 'Resumen' : 'Mis archivos', 'dashboard' => $dashboard, 'files' => StoredFile::listing($user,$search,$type,$page), 'stats' => StoredFile::stats($user), 'search' => $search, 'type' => $type, 'page' => $page]);
    }
    public function uploadForm(): void { Security::user(); View::render('upload', ['title' => 'Subir archivo']); }
    public function upload(): void
    {
        $user = Security::user();
        (new FileStorage())->upload($_FILES['file'] ?? [], $user);
        Session::flash('Tu archivo se guardó correctamente.', 'success'); redirect('/files');
    }
    public function preview(string $id): void
    {
        $file = StoredFile::owned($id, Security::user());
        if (!$file) throw new HttpException(404, 'Archivo no disponible.');
        (new FileStorage())->path($file);
        View::render('preview', ['title' => 'Vista previa', 'file' => $file]);
    }
    public function stream(string $id, bool $download): void
    {
        $file = StoredFile::owned($id, Security::user());
        if (!$file) throw new HttpException(404, 'Archivo no disponible.');
        $path = (new FileStorage())->path($file);
        $handle = fopen($path, 'rb');
        if ($handle === false) throw new HttpException(404, 'Archivo no disponible.');
        $inline = !$download && in_array($file['mime_type'], ['application/pdf','image/jpeg','image/png'], true);
        header('Content-Type: ' . $file['mime_type']);
        header("Content-Disposition: " . ($inline ? 'inline' : 'attachment') . "; filename=\"archivo." . $file['extension'] . "\"; filename*=UTF-8''" . rawurlencode($file['original_name']));
        header('Content-Length: ' . fstat($handle)['size']);
        // El PDF puede tener contenido activo: sandbox sin scripts ni navegación.
        header("Content-Security-Policy: sandbox; default-src 'none'; frame-ancestors 'self'");
        header('X-Frame-Options: SAMEORIGIN');
        session_write_close(); fpassthru($handle); fclose($handle);
    }
    public function delete(string $id): void
    {
        (new FileStorage())->delete($id, Security::user());
        Session::flash('El archivo se eliminó correctamente.', 'success'); redirect('/files');
    }
}
