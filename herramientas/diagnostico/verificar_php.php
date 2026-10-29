<?php
/**
 * Archivo: herramientas/diagnostico/verificar_php.php
 * Propósito: Comprueba que PHP y sus extensiones estén preparados.
 * Funcionamiento: Revisa versión, arquitectura y extensiones; con --conexion consulta además las tablas y devuelve un diagnóstico JSON.
 */

declare(strict_types=1);
/** Diagnóstico CLI de versión, arquitectura y extensiones; no contiene credenciales. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
// Construye un resultado verdadero o falso por cada extensión requerida.
$extensiones=[];foreach(['pdo_mysql','fileinfo','mbstring','openssl','zip']as$nombre)$extensiones[$nombre]=extension_loaded($nombre);
// Exige versión estable compatible, arquitectura de 64 bits y todas las extensiones.
$correcto=version_compare(PHP_VERSION,'8.5.12','>=')&&!preg_match('/RC|alpha|beta|dev/i',PHP_VERSION)&&PHP_INT_SIZE===8&&!in_array(false,$extensiones,true);
$resultado=['php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'x64'=>PHP_INT_SIZE===8,'multihilo'=>(bool)PHP_ZTS,'extensiones'=>$extensiones];
// Con --conexion añade una consulta de lectura a cada tabla requerida.
if(in_array('--conexion',$argv,true)){
    // SELECT no modifica usuarios ni documentos. Los errores completos quedan únicamente en el registro externo.
    require dirname(__DIR__,2).'/aplicacion/inicio.php';
    try{
        $conexion=Aplicacion\Nucleo\Conexion::obtener();
        foreach(['usuarios','documentos','intentos_autenticacion'] as $tabla)$conexion->query('SELECT 1 FROM '.$tabla.' LIMIT 1');
        $resultado['conexion']=true;$resultado['datos_externos']=RUTAS['datos'];
    }catch(Throwable $error){error_log((string)$error);$resultado['conexion']=false;$correcto=false;}
}
// Resume el diagnóstico y devuelve un código de salida distinto de cero si hay un problema.
$resultado['correcto']=$correcto;
echo json_encode($resultado,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
exit($correcto?0:1);
