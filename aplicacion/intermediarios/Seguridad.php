<?php
declare(strict_types=1);
namespace Aplicacion\Intermediarios;
use Aplicacion\Nucleo\{Sesion, ExcepcionHttp};
/** Controles HTTP compartidos; la identidad siempre se deriva de la sesión del servidor. */
final class Seguridad
{
    /** Envía CSP, nosniff y políticas de privacidad antes de producir contenido. */
    public static function cabeceras(): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cache-Control: no-store, private');
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
    }
    /** Devuelve el ID autenticado o redirige al login; ignora IDs enviados por el cliente. */
    public static function usuario(): int
    {
        if (empty($_SESSION['usuario'])) redirigir('/ingresar');
        return (int)$_SESSION['usuario']['id_usuario'];
    }
    /** Verifica el token recibido con comparación segura; rechaza con 403 si no coincide. */
    public static function campoCsrf(): void
    {
        if (!is_string($_POST['_token'] ?? null) || !hash_equals(Sesion::token(), $_POST['_token'])) throw new ExcepcionHttp(403, 'La solicitud no es válida. Actualiza la página e inténtalo otra vez.');
    }
}
