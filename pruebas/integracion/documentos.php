<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/aplicacion/inicio.php';
require dirname(__DIR__).'/soporte/DatosPrueba.php';
if (PHP_SAPI !== 'cli' || getenv('PERFIL_APLICACION') !== 'pruebas' || getenv('BD_NOMBRE') !== 'documentos_pruebas') exit('Requiere base y perfil aislados de pruebas.');
$base = $argv[1] ?? 'http://127.0.0.1:8089';
if (!preg_match('#^http://127\.0\.0\.1:\d+$#D', $base)) exit('Solo servidor local.');
$conexion = new DatosPrueba();
$conexion->limpiarIntentos(); // Solo base test, para repetir la suite.
$directorio = RAIZ . '/pruebas/.runtime';
$resultados = [];
/** Auxiliar de prueba: recibe los datos del caso y lanza una excepción si la condición o respuesta no coincide. */
function probar(string $descripcion, callable $operacion): void {
    global $resultados;
    try { $operacion(); $resultados[] = [$descripcion,true]; echo "PASS $descripcion\n"; }
    catch (Throwable $e) { $resultados[] = [$descripcion,false,$e->getMessage()]; echo "FAIL $descripcion: {$e->getMessage()}\n"; }
}
/** Auxiliar de prueba: recibe los datos del caso y lanza una excepción si la condición o respuesta no coincide. */
function comprobar(bool $correcto, string $mensaje = 'Aserción fallida'): void { if (!$correcto) throw new RuntimeException($mensaje); }
final class Cliente {
    private string $cookies;
    /** Cliente HTTP: recibe datos del caso, conserva cookies y devuelve la respuesta sin seguir redirecciones. */
    public function __construct(string $nombre) { $this->cookies = RAIZ . '/pruebas/.runtime/' . $nombre . '.cookies'; if (is_file($this->cookies)) unlink($this->cookies); }
    /** Cliente HTTP: recibe datos del caso, conserva cookies y devuelve la respuesta sin seguir redirecciones. */
    public function solicitar(string $ruta, ?array $datos = null, bool $multipart = false): array {
        global $base;
        $curl = curl_init($base . $ruta);
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEFILE=>$this->cookies,CURLOPT_COOKIEJAR=>$this->cookies,CURLOPT_TIMEOUT=>30,CURLOPT_PATH_AS_IS=>true]);
        if ($datos !== null) { curl_setopt($curl,CURLOPT_POST,true); curl_setopt($curl,CURLOPT_POSTFIELDS,$multipart ? $datos : http_build_query($datos)); }
        $respuestaCruda=curl_exec($curl); if ($respuestaCruda===false) throw new RuntimeException(curl_error($curl));
        $longitud=curl_getinfo($curl,CURLINFO_HEADER_SIZE);$estado=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
        $cabeceras=substr($respuestaCruda,0,$longitud);$cuerpo=substr($respuestaCruda,$longitud);unset($curl);
        return compact('estado','cabeceras','cuerpo');
    }
    /** Cliente HTTP: recibe datos del caso, conserva cookies y devuelve la respuesta sin seguir redirecciones. */
    public function token(string $ruta='/ingresar'): string { $respuesta=$this->solicitar($ruta); preg_match('/name="_token" value="([a-f0-9]{64})"/',$respuesta['cuerpo'],$coincidencias); if (!isset($coincidencias[1])) throw new RuntimeException('No hay token CSRF en '.$ruta.' HTTP '.$respuesta['estado']); return $coincidencias[1]; }
    /** Cliente HTTP: recibe datos del caso, conserva cookies y devuelve la respuesta sin seguir redirecciones. */
    public function ingresar(string $usuario,string $contrasena): array { return $this->solicitar('/ingresar',['_token'=>$this->token(),'acceso'=>$usuario,'contrasena'=>$contrasena]); }
    /** Cliente HTTP: recibe datos del caso, conserva cookies y devuelve la respuesta sin seguir redirecciones. */
    public function subir(string $ruta,string $nombre,string $tipoMime='application/octet-stream',array $adicionales=[]): array { return $this->solicitar('/documentos/subir',array_merge(['_token'=>$this->token('/documentos'),'documento'=>new CURLFile($ruta,$tipoMime,$nombre)],$adicionales),true); }
}
/** Auxiliar de prueba: recibe los datos del caso y lanza una excepción si la condición o respuesta no coincide. */
function estado(array $respuesta,int $esperado): void { comprobar($respuesta['estado']===$esperado,'HTTP esperado '.$esperado.', recibido '.$respuesta['estado']); }
$visitante=new Cliente('guest');$primero=new Cliente('one');$segundo=new Cliente('two');
probar('Dashboard sin sesión',fn()=>estado($visitante->solicitar('/'),303));
probar('Registro sin CSRF',fn()=>estado($visitante->solicitar('/registrarse',['nombre_usuario'=>'invalido']),403));
probar('Contraseña corta',fn()=>estado($visitante->solicitar('/registrarse',['_token'=>$visitante->token('/registrarse'),'nombre_usuario'=>'qa_short','correo'=>'short@example.test','contrasena'=>'1234567','confirmacion_contrasena'=>'1234567']),422));
probar('Ocho caracteres, no ocho bytes',fn()=>estado($visitante->solicitar('/registrarse',['_token'=>$visitante->token('/registrarse'),'nombre_usuario'=>'qa_utf8','correo'=>'utf8@example.test','contrasena'=>'áéíó','confirmacion_contrasena'=>'áéíó']),422));
probar('Registro duplicado',fn()=>estado($visitante->solicitar('/registrarse',['_token'=>$visitante->token('/registrarse'),'nombre_usuario'=>'prueba1','correo'=>'prueba1@example.test','contrasena'=>'OtraSegura!2026','confirmacion_contrasena'=>'OtraSegura!2026']),422));
$usuarioPrueba='qa_'.bin2hex(random_bytes(4));
probar('Registro válido',fn()=>estado($visitante->solicitar('/registrarse',['_token'=>$visitante->token('/registrarse'),'nombre_usuario'=>$usuarioPrueba,'correo'=>$usuarioPrueba.'@example.test','contrasena'=>'PruebaSegura!2026','confirmacion_contrasena'=>'PruebaSegura!2026']),303));
probar('Contraseña incorrecta',fn()=>estado($visitante->ingresar('prueba1','incorrecta'),422));
probar('SQL Injection en login',fn()=>estado($visitante->ingresar("' OR 1=1 -- ",'incorrecta'),422));
$antes=$primero->solicitar('/ingresar');
probar('Login prueba1 y regeneración de sesión',function() use($primero,$antes): void {
    $respuesta=$primero->ingresar('prueba1','DemoSegura2026!A');estado($respuesta,303);
    comprobar(str_contains(strtolower($respuesta['cabeceras']),'httponly') && str_contains(strtolower($respuesta['cabeceras']),'samesite=lax'),'Cookies sin atributos esperados');
    preg_match('/documentos_pruebas_sesion=([^;]+)/',$antes['cabeceras'],$a);preg_match('/documentos_pruebas_sesion=([^;]+)/',$respuesta['cabeceras'],$b);
    comprobar(isset($a[1],$b[1]) && $a[1]!==$b[1],'No cambió el identificador de sesión');
});
$texto=$directorio.'/documento.txt';file_put_contents($texto,"Archivo exclusivo de prueba1.\n");
$pdf=$directorio.'/documento.pdf';
$objetos=["<< /Type /Catalog /Pages 2 0 R >>","<< /Type /Pages /Kids [3 0 R] /Count 1 >>","<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Resources << >> >>"];
$contenidoPdf="%PDF-1.4\n";$desplazamientos=[0];foreach($objetos as$indice=>$objeto){$desplazamientos[]=strlen($contenidoPdf);$contenidoPdf.=($indice+1)." 0 obj\n$objeto\nendobj\n";}$xref=strlen($contenidoPdf);$contenidoPdf.="xref\n0 4\n0000000000 65535 f \n";foreach(array_slice($desplazamientos,1)as$desplazamiento)$contenidoPdf.=sprintf("%010d 00000 n \n",$desplazamiento);$contenidoPdf.="trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";file_put_contents($pdf,$contenidoPdf);
$png=$directorio.'/imagen.png';file_put_contents($png,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jf2kAAAAASUVORK5CYII='));
$nombre='aislamiento_'.bin2hex(random_bytes(4)).'.pdf';
probar('Subir PDF',fn()=>estado($primero->subir($pdf,$nombre),303));
$fila=$conexion->porNombre($nombre);
if (!$fila) { echo "No se creó el PDF; no se puede ejecutar aislamiento.\n";exit(1); }
$identificador=$fila['id_documento'];$fisico=(new Aplicacion\Servicios\AlmacenDocumentos())->ruta($fila);$huella=hash_file('sha256',$fisico);
probar('Subir imagen PNG',fn()=>estado($primero->subir($png,'imagen_prueba.png'),303));
probar('Ignorar id_usuario falsificado en carga',function() use($primero,$texto,$conexion,$fila): void { estado($primero->subir($texto,'propiedad.txt','text/plain',['id_usuario'=>'999999']),303);$archivo=$conexion->porNombre('propiedad.txt')['id_usuario'];comprobar((int)$archivo===(int)$fila['id_usuario']); });
probar('Archivo ejecutable rechazado',fn()=>estado($primero->subir($texto,'archivo.php'),422));
probar('MIME falsificado rechazado',fn()=>estado($primero->subir($texto,'falso.png','image/png'),422));
$vacio=$directorio.'/empty.tmp';file_put_contents($vacio,'');
probar('Archivo vacío rechazado',fn()=>estado($primero->subir($vacio,'vacio.txt'),422));
$grande=$directorio.'/large.tmp';file_put_contents($grande,str_repeat('a',10485761));
probar('Carga superior a 10 MB',fn()=>estado($primero->subir($grande,'grande.txt'),413));
probar('Nombre con XSS rechazado',function()use($primero,$texto):void{$respuesta=$primero->subir($texto,'<script>alert(1)</script>.txt');estado($respuesta,422);comprobar(!str_contains($respuesta['cuerpo'],'<script>alert(1)</script>'));});
probar('Ver PDF propio inline',function()use($primero,$identificador,$contenidoPdf):void{$respuesta=$primero->solicitar('/documentos/'.$identificador.'/ver');estado($respuesta,200);comprobar($respuesta['cuerpo']===$contenidoPdf);comprobar(str_contains($respuesta['cabeceras'],'Content-Disposition: inline'));comprobar(str_contains($respuesta['cabeceras'],'X-Content-Type-Options: nosniff'));});
probar('Vista previa propia',fn()=>estado($primero->solicitar('/documentos/'.$identificador.'/vista-previa'),200));
probar('Descarga propia attachment',function()use($primero,$identificador,$contenidoPdf):void{$respuesta=$primero->solicitar('/documentos/'.$identificador.'/descargar');estado($respuesta,200);comprobar($respuesta['cuerpo']===$contenidoPdf);comprobar(str_contains($respuesta['cabeceras'],'Content-Disposition: attachment'));});
probar('Búsqueda y filtro por tipo',function()use($primero,$nombre):void{$respuesta=$primero->solicitar('/documentos?busqueda='.rawurlencode($nombre).'&tipo=pdf');estado($respuesta,200);comprobar(str_contains($respuesta['cuerpo'],$nombre));comprobar(!str_contains($respuesta['cuerpo'],'imagen_prueba.png'));});
probar('SQL Injection en búsqueda',function()use($primero,$nombre):void{$respuesta=$primero->solicitar('/documentos?busqueda='.rawurlencode("' OR 1=1 -- "));estado($respuesta,200);comprobar(!str_contains($respuesta['cuerpo'],$nombre));});
probar('Eliminar sin CSRF',fn()=>estado($primero->solicitar('/documentos/'.$identificador.'/eliminar',[]),403));
probar('Eliminar por GET rechazado',fn()=>estado($primero->solicitar('/documentos/'.$identificador.'/eliminar'),405));
probar('Acceso directo a storage bloqueado',fn()=>estado($primero->solicitar('/storage/private/'.$fila['ruta_relativa']),404));
probar('Acceso directo a .env bloqueado',function()use($primero):void{$respuesta=$primero->solicitar('/.env');comprobar(in_array($respuesta['estado'],[403,404],true));comprobar(!str_contains($respuesta['cuerpo'],'BD_CONTRASENA='));});
probar('Path traversal rechazado',function()use($primero):void{$respuesta=$primero->solicitar('/documentos/..%2f..%2f.env/ver');comprobar(in_array($respuesta['estado'],[400,403,404],true));comprobar(!str_contains($respuesta['cuerpo'],'BD_CONTRASENA='));});
probar('Identificador inválido',fn()=>estado($primero->solicitar('/documentos/no-valido/ver'),404));
probar('Logout prueba1',fn()=>estado($primero->solicitar('/salir',['_token'=>$primero->token('/documentos')]),303));
probar('Sesión cerrada no descarga',fn()=>estado($primero->solicitar('/documentos/'.$identificador.'/descargar'),303));
probar('Login prueba2',fn()=>estado($segundo->ingresar('prueba2','DemoSegura2026!B'),303));
probar('Aislamiento en listado y estadísticas',function()use($segundo,$nombre):void{$respuesta=$segundo->solicitar('/documentos?id_usuario=1');estado($respuesta,200);comprobar(!str_contains($respuesta['cuerpo'],$nombre),'Archivo ajeno visible');});
foreach(['ver','vista-previa','descargar']as$operacion)probar('IDOR '.$operacion.' rechazado',fn()=>estado($segundo->solicitar('/documentos/'.$identificador.'/'.$operacion.'?id_usuario='.$fila['id_usuario']),404));
probar('IDOR eliminar con propietario falsificado',fn()=>estado($segundo->solicitar('/documentos/'.$identificador.'/eliminar',['_token'=>$segundo->token('/documentos'),'id_usuario'=>$fila['id_usuario'],'id_documento'=>$identificador]),404));
probar('IDOR eliminar sin token',fn()=>estado($segundo->solicitar('/documentos/'.$identificador.'/eliminar',['id_usuario'=>$fila['id_usuario']]),403));
probar('Archivo intacto después de ataques',function()use($conexion,$identificador,$fila,$fisico,$huella):void{comprobar($conexion->propietario($identificador)===(int)$fila['id_usuario']);comprobar(is_file($fisico)&&hash_file('sha256',$fisico)===$huella,'El archivo cambió');});
probar('Sesión expirada',function()use($segundo):void{sleep(5);estado($segundo->solicitar('/documentos'),303);});
probar('Login propietario para eliminar',fn()=>estado($primero->ingresar('prueba1','DemoSegura2026!A'),303));
probar('Eliminación propia',function()use($primero,$identificador,$fisico,$conexion):void{estado($primero->solicitar('/documentos/'.$identificador.'/eliminar',['_token'=>$primero->token('/documentos')]),303);comprobar(!file_exists($fisico));comprobar($conexion->cantidad($identificador)===0);estado($primero->solicitar('/documentos/'.$identificador.'/ver'),404);});
probar('Limpiar cargas creadas por la suite',function()use($primero,$conexion,$fila):void{foreach($conexion->delUsuario((int)$fila['id_usuario'])as$archivo)estado($primero->solicitar('/documentos/'.$archivo['id_documento'].'/eliminar',['_token'=>$primero->token('/documentos')]),303);});
probar('Limitación de intentos persistente',function()use($visitante,$conexion):void{$conexion->limpiarIntentos();for($indice=0;$indice<10;$indice++)estado($visitante->ingresar('inexistente','incorrecta'),422);$respuesta=$visitante->ingresar('inexistente','incorrecta');estado($respuesta,429);comprobar(str_contains($respuesta['cabeceras'],'Retry-After: 900'));});
$conexion->limpiarIntentos();
foreach([$texto,$pdf,$png,$vacio,$grande]as$archivo)if(is_file($archivo))unlink($archivo);
$fallidas=count(array_filter($resultados,fn($respuesta)=>!$respuesta[1]));
$informe=['fecha_ejecucion'=>date(DATE_ATOM),'php_cli'=>PHP_VERSION,'url_base'=>$base,'base_datos'=>getenv('BD_NOMBRE'),'correctas'=>count($resultados)-$fallidas,'fallidas'=>$fallidas,'resultados'=>$resultados];
file_put_contents(RAIZ.'/documentacion/evidencias/resultados-http.json',json_encode($informe,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL);
echo (count($resultados)-$fallidas)." correctas; $fallidas fallidas.\n";
exit($fallidas?1:0);
