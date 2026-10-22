<?php
declare(strict_types=1);
namespace Aplicacion\Nucleo;
/** Gestiona sesiones privadas con protección contra fijación y caducidad por inactividad. */
final class Sesion
{
    /** Inicia la sesión HTTP usando rutas centralizadas y cookies HttpOnly/SameSite. */
    public static function iniciar(): void
    {
        ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
        session_save_path(RUTAS['sesiones']);
        session_name(getenv('PERFIL_APLICACION')==='pruebas'?'documentos_pruebas_sesion':'documentos_sesion');
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
        session_start();
        if (isset($_SESSION['ultima_actividad']) && time()-$_SESSION['ultima_actividad']>(int)(getenv('SESION_INACTIVIDAD')?:1800)) { $_SESSION=[]; session_regenerate_id(true); self::notificar('Tu sesión expiró por inactividad. Inicia sesión nuevamente.'); }
        $_SESSION['ultima_actividad']=time();
    }
    /** Devuelve un token CSRF aleatorio, vinculado a la sesión y renovado al ingresar. */
    public static function token(): string { return $_SESSION['_token']??=bin2hex(random_bytes(32)); }
    /** Almacena un mensaje temporal para que Vista lo consuma en la próxima respuesta. */
    public static function notificar(string $mensaje,string $tipo='info'): void { $_SESSION['aviso']=[$mensaje,$tipo]; }
    /** Destruye datos y cookie; conserva los atributos de seguridad al expirar la cookie. */
    public static function destruir(): void
    {
        $_SESSION=[]; $parametros=session_get_cookie_params();
        setcookie(session_name(),'',['expires'=>time()-3600,'path'=>$parametros['path'],'secure'=>$parametros['secure'],'httponly'=>true,'samesite'=>'Lax']); session_destroy();
    }
}
