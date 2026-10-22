<?php
declare(strict_types=1);
namespace App\Core;
use App\Controllers\{AuthController, FileController};
use App\Middleware\Security;
final class Router
{
    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST' && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 12582912) throw new HttpException(413, 'La solicitud supera el tamaño permitido. Máximo 10 MB por archivo.');
        $private = $path === '/' || str_starts_with($path, '/files') || $path === '/logout';
        if ($private) Security::user();
        if ($method === 'POST') Security::csrf();
        $auth = new AuthController(); $files = new FileController();
        if ($method === 'GET') {
            switch ($path) {
                case '/login': $auth->form('login'); return;
                case '/register': $auth->form('register'); return;
                case '/': $files->index(true); return;
                case '/files': $files->index(); return;
                case '/files/upload': $files->uploadForm(); return;
            }
        }
        if ($method === 'POST') {
            switch ($path) {
                case '/login': $auth->login(); return;
                case '/register': $auth->register(); return;
                case '/logout': $auth->logout(); return;
                case '/files/upload': $files->upload(); return;
            }
        }
        if (preg_match('#^/files/([a-f0-9]{32})/(view|download|preview|delete)$#D', (string)$path, $m)) {
            if ($m[2] === 'delete' && $method === 'POST') { $files->delete($m[1]); return; }
            if ($m[2] !== 'delete' && $method === 'GET') {
                if ($m[2] === 'preview') $files->preview($m[1]);
                else $files->stream($m[1], $m[2] === 'download');
                return;
            }
            header('Allow: ' . ($m[2] === 'delete' ? 'POST' : 'GET'));
            throw new HttpException(405, 'Método no permitido.');
        }
        throw new HttpException(404, 'La página o el archivo no está disponible.');
    }
}
