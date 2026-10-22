<?php
declare(strict_types=1);
namespace Aplicacion\Nucleo;
use Aplicacion\Controladores\{ControladorAutenticacion,ControladorDocumentos};
use Aplicacion\Intermediarios\Seguridad;
/** Despacha rutas locales y aplica autenticación/CSRF antes de cualquier controlador. */
final class Enrutador
{
    /** Interpreta método y ruta HTTP; rechaza identificadores, métodos y cuerpos inválidos. */
    public function despachar(): void
    {
        $ruta=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $metodo=$_SERVER['REQUEST_METHOD'];
        if ($metodo==='POST' && (int)($_SERVER['CONTENT_LENGTH']??0)>PARAMETROS_SEGURIDAD['limite_cuerpo_bytes']) throw new ExcepcionHttp(413,'La solicitud supera el tamaño permitido. Máximo 10 MB por archivo.');
        if ($ruta==='/' || str_starts_with((string)$ruta,'/documentos') || $ruta==='/salir') Seguridad::usuario();
        if ($metodo==='POST') Seguridad::campoCsrf();
        $autenticacion=new ControladorAutenticacion(); $documentos=new ControladorDocumentos();
        if ($metodo==='GET') {
            switch ($ruta) {
                case '/ingresar': $autenticacion->formulario('ingresar'); return;
                case '/registrarse': $autenticacion->formulario('registrarse'); return;
                case '/': $documentos->indice(true); return;
                case '/documentos': $documentos->indice(); return;
                case '/documentos/subir': $documentos->formularioCarga(); return;
            }
        }
        if ($metodo==='POST') {
            switch ($ruta) {
                case '/ingresar': $autenticacion->ingresar(); return;
                case '/registrarse': $autenticacion->registrar(); return;
                case '/salir': $autenticacion->salir(); return;
                case '/documentos/subir': $documentos->subir(); return;
            }
        }
        if (preg_match('#^/documentos/([a-f0-9]{32})/(ver|descargar|vista-previa|eliminar)$#D',(string)$ruta,$coincidencias)) {
            $operacion=$coincidencias[2]; $identificador=$coincidencias[1];
            if ($operacion==='eliminar' && $metodo==='POST') { $documentos->eliminar($identificador); return; }
            if ($operacion!=='eliminar' && $metodo==='GET') { if ($operacion==='vista-previa') $documentos->vistaPrevia($identificador); else $documentos->transmitir($identificador,$operacion==='descargar'); return; }
            header('Allow: '.($operacion==='eliminar'?'POST':'GET')); throw new ExcepcionHttp(405,'Método no permitido.');
        }
        throw new ExcepcionHttp(404,'La página o el archivo no está disponible.');
    }
}
