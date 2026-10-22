<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\HttpException;
use App\Services\{UploadValidator, FileStorage};
if (PHP_SAPI !== 'cli' || getenv('APP_PROFILE') !== 'test') exit('Solo perfil de pruebas CLI.');
$dir = ROOT . '/tests/.runtime';
$passed = 0; $failed = 0;
function check(string $label, callable $test): void {
    global $passed,$failed;
    try { $test(); $passed++; echo "PASS $label\n"; } catch (Throwable $ex) { $failed++; echo "FAIL $label: {$ex->getMessage()}\n"; }
}
function reject(callable $fn, int $status): void {
    try { $fn(); } catch (HttpException $e) { if ($e->status === $status) return; throw $e; }
    throw new RuntimeException('Aceptó contenido que debía rechazar');
}
$validator = new UploadValidator();
$file = $dir . '/validation.tmp';
file_put_contents($file, "Documento de prueba UTF-8.\n");
check('TXT legítimo', fn() => $validator->inspect($file, 'notas.txt'));
foreach (['../notas.txt','notas.php.txt','notas..txt','notas.txt.','aux.exe',"mal\r\n.txt",'carpeta\\notas.txt'] as $name) check('Nombre rechazado: '.json_encode($name), fn() => reject(fn() => $validator->inspect($file,$name),422));
check('MIME falsificado', fn() => reject(fn() => $validator->inspect($file,'falso.png'),422));
file_put_contents($file,''); check('Archivo vacío', fn() => reject(fn() => $validator->inspect($file,'vacio.txt'),422));
$line = "Documento valido de pruebas, texto y numeros 12345.\n";
file_put_contents($file,substr(str_repeat($line,(int)ceil(UploadValidator::MAX / strlen($line))),0,UploadValidator::MAX));
check('Límite exacto 10 MB aceptado', fn() => $validator->inspect($file,'limite.txt'));
file_put_contents($file,'a',FILE_APPEND);
check('10 MB + 1 byte rechazado', fn() => reject(fn() => $validator->inspect($file,'grande.txt'),413));
file_put_contents($file,'<?php echo 1;');check('Script disfrazado', fn() => reject(fn() => $validator->inspect($file,'codigo.txt'),422));
$office = $dir . '/office.tmp';
foreach (['docx'=>'word/document.xml','xlsx'=>'xl/workbook.xml'] as $ext=>$main) {
    $z=new ZipArchive();$z->open($office,ZipArchive::CREATE|ZipArchive::OVERWRITE);$z->addFromString('[Content_Types].xml','<Types/>');$z->addFromString('_rels/.rels','<Relationships/>');$z->addFromString($main,'<document/>');$z->close();
    check('Contenedor '.$ext, fn() => $validator->inspect($office,'documento.'.$ext));
    $z=new ZipArchive();$z->open($office);$z->addFromString('word/vbaProject.bin','macro');$z->close();
    check('Macro bloqueada '.$ext, fn() => reject(fn() => $validator->inspect($office,'documento.'.$ext),422));
}
check('Ruta de metadato manipulada', fn() => reject(fn() => (new FileStorage())->path(['user_id'=>1,'stored_name'=>str_repeat('a',64),'storage_path'=>'../../.env']),404));
check('Escape HTML', function (): void { if (e('<script>"&') !== '&lt;script&gt;&quot;&amp;') throw new RuntimeException('Escape inválido'); });
unlink($file);unlink($office);
echo "$passed correctas; $failed fallidas.\n";
file_put_contents(ROOT . '/docs/unit-results.json', json_encode(['executed_at'=>date(DATE_ATOM),'php'=>PHP_VERSION,'passed'=>$passed,'failed'=>$failed], JSON_PRETTY_PRINT) . PHP_EOL);
exit($failed ? 1 : 0);
