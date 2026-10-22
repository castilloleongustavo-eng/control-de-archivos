<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\Database;
if (PHP_SAPI !== 'cli' || getenv('APP_PROFILE') !== 'test' || getenv('DB_NAME') !== 'admosa_file_manager_test') exit('Requiere base y perfil aislados de pruebas.');
$base = $argv[1] ?? 'http://127.0.0.1:8089';
if (!preg_match('#^http://127\.0\.0\.1:\d+$#D', $base)) exit('Solo servidor local.');
$pdo = Database::get();
$pdo->exec('DELETE FROM auth_attempts'); // Solo base test, para repetir la suite.
$dir = ROOT . '/tests/.runtime';
$results = [];
function test(string $label, callable $fn): void {
    global $results;
    try { $fn(); $results[] = [$label,true]; echo "PASS $label\n"; }
    catch (Throwable $e) { $results[] = [$label,false,$e->getMessage()]; echo "FAIL $label: {$e->getMessage()}\n"; }
}
function expect(bool $ok, string $message = 'Aserción fallida'): void { if (!$ok) throw new RuntimeException($message); }
final class Client {
    private string $jar;
    public function __construct(string $name) { $this->jar = ROOT . '/tests/.runtime/' . $name . '.cookies'; if (is_file($this->jar)) unlink($this->jar); }
    public function request(string $path, ?array $data = null, bool $multipart = false): array {
        global $base;
        $ch = curl_init($base . $path);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_COOKIEFILE=>$this->jar,CURLOPT_COOKIEJAR=>$this->jar,CURLOPT_TIMEOUT=>30,CURLOPT_PATH_AS_IS=>true]);
        if ($data !== null) { curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,$multipart ? $data : http_build_query($data)); }
        $raw=curl_exec($ch); if ($raw===false) throw new RuntimeException(curl_error($ch));
        $length=curl_getinfo($ch,CURLINFO_HEADER_SIZE);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        $headers=substr($raw,0,$length);$body=substr($raw,$length);unset($ch);
        return compact('status','headers','body');
    }
    public function token(string $path='/login'): string { $r=$this->request($path); preg_match('/name="_token" value="([a-f0-9]{64})"/',$r['body'],$m); if (!isset($m[1])) throw new RuntimeException('No hay token CSRF en '.$path.' HTTP '.$r['status']); return $m[1]; }
    public function login(string $user,string $password): array { return $this->request('/login',['_token'=>$this->token(),'login'=>$user,'password'=>$password]); }
    public function upload(string $path,string $name,string $mime='application/octet-stream',array $extra=[]): array { return $this->request('/files/upload',array_merge(['_token'=>$this->token('/files'),'file'=>new CURLFile($path,$mime,$name)],$extra),true); }
}
function status(array $r,int $expected): void { expect($r['status']===$expected,'HTTP esperado '.$expected.', recibido '.$r['status']); }
$guest=new Client('guest');$one=new Client('one');$two=new Client('two');
test('Dashboard sin sesión',fn()=>status($guest->request('/'),303));
test('Registro sin CSRF',fn()=>status($guest->request('/register',['username'=>'invalido']),403));
test('Contraseña corta',fn()=>status($guest->request('/register',['_token'=>$guest->token('/register'),'username'=>'qa_short','email'=>'short@example.test','password'=>'1234567','password_confirmation'=>'1234567']),422));
test('Ocho caracteres, no ocho bytes',fn()=>status($guest->request('/register',['_token'=>$guest->token('/register'),'username'=>'qa_utf8','email'=>'utf8@example.test','password'=>'áéíó','password_confirmation'=>'áéíó']),422));
test('Registro duplicado',fn()=>status($guest->request('/register',['_token'=>$guest->token('/register'),'username'=>'prueba1','email'=>'prueba1@example.test','password'=>'OtraSegura!2026','password_confirmation'=>'OtraSegura!2026']),422));
$qa='qa_'.bin2hex(random_bytes(4));
test('Registro válido',fn()=>status($guest->request('/register',['_token'=>$guest->token('/register'),'username'=>$qa,'email'=>$qa.'@example.test','password'=>'PruebaSegura!2026','password_confirmation'=>'PruebaSegura!2026']),303));
test('Contraseña incorrecta',fn()=>status($guest->login('prueba1','incorrecta'),422));
test('SQL Injection en login',fn()=>status($guest->login("' OR 1=1 -- ",'incorrecta'),422));
$before=$one->request('/login');
test('Login prueba1 y regeneración de sesión',function() use($one,$before): void {
    $r=$one->login('prueba1','DemoSegura2026!A');status($r,303);
    expect(str_contains(strtolower($r['headers']),'httponly') && str_contains(strtolower($r['headers']),'samesite=lax'),'Cookies sin atributos esperados');
    preg_match('/admosa_test_session=([^;]+)/',$before['headers'],$a);preg_match('/admosa_test_session=([^;]+)/',$r['headers'],$b);
    expect(isset($a[1],$b[1]) && $a[1]!==$b[1],'No cambió el identificador de sesión');
});
$txt=$dir.'/documento.txt';file_put_contents($txt,"Archivo exclusivo de prueba1.\n");
$pdf=$dir.'/documento.pdf';
$objects=["<< /Type /Catalog /Pages 2 0 R >>","<< /Type /Pages /Kids [3 0 R] /Count 1 >>","<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Resources << >> >>"];
$pdfData="%PDF-1.4\n";$offsets=[0];foreach($objects as$i=>$obj){$offsets[]=strlen($pdfData);$pdfData.=($i+1)." 0 obj\n$obj\nendobj\n";}$xref=strlen($pdfData);$pdfData.="xref\n0 4\n0000000000 65535 f \n";foreach(array_slice($offsets,1)as$o)$pdfData.=sprintf("%010d 00000 n \n",$o);$pdfData.="trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";file_put_contents($pdf,$pdfData);
$png=$dir.'/imagen.png';file_put_contents($png,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jf2kAAAAASUVORK5CYII='));
$name='aislamiento_'.bin2hex(random_bytes(4)).'.pdf';
test('Subir PDF',fn()=>status($one->upload($pdf,$name),303));
$q=$pdo->prepare('SELECT * FROM files WHERE original_name=?');$q->execute([$name]);$row=$q->fetch();
if (!$row) { echo "No se creó el PDF; no se puede ejecutar aislamiento.\n";exit(1); }
$id=$row['id'];$physical=(new App\Services\FileStorage())->path($row);$hash=hash_file('sha256',$physical);
test('Subir imagen PNG',fn()=>status($one->upload($png,'imagen_prueba.png'),303));
test('Ignorar user_id falsificado en carga',function() use($one,$txt,$pdo,$row): void { status($one->upload($txt,'propiedad.txt','text/plain',['user_id'=>'999999']),303);$f=$pdo->query("SELECT user_id FROM files WHERE original_name='propiedad.txt' ORDER BY created_at DESC LIMIT 1")->fetchColumn();expect((int)$f===(int)$row['user_id']); });
test('Archivo ejecutable rechazado',fn()=>status($one->upload($txt,'archivo.php'),422));
test('MIME falsificado rechazado',fn()=>status($one->upload($txt,'falso.png','image/png'),422));
$empty=$dir.'/empty.tmp';file_put_contents($empty,'');
test('Archivo vacío rechazado',fn()=>status($one->upload($empty,'vacio.txt'),422));
$large=$dir.'/large.tmp';file_put_contents($large,str_repeat('a',10485761));
test('Carga superior a 10 MB',fn()=>status($one->upload($large,'grande.txt'),413));
test('Nombre con XSS rechazado',function()use($one,$txt):void{$r=$one->upload($txt,'<script>alert(1)</script>.txt');status($r,422);expect(!str_contains($r['body'],'<script>alert(1)</script>'));});
test('Ver PDF propio inline',function()use($one,$id,$pdfData):void{$r=$one->request('/files/'.$id.'/view');status($r,200);expect($r['body']===$pdfData);expect(str_contains($r['headers'],'Content-Disposition: inline'));expect(str_contains($r['headers'],'X-Content-Type-Options: nosniff'));});
test('Vista previa propia',fn()=>status($one->request('/files/'.$id.'/preview'),200));
test('Descarga propia attachment',function()use($one,$id,$pdfData):void{$r=$one->request('/files/'.$id.'/download');status($r,200);expect($r['body']===$pdfData);expect(str_contains($r['headers'],'Content-Disposition: attachment'));});
test('Búsqueda y filtro por tipo',function()use($one,$name):void{$r=$one->request('/files?q='.rawurlencode($name).'&type=pdf');status($r,200);expect(str_contains($r['body'],$name));expect(!str_contains($r['body'],'imagen_prueba.png'));});
test('SQL Injection en búsqueda',function()use($one,$name):void{$r=$one->request('/files?q='.rawurlencode("' OR 1=1 -- "));status($r,200);expect(!str_contains($r['body'],$name));});
test('Eliminar sin CSRF',fn()=>status($one->request('/files/'.$id.'/delete',[]),403));
test('Eliminar por GET rechazado',fn()=>status($one->request('/files/'.$id.'/delete'),405));
test('Acceso directo a storage bloqueado',fn()=>status($one->request('/storage/private/'.$row['storage_path']),404));
test('Acceso directo a .env bloqueado',function()use($one):void{$r=$one->request('/.env');expect(in_array($r['status'],[403,404],true));expect(!str_contains($r['body'],'DB_PASS='));});
test('Path traversal rechazado',function()use($one):void{$r=$one->request('/files/..%2f..%2f.env/view');expect(in_array($r['status'],[400,403,404],true));expect(!str_contains($r['body'],'DB_PASS='));});
test('Identificador inválido',fn()=>status($one->request('/files/no-valido/view'),404));
test('Logout prueba1',fn()=>status($one->request('/logout',['_token'=>$one->token('/files')]),303));
test('Sesión cerrada no descarga',fn()=>status($one->request('/files/'.$id.'/download'),303));
test('Login prueba2',fn()=>status($two->login('prueba2','DemoSegura2026!B'),303));
test('Aislamiento en listado y estadísticas',function()use($two,$name):void{$r=$two->request('/files?user_id=1');status($r,200);expect(!str_contains($r['body'],$name),'Archivo ajeno visible');});
foreach(['view','preview','download']as$operation)test('IDOR '.$operation.' rechazado',fn()=>status($two->request('/files/'.$id.'/'.$operation.'?user_id='.$row['user_id']),404));
test('IDOR delete con propietario falsificado',fn()=>status($two->request('/files/'.$id.'/delete',['_token'=>$two->token('/files'),'user_id'=>$row['user_id'],'id'=>$id]),404));
test('IDOR delete sin token',fn()=>status($two->request('/files/'.$id.'/delete',['user_id'=>$row['user_id']]),403));
test('Archivo intacto después de ataques',function()use($pdo,$id,$row,$physical,$hash):void{$q=$pdo->prepare('SELECT user_id FROM files WHERE id=?');$q->execute([$id]);expect((int)$q->fetchColumn()===(int)$row['user_id']);expect(is_file($physical)&&hash_file('sha256',$physical)===$hash,'El archivo cambió');});
test('Sesión expirada',function()use($two):void{sleep(5);status($two->request('/files'),303);});
test('Login propietario para eliminar',fn()=>status($one->login('prueba1','DemoSegura2026!A'),303));
test('Eliminación propia',function()use($one,$id,$physical,$pdo):void{status($one->request('/files/'.$id.'/delete',['_token'=>$one->token('/files')]),303);expect(!file_exists($physical));$q=$pdo->prepare('SELECT COUNT(*) FROM files WHERE id=?');$q->execute([$id]);expect((int)$q->fetchColumn()===0);status($one->request('/files/'.$id.'/view'),404);});
test('Limpiar cargas creadas por la suite',function()use($one,$pdo,$row):void{$q=$pdo->prepare('SELECT id FROM files WHERE user_id=?');$q->execute([$row['user_id']]);foreach($q->fetchAll()as$f)status($one->request('/files/'.$f['id'].'/delete',['_token'=>$one->token('/files')]),303);});
test('Limitación de intentos persistente',function()use($guest,$pdo):void{$pdo->exec('DELETE FROM auth_attempts');for($i=0;$i<10;$i++)status($guest->login('inexistente','incorrecta'),422);$r=$guest->login('inexistente','incorrecta');status($r,429);expect(str_contains($r['headers'],'Retry-After: 900'));});
$pdo->exec('DELETE FROM auth_attempts');
foreach([$txt,$pdf,$png,$empty,$large]as$f)if(is_file($f))unlink($f);
$failed=count(array_filter($results,fn($r)=>!$r[1]));
$report=['executed_at'=>date(DATE_ATOM),'php_cli'=>PHP_VERSION,'base_url'=>$base,'database'=>getenv('DB_NAME'),'passed'=>count($results)-$failed,'failed'=>$failed,'results'=>$results];
file_put_contents(ROOT.'/docs/http-results.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL);
echo (count($results)-$failed)." correctas; $failed fallidas.\n";
exit($failed?1:0);
