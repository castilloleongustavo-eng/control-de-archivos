<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require $file;
    }
});
if (is_file(ROOT . '/.env')) {
    foreach (file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) putenv(trim($key) . '=' . trim($value));
    }
}
date_default_timezone_set('America/Guatemala');
define('STORAGE_ROOT', getenv('APP_PROFILE') === 'test' ? ROOT . '/tests/.runtime/storage' : ROOT . '/storage');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_ROOT . '/logs/php.log');
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { return '<input type="hidden" name="_token" value="' . e(\App\Core\Session::token()) . '">'; }
function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
function bytes(int $size): string { return $size >= 1048576 ? number_format($size / 1048576, 1) . ' MB' : number_format($size / 1024, 1) . ' KB'; }
