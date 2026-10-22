[CmdletBinding()]
param([string]$UrlBase='http://127.0.0.1:8089')
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'herramientas\comun.ps1')
$anteriores=Importar-EntornoPruebas
try{
    & $ejecutablePhp (Join-Path $raizProyecto 'base_datos\datos_prueba\usuarios.php') --demostracion
    if($LASTEXITCODE -ne 0){throw 'Semilla de pruebas fallida.'}
    & $ejecutablePhp (Join-Path $PSScriptRoot 'unitarias\validacion.php')
    if($LASTEXITCODE -ne 0){throw 'Pruebas unitarias fallidas.'}
    & $ejecutablePhp -d extension=curl (Join-Path $PSScriptRoot 'integracion\documentos.php') $UrlBase
    if($LASTEXITCODE -ne 0){throw 'Pruebas HTTP fallidas.'}
}finally{Restaurar-Entorno $anteriores}
