<?php
declare(strict_types=1);
/** Diagnóstico CLI de versión, arquitectura y extensiones; no contiene credenciales. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$extensiones=[];foreach(['pdo_mysql','fileinfo','mbstring','openssl','zip']as$nombre)$extensiones[$nombre]=extension_loaded($nombre);
$correcto=version_compare(PHP_VERSION,'8.5.12','>=')&&!preg_match('/RC|alpha|beta|dev/i',PHP_VERSION)&&PHP_INT_SIZE===8&&!in_array(false,$extensiones,true);
$resultado=['php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'x64'=>PHP_INT_SIZE===8,'multihilo'=>(bool)PHP_ZTS,'extensiones'=>$extensiones];
if(in_array('--conexion',$argv,true)){
    // SELECT no modifica usuarios ni documentos. Los errores completos quedan únicamente en el registro externo.
    require dirname(__DIR__,2).'/aplicacion/inicio.php';
    try{
        $conexion=Aplicacion\Nucleo\Conexion::obtener();
        foreach(['usuarios','documentos','intentos_autenticacion'] as $tabla)$conexion->query('SELECT 1 FROM '.$tabla.' LIMIT 1');
        $resultado['conexion']=true;$resultado['datos_externos']=RUTAS['datos'];
    }catch(Throwable $error){error_log((string)$error);$resultado['conexion']=false;$correcto=false;}
}
$resultado['correcto']=$correcto;
echo json_encode($resultado,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($correcto?0:1);
