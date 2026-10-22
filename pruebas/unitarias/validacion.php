<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/aplicacion/inicio.php';
use Aplicacion\Nucleo\ExcepcionHttp;
use Aplicacion\Validadores\ValidadorDocumento;
use Aplicacion\Servicios\AlmacenDocumentos;
if (PHP_SAPI !== 'cli' || getenv('PERFIL_APLICACION') !== 'pruebas') exit('Solo perfil de pruebas CLI.');
$directorio = RAIZ . '/pruebas/.runtime';
$correctas = 0; $fallidas = 0;
/** Auxiliar de prueba: recibe los datos del caso y lanza una excepción si la condición o respuesta no coincide. */
function verificar(string $descripcion, callable $prueba): void {
    global $correctas,$fallidas;
    try { $prueba(); $correctas++; echo "PASS $descripcion\n"; } catch (Throwable $excepcion) { $fallidas++; echo "FAIL $descripcion: {$excepcion->getMessage()}\n"; }
}
/** Auxiliar de prueba: recibe los datos del caso y lanza una excepción si la condición o respuesta no coincide. */
function rechazar(callable $operacion, int $estado): void {
    try { $operacion(); } catch (ExcepcionHttp $e) { if ($e->estado === $estado) return; throw $e; }
    throw new RuntimeException('Aceptó contenido que debía rechazar');
}
$validador = new ValidadorDocumento();
$documento = $directorio . '/validation.tmp';
file_put_contents($documento, "Documento de prueba UTF-8.\n");
verificar('TXT legítimo', fn() => $validador->inspeccionar($documento, 'notas.txt'));
foreach (['../notas.txt','notas.php.txt','notas..txt','notas.txt.','aux.exe',"mal\r\n.txt",'carpeta\\notas.txt'] as $nombre) verificar('Nombre rechazado: '.json_encode($nombre), fn() => rechazar(fn() => $validador->inspeccionar($documento,$nombre),422));
verificar('MIME falsificado', fn() => rechazar(fn() => $validador->inspeccionar($documento,'falso.png'),422));
file_put_contents($documento,''); verificar('Archivo vacío', fn() => rechazar(fn() => $validador->inspeccionar($documento,'vacio.txt'),422));
$linea = "Documento valido de pruebas, texto y numeros 12345.\n";
file_put_contents($documento,substr(str_repeat($linea,(int)ceil(ValidadorDocumento::LIMITE_BYTES / strlen($linea))),0,ValidadorDocumento::LIMITE_BYTES));
verificar('Límite exacto 10 MB aceptado', fn() => $validador->inspeccionar($documento,'limite.txt'));
file_put_contents($documento,'a',FILE_APPEND);
verificar('10 MB + 1 byte rechazado', fn() => rechazar(fn() => $validador->inspeccionar($documento,'grande.txt'),413));
file_put_contents($documento,'<?php echo 1;');verificar('Script disfrazado', fn() => rechazar(fn() => $validador->inspeccionar($documento,'codigo.txt'),422));
$office = $directorio . '/office.tmp';
foreach (['docx'=>'word/document.xml','xlsx'=>'xl/workbook.xml'] as $extension=>$principal) {
    $z=new ZipArchive();$z->open($office,ZipArchive::CREATE|ZipArchive::OVERWRITE);$z->addFromString('[Content_Types].xml','<Types/>');$z->addFromString('_rels/.rels','<Relationships/>');$z->addFromString($principal,'<document/>');$z->close();
    verificar('Contenedor '.$extension, fn() => $validador->inspeccionar($office,'documento.'.$extension));
    $z=new ZipArchive();$z->open($office);$z->addFromString('word/vbaProject.bin','macro');$z->close();
    verificar('Macro bloqueada '.$extension, fn() => rechazar(fn() => $validador->inspeccionar($office,'documento.'.$extension),422));
}
verificar('Ruta de metadato manipulada', fn() => rechazar(fn() => (new AlmacenDocumentos())->ruta(['id_usuario'=>1,'nombre_almacenado'=>str_repeat('a',64),'ruta_relativa'=>'../../.env']),404));
verificar('Escape HTML', function (): void { if (escapar('<script>"&') !== '&lt;script&gt;&quot;&amp;') throw new RuntimeException('Escape inválido'); });
unlink($documento);unlink($office);
echo "$correctas correctas; $fallidas fallidas.\n";
file_put_contents(RAIZ . '/documentacion/evidencias/resultados-unitarias.json', json_encode(['fecha_ejecucion'=>date(DATE_ATOM),'php'=>PHP_VERSION,'correctas'=>$correctas,'fallidas'=>$fallidas], JSON_PRETTY_PRINT) . PHP_EOL);
exit($fallidas ? 1 : 0);
