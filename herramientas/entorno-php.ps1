# Archivo: herramientas/entorno-php.ps1
# Propósito: Ajusta la ruta de extensiones del PHP local al directorio actual.
# Funcionamiento: Se carga desde otros scripts, comprueba php.exe y php.ini y actualiza únicamente extension_dir si cambió la ubicación.

# Actualiza solo extension_dir cuando se mueve el proyecto; conserva los demas ajustes.
# Resuelve las rutas desde el script para admitir nombres con espacios o cambios de ubicación.
$directorioPhp=Join-Path $PSScriptRoot 'ejecucion\php-8.5.12'
$php=Join-Path $directorioPhp 'php.exe'
$archivoIni=Join-Path $directorioPhp 'php.ini'
if (!(Test-Path -LiteralPath $php)) { throw 'Falta el PHP local. Ejecuta herramientas/preparar-php.ps1 -Instalar.' }
if (!(Test-Path -LiteralPath $archivoIni)) { throw 'Falta php.ini en el directorio del PHP local.' }
# Lee la configuración y construye la directiva absoluta de extensiones de esta instalación.
$contenidoIni=[IO.File]::ReadAllText($archivoIni)
$directiva='extension_dir = "'+(Join-Path $directorioPhp 'ext').Replace('\','/')+'"'
$patron='(?m)^\s*extension_dir\s*=[^\r\n]*'
# Reemplaza la directiva existente; si no está definida la agrega al final.
if ([regex]::IsMatch($contenidoIni,$patron)) {
    $nuevoIni=[regex]::Replace($contenidoIni,$patron,[System.Text.RegularExpressions.MatchEvaluator]{ param($coincidencia) $directiva })
} else {
    $nuevoIni=$contenidoIni+[Environment]::NewLine+$directiva+[Environment]::NewLine
}
# Escribe UTF-8 sin BOM solo si cambió el contenido, conservando los demás ajustes.
if ($nuevoIni -cne $contenidoIni) {
    [IO.File]::WriteAllText($archivoIni,$nuevoIni,[Text.UTF8Encoding]::new($false))
}
