[CmdletBinding()]
param([switch]$Install)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$target = Join-Path $projectRoot 'tools\php-8.5.12'
$url = 'https://downloads.php.net/~windows/releases/archives/php-8.5.12-Win32-vs17-x64.zip'
$expected = '660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184'
if (!$Install) {
    Write-Output "Plan: descargar PHP 8.5.12 VS17 x64 Thread Safe desde $url"
    Write-Output "Verificar SHA256 $expected y extraer exclusivamente en $target"
    Write-Output 'No modifica PATH, XAMPP, servicios, registro ni DLL existentes. Ejecutar con -Install solo tras autorizar la instalación local.'
    exit 0
}
if (Test-Path -LiteralPath $target) { throw 'El destino ya existe. Inspeccionarlo antes de reutilizarlo; no se reemplaza automáticamente.' }
$null = New-Item -ItemType Directory -Force -Path (Join-Path $projectRoot 'tools')
$archive = Join-Path $projectRoot 'tools\php-8.5.12.zip'
Invoke-WebRequest -Uri $url -OutFile $archive -UseBasicParsing
if ((Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash.ToLowerInvariant() -ne $expected) { throw 'SHA256 incorrecto. No se extrae ni ejecuta el paquete.' }
Expand-Archive -LiteralPath $archive -DestinationPath $target
$ini = @"
[PHP]
extension_dir = "$($target.Replace('\','/'))/ext"
extension=pdo_mysql
extension=fileinfo
extension=mbstring
extension=openssl
extension=zip
extension=curl
file_uploads=On
upload_max_filesize=10M
post_max_size=12M
max_file_uploads=1
memory_limit=128M
max_execution_time=30
display_errors=Off
log_errors=On
expose_php=Off
allow_url_include=Off
session.use_strict_mode=1
session.upload_progress.enabled=0
date.timezone=America/Guatemala
"@
[IO.File]::WriteAllText((Join-Path $target 'php.ini'), $ini, [Text.UTF8Encoding]::new($false))
& (Join-Path $target 'php.exe') -v
if ($LASTEXITCODE -ne 0) { throw 'PHP no inicia. Comprobar Visual C++ Redistributable VS2015-2022 x64 oficial; no copiar DLL de otras versiones.' }
& (Join-Path $target 'php.exe') --ini
& (Join-Path $target 'php.exe') -m
