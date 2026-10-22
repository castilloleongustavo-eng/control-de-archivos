[CmdletBinding()]
param()
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'comun.ps1')
$cantidad=0
foreach($directorio in @('aplicacion','configuracion','base_datos','publico','herramientas\diagnostico','herramientas\instalacion','herramientas\mantenimiento','pruebas\unitarias','pruebas\integracion','pruebas\soporte')){
    Get-ChildItem -LiteralPath (Join-Path $raizProyecto $directorio) -Recurse -File -Filter '*.php' | Where-Object FullName -notmatch '\\externos\\' | ForEach-Object {
        $resultado=& $ejecutablePhp -l $_.FullName
        if($LASTEXITCODE -ne 0){throw ($resultado -join "`n")};$cantidad++
    }
}
foreach($archivo in @(Get-ChildItem -LiteralPath (Join-Path $raizProyecto 'herramientas'),(Join-Path $raizProyecto 'pruebas') -Recurse -File -Filter '*.ps1' | Where-Object FullName -notmatch '\\(ejecucion|\.runtime)\\')){
    $errores=$null;$tokens=$null;[Management.Automation.Language.Parser]::ParseFile($archivo.FullName,[ref]$tokens,[ref]$errores)|Out-Null
    if($errores.Count){throw ($errores|Out-String)}
}
Write-Output "$cantidad archivos PHP y scripts PowerShell sin errores de sintaxis."
