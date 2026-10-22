<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\{Session, Router, View, HttpException};
use App\Middleware\Security;
Security::headers();
try {
    if (version_compare(PHP_VERSION, '8.5.12', '<') || preg_match('/RC|alpha|beta|dev/i', PHP_VERSION)) throw new HttpException(503, 'El servidor requiere una versión estable y corregida de PHP 8.5.12 o posterior.');
    Session::start();
    (new Router())->dispatch();
} catch (Throwable $ex) {
    $status = $ex instanceof HttpException ? $ex->status : 500;
    if ($status === 500) error_log((string)$ex);
    http_response_code($status);
    View::render('error', ['title' => 'Solicitud no completada', 'status' => $status, 'message' => $ex instanceof HttpException ? $ex->getMessage() : 'No pudimos completar la operación. Inténtalo nuevamente más tarde.']);
}
