[CmdletBinding()]
param([string]$Php = '')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
if (!$Php) { $Php = Join-Path $projectRoot 'tools\php-8.5.12\php.exe' }
$version = & $Php -r 'echo PHP_VERSION;'
if ($LASTEXITCODE -ne 0 -or [version]$version -lt [version]'8.5.12') { throw 'Usar PHP estable 8.5.12 o posterior para validar.' }
$count = 0
foreach ($folder in @('app','config','database','public','scripts','tests')) {
    Get-ChildItem -LiteralPath (Join-Path $projectRoot $folder) -Recurse -Filter '*.php' | ForEach-Object {
        $result = & $Php -l $_.FullName
        if ($LASTEXITCODE -ne 0) { throw $result }
        $count++
    }
}
Write-Output "$count archivos PHP sin errores de sintaxis ($version)."
