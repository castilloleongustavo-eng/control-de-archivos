<?php
declare(strict_types=1);
namespace Aplicacion\Controladores;
use Aplicacion\Nucleo\{Sesion, Vista};
use Aplicacion\Modelos\Usuario;
use Aplicacion\Servicios\LimitadorIntentos;
/** Coordina los formularios con Usuario; valida datos y no realiza consultas SQL. */
final class ControladorAutenticacion
{
    /** Recibe ingresar/registrarse del enrutador y muestra el formulario correspondiente. */
    public function formulario(string $modo): void { Vista::mostrar('autenticacion/formulario', ['modo'=>$modo,'titulo'=>$modo==='ingresar'?'Iniciar sesión':'Crear cuenta']); }
    /** Valida usuario, correo y contraseña, y crea una cuenta con hash y restricciones únicas. */
    public function registrar(): void
    {
        LimitadorIntentos::registrarIntento('registro');
        $nombre=trim((string)($_POST['nombre_usuario']??'')); $correo=trim((string)($_POST['correo']??'')); $contrasena=(string)($_POST['contrasena']??'');
        if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/D',$nombre)) $error='El usuario debe tener entre 3 y 40 letras, números o guiones bajos.';
        elseif (strlen($correo)>254 || !filter_var($correo,FILTER_VALIDATE_EMAIL)) $error='Ingresa un correo electrónico válido.';
        elseif (!mb_check_encoding($contrasena,'UTF-8') || mb_strlen($contrasena)<8 || strlen($contrasena)>72) $error='La contraseña debe tener al menos 8 caracteres y un máximo de 72 bytes.';
        elseif ($contrasena!==($_POST['confirmacion_contrasena']??'')) $error='Las contraseñas no coinciden.';
        else {
            try { Usuario::crear($nombre,$correo,$contrasena); }
            catch (\PDOException $excepcion) { if ($excepcion->getCode()!=='23000') throw $excepcion; $error='El usuario o correo electrónico ya está registrado.'; }
        }
        if (isset($error)) { http_response_code(422); Vista::mostrar('autenticacion/formulario',['modo'=>'registrarse','titulo'=>'Crear cuenta','error'=>$error]); return; }
        Sesion::notificar('Cuenta creada. Ya puedes iniciar sesión.','success'); redirigir('/ingresar');
    }
    /** Comprueba el hash, regenera la sesión y guarda únicamente identidad y actividad. */
    public function ingresar(): void
    {
        LimitadorIntentos::registrarIntento('ingreso');
        $acceso=substr(trim((string)($_POST['acceso']??'')),0,254); $contrasena=(string)($_POST['contrasena']??'');
        $usuario=Usuario::buscar($acceso);
        $huella=$usuario['hash_contrasena']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($contrasena,$huella) || !$usuario) { http_response_code(422); Vista::mostrar('autenticacion/formulario',['modo'=>'ingresar','titulo'=>'Iniciar sesión','error'=>'Usuario o contraseña incorrectos.']); return; }
        if (password_needs_rehash($huella,PASSWORD_DEFAULT)) Usuario::actualizarHash((int)$usuario['id_usuario'],$contrasena);
        session_regenerate_id(true);
        $_SESSION=['usuario'=>['id_usuario'=>(int)$usuario['id_usuario'],'nombre_usuario'=>$usuario['nombre_usuario']],'ultima_actividad'=>time()];
        redirigir('/');
    }
    /** Destruye la cookie y la sesión después de que el intermediario valide el POST/CSRF. */
    public function salir(): void { Sesion::destruir(); redirigir('/ingresar'); }
}
