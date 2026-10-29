<?php
/**
 * Archivo: herramientas/instalacion/crear_base_datos.php
 * Propósito: Recibe opciones de consola para preparar MariaDB.
 * Funcionamiento: Lee credenciales administrativas desde stdin o configuración local y llama a Instalador con el modo solicitado.
 */

declare(strict_types=1);
/** Entrada CLI para configurar MariaDB; las credenciales locales quedan fuera del directorio público. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/aplicacion/inicio.php';
require RAIZ.'/base_datos/migraciones/Instalador.php';
// Exige elegir entre crear una base nueva o configurar una existente.
if(!in_array('--crear',$argv,true)&&!in_array('--configurar',$argv,true)){fwrite(STDERR,"Requiere --crear o --configurar.\n");exit(1);}
try{
    if(in_array('--desde-xampp',$argv,true)){
        // Solo usa valores realmente configurados; nunca presupone usuario ni contraseña vacía.
        $ruta='C:/xampp/phpMyAdmin/config.inc.php';
        if(!is_file($ruta))throw new RuntimeException('Utiliza herramientas/configurar.ps1 para introducir las credenciales localmente.');
        $cfg=[];require $ruta;$servidor=$cfg['Servers'][1]??[];
        if(($servidor['auth_type']??'')!=='config'||!array_key_exists('user',$servidor)||!array_key_exists('password',$servidor))throw new RuntimeException('phpMyAdmin no tiene credenciales configuradas; utiliza el asistente local.');
        $administrador=['servidor'=>'127.0.0.1','puerto'=>3306,'usuario'=>$servidor['user'],'contrasena'=>$servidor['password']];
    }else $administrador=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);
    // Delega la preparación y escritura de credenciales a Instalador.
    \BaseDatos\Instalador::ejecutar($administrador,in_array('--crear',$argv,true));
}
catch(Throwable $excepcion){error_log((string)$excepcion);fwrite(STDERR,"No se completó la preparación: ".$excepcion->getMessage()."\n");exit(1);}
