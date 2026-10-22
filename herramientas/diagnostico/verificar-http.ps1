[CmdletBinding()]
param([string]$UrlBase='http://127.0.0.1:8088')
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'comun.ps1')
if(!([Uri]$UrlBase).IsLoopback){throw 'Solo se permite verificación local.'}
$nombre='__verificacion_'+[Guid]::NewGuid().ToString('N')+'.php'
$sonda=Join-Path $raizProyecto ('publico\'+$nombre)
$codigo=@'
<?php
if(!in_array($_SERVER['REMOTE_ADDR'],['127.0.0.1','::1'],true)){http_response_code(404);exit;}
header('Content-Type: application/json');
$extensiones=[];foreach(['pdo_mysql','fileinfo','mbstring','openssl','zip']as$nombre)$extensiones[$nombre]=extension_loaded($nombre);
echo json_encode(['php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'x64'=>PHP_INT_SIZE===8,'multihilo'=>(bool)PHP_ZTS,'extensiones'=>$extensiones,'carga_maxima'=>ini_get('upload_max_filesize'),'cuerpo_maximo'=>ini_get('post_max_size')]);
'@
try{
    [IO.File]::WriteAllText($sonda,$codigo,$codificacionUtf8)
    $resultado=Invoke-RestMethod -Uri ($UrlBase.TrimEnd('/')+'/'+$nombre) -TimeoutSec 15
    if($resultado.php -ne '8.5.12' -or $resultado.sapi -ne 'apache2handler' -or !$resultado.x64 -or !$resultado.multihilo -or @($resultado.extensiones.PSObject.Properties|Where-Object{!$_.Value}).Count){throw 'Runtime HTTP no válido.'}
    $resultado|ConvertTo-Json -Depth 4
}finally{if(Test-Path -LiteralPath $sonda){Remove-Item -LiteralPath $sonda}}
