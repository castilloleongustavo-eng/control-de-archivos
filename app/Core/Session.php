<?php
declare(strict_types=1);
namespace App\Core;
final class Session
{
    public static function start(): void
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_save_path(ROOT . '/storage/sessions');
        session_name('admosa_session');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > (int)(getenv('SESSION_TIMEOUT') ?: 1800)) {
            $_SESSION = [];
            session_regenerate_id(true);
            self::flash('Tu sesión expiró por inactividad. Inicia sesión nuevamente.');
        }
        $_SESSION['last_activity'] = time();
    }
    public static function token(): string { return $_SESSION['_token'] ??= bin2hex(random_bytes(32)); }
    public static function flash(string $message, string $type = 'info'): void { $_SESSION['flash'] = [$message, $type]; }
    public static function destroy(): void
    {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        session_destroy();
    }
}
