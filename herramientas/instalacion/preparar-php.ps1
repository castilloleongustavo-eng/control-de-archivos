[CmdletBinding()]
param([switch]$Instalar)
$ErrorActionPreference = 'Stop'
$raizProyecto = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$destino = Join-Path $raizProyecto 'herramientas\ejecucion\php-8.5.12'
$url = 'https://downloads.php.net/~windows/releases/archives/php-8.5.12-Win32-vs17-x64.zip'
$huellaEsperada = '660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184'
if (!$Instalar) {
    Write-Output "Plan: descargar PHP 8.5.12 VS17 x64 Thread Safe desde $url"
    Write-Output "Verificar SHA256 $huellaEsperada y extraer exclusivamente en $destino"
    Write-Output 'No modifica PATH, XAMPP, servicios, registro ni DLL existentes. Ejecutar con -Install solo tras autorizar la instalación local.'
    exit 0
}
if (Test-Path -LiteralPath $destino) { throw 'El destino ya existe. Inspeccionarlo antes de reutilizarlo; no se reemplaza automáticamente.' }
$null = New-Item -ItemType Directory -Force -Path (Join-Path $raizProyecto 'herramientas\ejecucion')
$paquete = Join-Path $raizProyecto 'herramientas\ejecucion\php-8.5.12.zip'
Invoke-WebRequest -Uri $url -OutFile $paquete -UseBasicParsing
if ((Get-FileHash -LiteralPath $paquete -Algorithm SHA256).Hash.ToLowerInvariant() -ne $huellaEsperada) { throw 'SHA256 incorrecto. No se extrae ni ejecuta el paquete.' }
Expand-Archive -LiteralPath $paquete -DestinationPath $destino
$configuracionIni = @"
[PHP]
extension_dir = "$($destino.Replace('\','/'))/ext"
extension=pdo_mysql
extension=fileinfo
extension=mbstring
extension=openssl
extension=zip
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
[IO.File]::WriteAllText((Join-Path $destino 'php.ini'), $configuracionIni, [Text.UTF8Encoding]::new($false))
& (Join-Path $destino 'php.exe') -v
if ($LASTEXITCODE -ne 0) { throw 'PHP no inicia. Comprobar Visual C++ Redistributable VS2015-2022 x64 oficial; no copiar DLL de otras versiones.' }
& (Join-Path $destino 'php.exe') --ini
& (Join-Path $destino 'php.exe') -m
