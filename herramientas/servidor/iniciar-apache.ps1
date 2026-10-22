[CmdletBinding()]
param([string]$Xampp='C:\xampp',[int]$Puerto=8088,[switch]$SoloValidar,[switch]$Pruebas)
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'comun.ps1')
$valoresAnteriores=@{};$rutaAnterior=$env:PATH
try {
    if ($Pruebas) { $valoresAnteriores=Importar-EntornoPruebas }
    if (!$SoloValidar -and (Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue)) { throw "El puerto $Puerto está ocupado." }
    $env:PATH="$directorioPhp;$rutaAnterior"
    $rutas=(& $ejecutablePhp (Join-Path $raizProyecto 'herramientas\diagnostico\rutas.php')) | ConvertFrom-Json
    $instancia=if($Pruebas){'pruebas'}else{'local'}
    $raizApache=(Join-Path $Xampp 'apache').Replace('\','/')
    $publico=$rutas.publico.Replace('\','/');$php=$directorioPhp.Replace('\','/')
    $archivoPid=(Join-Path $rutas.temporal "apache-$instancia.pid").Replace('\','/')
    $registro=(Join-Path $rutas.registros "apache-$instancia.log").Replace('\','/')
    $rutaConfiguracion=Join-Path $rutas.temporal "apache-$instancia.conf"
    $configuracion=@"
ServerRoot "$raizApache"
Listen 127.0.0.1:$Puerto
ServerName 127.0.0.1
PidFile "$archivoPid"
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
LoadFile "$php/libcrypto-3-x64.dll"
LoadFile "$php/libssl-3-x64.dll"
LoadFile "$php/php8ts.dll"
LoadModule php_module "$php/php8apache2_4.dll"
PHPIniDir "$php"
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
    if(Test-Path -LiteralPath $rutaConfiguracion){Copy-Item -LiteralPath $rutaConfiguracion -Destination ($rutaConfiguracion+'.respaldo-'+(Get-Date -Format yyyyMMddHHmmss))}
    [IO.File]::WriteAllText($rutaConfiguracion,$configuracion,$codificacionUtf8)
    $apache=Join-Path $Xampp 'apache\bin\httpd.exe'
    & $apache -t -f $rutaConfiguracion
    if($LASTEXITCODE -ne 0){throw 'Configuración de Apache inválida.'}
    if(!$SoloValidar){Start-Process -FilePath $apache -ArgumentList @('-f',('"'+$rutaConfiguracion+'"')) -WorkingDirectory $directorioPhp -WindowStyle Hidden;Write-Output "Documentos: http://127.0.0.1:$Puerto/ingresar"}
} finally { $env:PATH=$rutaAnterior;Restaurar-Entorno $valoresAnteriores }
