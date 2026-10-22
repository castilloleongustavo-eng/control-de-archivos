[CmdletBinding()]
param([string]$Xampp = 'C:\xampp', [int]$Port = 8088, [switch]$CheckOnly, [switch]$TestMode)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpDir = Join-Path $projectRoot 'tools\php-8.5.12'
$httpd = Join-Path $Xampp 'apache\bin\httpd.exe'
if (!(Test-Path -LiteralPath (Join-Path $phpDir 'php8apache2_4.dll'))) { throw 'Primero preparar PHP seguro mediante prepare-php.ps1.' }
if (!$CheckOnly -and (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)) { throw "El puerto $Port está ocupado; no se modifica ningún servicio." }
$version = & (Join-Path $phpDir 'php.exe') -r 'echo PHP_VERSION;'
if ($LASTEXITCODE -ne 0 -or $version -ne '8.5.12') { throw 'La versión local no coincide con PHP 8.5.12.' }
$apacheRoot = (Join-Path $Xampp 'apache').Replace('\','/')
$root = $projectRoot.Replace('\','/')
$php = $phpDir.Replace('\','/')
$instance = if ($TestMode) { 'test' } else { 'demo' }
$pidFile = "$root/storage/runtime/apache-$instance.pid"
$conf = @"
ServerRoot "$apacheRoot"
Listen 127.0.0.1:$Port
ServerName 127.0.0.1
PidFile "$pidFile"
ErrorLog "$root/storage/logs/apache-$instance-error.log"
LogLevel warn
ServerTokens Prod
ServerSignature Off
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule mime_module modules/mod_mime.so
LoadModule dir_module modules/mod_dir.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule log_config_module modules/mod_log_config.so
LoadFile "$php/libcrypto-3-x64.dll"
LoadFile "$php/libssl-3-x64.dll"
LoadFile "$php/php8ts.dll"
LoadModule php_module "$php/php8apache2_4.dll"
PHPIniDir "$php"
TypesConfig conf/mime.types
DocumentRoot "$root/public"
DirectoryIndex index.php
<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$root/public">
    Options -Indexes -MultiViews
    AllowOverride None
    Require local
    FallbackResource /index.php
    LimitRequestBody 12582912
    <FilesMatch "\.php$">
        SetHandler application/x-httpd-php
    </FilesMatch>
</Directory>
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
"@
$configPath = Join-Path $projectRoot "storage\runtime\httpd-$instance.conf"
if (Test-Path -LiteralPath $configPath) { Copy-Item -LiteralPath $configPath -Destination ($configPath + '.bak-' + (Get-Date -Format 'yyyyMMddHHmmss')) }
[IO.File]::WriteAllText($configPath, $conf, [Text.UTF8Encoding]::new($false))
# PATH solo del proceso y sus hijos; no cambia el PATH del sistema.
$previousPath = $env:PATH
$savedTestEnv = @{}
try {
    $env:PATH = "$phpDir;$previousPath"
    if ($TestMode) {
        $testEnvPath = Join-Path $projectRoot 'tests\.runtime\.env'
        if (!(Test-Path -LiteralPath $testEnvPath)) { throw 'Preparar la base aislada de pruebas primero.' }
        foreach ($line in Get-Content -LiteralPath $testEnvPath) {
            if ($line -match '^(DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS|SESSION_TIMEOUT|APP_PROFILE)=(.*)$') {
                $key = $Matches[1]; $value = $Matches[2]
                $savedTestEnv[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
                [Environment]::SetEnvironmentVariable($key, $value, 'Process')
            }
        }
    }
    & $httpd -t -f $configPath
    if ($LASTEXITCODE -ne 0) { throw 'Configuración incompatible. No se inicia Apache.' }
    if (!$CheckOnly) {
        Start-Process -FilePath $httpd -ArgumentList @('-f', ('"' + $configPath + '"')) -WindowStyle Hidden -WorkingDirectory $phpDir
        Write-Output "Apache local iniciado: http://127.0.0.1:$Port (verificar HTTP antes de afirmar que funciona)."
    }
} finally {
    $env:PATH = $previousPath
    foreach ($key in $savedTestEnv.Keys) { [Environment]::SetEnvironmentVariable($key, $savedTestEnv[$key], 'Process') }
}
