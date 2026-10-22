[CmdletBinding()]
param([string]$BaseUrl = 'http://127.0.0.1:8089')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpExe = Join-Path $projectRoot 'tools\php-8.5.12\php.exe'
$savedEnv = @{}
try {
    foreach ($line in Get-Content -LiteralPath (Join-Path $PSScriptRoot '.runtime\.env')) {
        if ($line -match '^(DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS|SESSION_TIMEOUT|APP_PROFILE)=(.*)$') {
            $key = $Matches[1]
            $savedEnv[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
            [Environment]::SetEnvironmentVariable($key, $Matches[2], 'Process')
        }
    }
    & $phpExe (Join-Path $projectRoot 'database\seed.php') --demo
    if ($LASTEXITCODE -ne 0) { throw 'Seed de pruebas fallido.' }
    & $phpExe (Join-Path $PSScriptRoot 'unit.php')
    if ($LASTEXITCODE -ne 0) { throw 'Pruebas unitarias fallidas.' }
    & $phpExe -d extension=curl (Join-Path $PSScriptRoot 'http.php') $BaseUrl
    if ($LASTEXITCODE -ne 0) { throw 'Pruebas HTTP fallidas.' }
} finally {
    foreach ($key in $savedEnv.Keys) { [Environment]::SetEnvironmentVariable($key, $savedEnv[$key], 'Process') }
}
