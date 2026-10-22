<?php
declare(strict_types=1);
/** Entrada CLI explícita. Las credenciales administrativas solo se reciben por stdin. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/aplicacion/inicio.php';
require RAIZ.'/base_datos/migraciones/Instalador.php';
if(!in_array('--crear',$argv,true)){fwrite(STDERR,"Requiere --crear y credenciales JSON por stdin.\n");exit(1);}
try{\BaseDatos\Instalador::ejecutar(json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR),in_array('--pruebas',$argv,true),in_array('--migrar',$argv,true),in_array('--continuar',$argv,true));}
catch(Throwable $excepcion){error_log((string)$excepcion);fwrite(STDERR,"No se completó la preparación: ".$excepcion->getMessage()."\n");exit(1);}
