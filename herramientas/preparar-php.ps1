<#
.SYNOPSIS
Prepara el PHP local usado por Apache sin reemplazar la instalacion de XAMPP.
.PARAMETER Instalar
Descarga y extrae la version indicada; sin este parametro solo se muestra el plan.
#>
[CmdletBinding()]
param([switch]$Instalar)
$ErrorActionPreference='Stop'
# La version, la arquitectura y la huella pertenecen al mismo paquete oficial Thread Safe.
$version='8.5.12'
$url='https://downloads.php.net/~windows/releases/archives/php-8.5.12-Win32-vs17-x64.zip'
$huella='660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184'
$destino=Join-Path $PSScriptRoot "ejecucion\php-$version"
$paquete=Join-Path ([IO.Path]::GetTempPath()) "control-de-archivos-php-$version.zip"
if (!$Instalar) {
    Write-Output "PHP $version x64 Thread Safe: $url"
    Write-Output "Destino: $destino"
    Write-Output 'Ejecuta con -Instalar para descargar y preparar PHP. No modifica XAMPP ni el PATH global.'
    exit 0
}
# No se sobrescribe un motor existente, porque Apache podria tener sus DLL cargadas.
if (Test-Path -LiteralPath $destino) { throw 'El PHP local ya existe; compruebalo con herramientas/servidor.ps1 -Accion validar.' }
Invoke-WebRequest -UseBasicParsing -Uri $url -OutFile $paquete
if ((Get-FileHash -LiteralPath $paquete -Algorithm SHA256).Hash.ToLowerInvariant() -ne $huella) { throw 'El SHA-256 no coincide con el paquete oficial; no se instala.' }
$null=New-Item -ItemType Directory -Path (Split-Path $destino -Parent) -Force
Expand-Archive -LiteralPath $paquete -DestinationPath $destino
# extension_dir usa una ruta absoluta para cargar las extensiones correctas incluso desde Apache.
$extensiones=(Join-Path $destino 'ext').Replace('\','/')
$registro=(Join-Path ([IO.Path]::GetTempPath()) 'control-de-archivos-php.log').Replace('\','/')
$ini=@"
[PHP]
extension_dir = "$extensiones"
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
error_log="$registro"
expose_php=Off
allow_url_include=Off
session.use_strict_mode=1
session.upload_progress.enabled=0
date.timezone=America/Guatemala
"@
[IO.File]::WriteAllText((Join-Path $destino 'php.ini'),$ini,[Text.UTF8Encoding]::new($false))
& (Join-Path $destino 'php.exe') -v
if ($LASTEXITCODE -ne 0) { throw 'PHP no inicia. Comprueba Visual C++ Redistributable x64 y la arquitectura de Windows.' }
Write-Output 'PHP local preparado. El siguiente paso es configurar MariaDB.'
