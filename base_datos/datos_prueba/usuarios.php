<?php
declare(strict_types=1);
/** Crea únicamente cuentas demostrativas, por CLI y con autorización explícita --demostracion. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/aplicacion/inicio.php';
if(!in_array('--demostracion',$argv,true)){fwrite(STDERR,"Uso: php base_datos/datos_prueba/usuarios.php --demostracion\n");exit(1);}
foreach(['usuario1'=>'Pruebas12026','usuario2'=>'Pruebas22026']as$nombre=>$contrasena){
    $correo=$nombre.'@example.test';
    if(\Aplicacion\Modelos\Usuario::buscar($nombre)||\Aplicacion\Modelos\Usuario::buscar($correo)){echo "$nombre ya existe; no se modifica.\n";continue;}
    \Aplicacion\Modelos\Usuario::crear($nombre,$correo,$contrasena);echo "$nombre creado.\n";
}
