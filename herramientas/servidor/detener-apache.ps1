[CmdletBinding()]
param([switch]$Pruebas)
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'comun.ps1')
$anteriores=@{}
try{
    if($Pruebas){$anteriores=Importar-EntornoPruebas}
    $rutas=(& $ejecutablePhp (Join-Path $raizProyecto 'herramientas\diagnostico\rutas.php'))|ConvertFrom-Json
    $instancia=if($Pruebas){'pruebas'}else{'local'}
    $configuracion=Join-Path $rutas.temporal "apache-$instancia.conf";$rutaPid=Join-Path $rutas.temporal "apache-$instancia.pid"
    if(!(Test-Path -LiteralPath $rutaPid)){throw 'La instancia no tiene PID registrado.'}
    $identificadorProceso=[int](Get-Content -LiteralPath $rutaPid -Raw)
    $proceso=Get-CimInstance Win32_Process -Filter "ProcessId=$identificadorProceso"
    if(!$proceso -or $proceso.Name -ne 'httpd.exe' -or !$proceso.CommandLine.Contains($configuracion)){throw 'El PID no corresponde a esta configuración; no se detiene.'}
    $hijos=@(Get-CimInstance Win32_Process -Filter "ParentProcessId=$identificadorProceso"|Where-Object{$_.Name -eq 'httpd.exe' -and $_.CommandLine -and $_.CommandLine.Contains($configuracion)})
    foreach($hijo in $hijos){Stop-Process -Id $hijo.ProcessId -ErrorAction SilentlyContinue}
    Stop-Process -Id $identificadorProceso -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $rutaPid
    Write-Output "Instancia $instancia detenida."
}finally{Restaurar-Entorno $anteriores}
