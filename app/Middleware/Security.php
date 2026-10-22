<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\{Session, HttpException};
final class Security
{
    public static function headers(): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, private');
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
    }
    public static function user(): int
    {
        if (empty($_SESSION['user'])) redirect('/login');
        return (int)$_SESSION['user']['id'];
    }
    public static function csrf(): void
    {
        if (!is_string($_POST['_token'] ?? null) || !hash_equals(Session::token(), $_POST['_token'])) throw new HttpException(403, 'La solicitud no es válida. Actualiza la página e inténtalo otra vez.');
    }
}
