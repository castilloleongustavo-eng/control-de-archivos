<#
.SYNOPSIS
Inicia, detiene o valida la instancia Apache local del proyecto.
.PARAMETER Accion
iniciar abre el servidor; detener verifica su PID; estado consulta el proceso; validar comprueba el entorno.
.PARAMETER Xampp
Directorio de XAMPP que proporciona Apache. El motor PHP se toma de herramientas/ejecucion.
.PARAMETER Puerto
Puerto HTTP local. El valor predeterminado es 8088.
#>
[CmdletBinding()]
param([ValidateSet('iniciar','detener','estado','validar')][string]$Accion='iniciar',[string]$Xampp='C:\xampp',[int]$Puerto=8088)
$ErrorActionPreference='Stop'
$directorioPhp=Join-Path $PSScriptRoot 'ejecucion\php-8.5.12'
$php=Join-Path $directorioPhp 'php.exe'
if (!(Test-Path -LiteralPath $php)) { throw 'Falta el PHP local: herramientas/ejecucion/php-8.5.12/php.exe.' }
# PHP lee configuracion/configuracion.env; Apache y las cargas escriben sus datos fuera del proyecto.
$rutas=(& $php (Join-Path $PSScriptRoot 'diagnostico\rutas.php')) | ConvertFrom-Json
if ($LASTEXITCODE -ne 0 -or !$rutas.temporal) { throw 'No se pudieron resolver las rutas externas.' }
$configuracion=Join-Path $rutas.temporal 'apache-local.conf'
$archivoPid=Join-Path $rutas.temporal 'apache-local.pid'
$proceso=$null
if (Test-Path -LiteralPath $archivoPid) {
    $identificador=0
    if ([int]::TryParse(([IO.File]::ReadAllText($archivoPid)).Trim(),[ref]$identificador)) {
        $candidato=Get-CimInstance Win32_Process -Filter "ProcessId=$identificador"
        if ($candidato -and $candidato.Name -eq 'httpd.exe' -and $candidato.CommandLine -and
            $candidato.CommandLine.Replace('\','/').ToLowerInvariant().Contains($configuracion.Replace('\','/').ToLowerInvariant())) { $proceso=$candidato }
    }
}
if ($Accion -eq 'estado') {
    if ($proceso) { Write-Output "Servidor activo: http://127.0.0.1:$Puerto/ingresar" }
    else { Write-Output 'Servidor del proyecto detenido.' }
    exit 0
}
if ($Accion -eq 'detener') {
    # Se verifica el PID y su configuración antes de detener solo esta instancia, nunca el Apache global de XAMPP.
    if (!$proceso) { Write-Output 'La instancia ya esta detenida.'; exit 0 }
    $hijos=@(Get-CimInstance Win32_Process -Filter "ParentProcessId=$($proceso.ProcessId)" | Where-Object Name -eq 'httpd.exe')
    foreach ($hijo in $hijos) { Stop-Process -Id $hijo.ProcessId -ErrorAction SilentlyContinue }
    Stop-Process -Id $proceso.ProcessId -ErrorAction SilentlyContinue
    Write-Output 'Servidor del proyecto detenido.'
    exit 0
}
if (!(Test-Path -LiteralPath $rutas.entorno)) { throw 'Ejecuta primero herramientas/configurar.ps1 para crear la conexion privada.' }
& $php (Join-Path $PSScriptRoot 'diagnostico\verificar_php.php') --conexion
if ($LASTEXITCODE -ne 0) { throw 'Revisa MySQL en XAMPP y configuracion/configuracion.env.' }
if ($proceso -and $Accion -eq 'iniciar') { Write-Output "Servidor ya activo: http://127.0.0.1:$Puerto/ingresar"; exit 0 }
if ($Accion -eq 'iniciar' -and (Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue)) { throw "El puerto $Puerto pertenece a otro proceso; no se altera." }
$raizApache=(Join-Path $Xampp 'apache').Replace('\','/')
$publico=$rutas.publico.Replace('\','/')
$phpRuta=$directorioPhp.Replace('\','/')
$pidRuta=$archivoPid.Replace('\','/')
$registro=(Join-Path $rutas.registros 'apache-local.log').Replace('\','/')
$temporales=$rutas.temporal.Replace('\','/')
# Todo archivo generado por Apache y por las cargas HTTP se escribe en el directorio externo.
$contenido=@"
ServerRoot "$raizApache"
Listen 127.0.0.1:$Puerto
ServerName 127.0.0.1
PidFile "$pidRuta"
ErrorLog "$registro"
LogLevel warn
ServerTokens Prod
ServerSignature Off
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule mime_module modules/mod_mime.so
LoadModule dir_module modules/mod_dir.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule log_config_module modules/mod_log_config.so
LoadFile "$phpRuta/libcrypto-3-x64.dll"
LoadFile "$phpRuta/libssl-3-x64.dll"
LoadFile "$phpRuta/php8ts.dll"
LoadModule php_module "$phpRuta/php8apache2_4.dll"
PHPIniDir "$phpRuta"
php_admin_value upload_tmp_dir "$temporales"
TypesConfig conf/mime.types
DocumentRoot "$publico"
DirectoryIndex index.php
<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$publico">
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
[IO.File]::WriteAllText($configuracion,$contenido,[Text.UTF8Encoding]::new($false))
$apache=Join-Path $Xampp 'apache\bin\httpd.exe'
if (!(Test-Path -LiteralPath $apache)) { throw 'No se encontro Apache en la ruta XAMPP indicada.' }
$rutaAnterior=$env:PATH
try {
    # La ruta de DLL se ajusta solo para este proceso y sus hijos; no se modifica el PATH del equipo.
    $env:PATH="$directorioPhp;$rutaAnterior"
    & $apache -t -f $configuracion
    if ($LASTEXITCODE -ne 0) { throw 'Configuracion Apache invalida.' }
    if ($Accion -eq 'validar') { Write-Output 'PHP, base de datos y Apache verificados.'; exit 0 }
    Start-Process -FilePath $apache -ArgumentList @('-f',('"'+$configuracion+'"')) -WorkingDirectory $directorioPhp -WindowStyle Hidden
    for ($intento=0;$intento -lt 30;$intento++) {
        Start-Sleep -Milliseconds 200
        try { if ((Invoke-WebRequest -UseBasicParsing -Uri "http://127.0.0.1:$Puerto/ingresar" -TimeoutSec 2).StatusCode -eq 200) { Write-Output "Documentos: http://127.0.0.1:$Puerto/ingresar"; exit 0 } } catch { }
    }
    throw 'Apache no respondio. Revisa apache-local.log en el directorio externo de registros.'
} finally { $env:PATH=$rutaAnterior }
