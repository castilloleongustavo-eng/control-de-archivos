# Rutas compartidas por herramientas locales. Nunca imprime credenciales del entorno.
$ErrorActionPreference = 'Stop'
$raizProyecto = Split-Path $PSScriptRoot -Parent
$directorioPhp = Join-Path $PSScriptRoot 'ejecucion\php-8.5.12'
$ejecutablePhp = Join-Path $directorioPhp 'php.exe'
$codificacionUtf8 = [Text.UTF8Encoding]::new($false)
if (Test-Path -LiteralPath (Join-Path $directorioPhp 'php.ini')) {
    $rutaIni=Join-Path $directorioPhp 'php.ini'
    $contenidoIni=[IO.File]::ReadAllText($rutaIni)
    $nuevoIni=[regex]::Replace($contenidoIni,'(?m)^extension_dir\s*=.*$', ('extension_dir = "'+$directorioPhp.Replace('\','/')+'/ext"'))
    if ($nuevoIni -ne $contenidoIni) {
        Copy-Item -LiteralPath $rutaIni -Destination ($rutaIni+'.respaldo-'+(Get-Date -Format yyyyMMddHHmmss))
        [IO.File]::WriteAllText($rutaIni,$nuevoIni,$codificacionUtf8)
    }
}
function Importar-EntornoPruebas {
    # Devuelve valores previos para restaurarlos al terminar el proceso de pruebas.
    $anteriores=@{}
    foreach ($linea in Get-Content -LiteralPath (Join-Path $raizProyecto 'pruebas\.runtime\.env') -Encoding UTF8) {
        if ($linea -match '^([A-Z_]+)=(.*)$') {
            $clave=$Matches[1];$anteriores[$clave]=[Environment]::GetEnvironmentVariable($clave,'Process')
            [Environment]::SetEnvironmentVariable($clave,$Matches[2],'Process')
        }
    }
    return $anteriores
}
function Restaurar-Entorno([hashtable]$Anteriores) {
    # Restablece solo las variables que esta herramienta cambió en su propio proceso.
    foreach ($clave in $Anteriores.Keys) { [Environment]::SetEnvironmentVariable($clave,$Anteriores[$clave],'Process') }
}
