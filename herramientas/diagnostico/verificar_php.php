<?php
declare(strict_types=1);
/** Diagnóstico CLI de versión, arquitectura y extensiones; no contiene credenciales. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$extensiones=[];foreach(['pdo_mysql','fileinfo','mbstring','openssl','zip']as$nombre)$extensiones[$nombre]=extension_loaded($nombre);
$correcto=version_compare(PHP_VERSION,'8.5.12','>=')&&!preg_match('/RC|alpha|beta|dev/i',PHP_VERSION)&&PHP_INT_SIZE===8&&!in_array(false,$extensiones,true);
echo json_encode(['php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'x64'=>PHP_INT_SIZE===8,'multihilo'=>(bool)PHP_ZTS,'extensiones'=>$extensiones,'correcto'=>$correcto],JSON_PRETTY_PRINT).PHP_EOL;
exit($correcto?0:1);
