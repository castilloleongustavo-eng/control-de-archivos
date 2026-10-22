[CmdletBinding()]
param([string]$BaseUrl = 'http://127.0.0.1:8088')
$ErrorActionPreference = 'Stop'
$parsed = [Uri]$BaseUrl
if (!$parsed.IsLoopback) { throw 'Solo se permite verificar el servidor local.' }
$projectRoot = Split-Path $PSScriptRoot -Parent
$name = '__runtime_' + [Guid]::NewGuid().ToString('N') + '.php'
$probe = Join-Path $projectRoot ('public\' + $name)
$source = @'
<?php
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1','::1'], true)) { http_response_code(404); exit; }
header('Content-Type: application/json');
$extensions=[];
foreach(['pdo_mysql','fileinfo','mbstring','openssl','zip'] as $e) $extensions[$e]=extension_loaded($e);
echo json_encode(['php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'x64'=>PHP_INT_SIZE===8,'thread_safe'=>(bool)PHP_ZTS,'extensions'=>$extensions,'upload_max_filesize'=>ini_get('upload_max_filesize'),'post_max_size'=>ini_get('post_max_size')]);
'@
try {
    [IO.File]::WriteAllText($probe, $source, [Text.UTF8Encoding]::new($false))
    $result = Invoke-RestMethod -Uri ($BaseUrl.TrimEnd('/') + '/' + $name) -TimeoutSec 15
    $result | ConvertTo-Json -Depth 4
    if ($result.php -ne '8.5.12' -or $result.sapi -ne 'apache2handler' -or !$result.x64 -or !$result.thread_safe -or @($result.extensions.PSObject.Properties | Where-Object { !$_.Value }).Count -gt 0) { throw 'El runtime HTTP no cumple los requisitos.' }
} finally {
    if (Test-Path -LiteralPath $probe) { Remove-Item -LiteralPath $probe }
}
