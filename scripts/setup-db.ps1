[CmdletBinding()]
param([string]$DatabaseUser = 'root', [string]$DatabaseHost = '127.0.0.1', [int]$DatabasePort = 3306)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpExe = Join-Path $projectRoot 'tools\php-8.5.12\php.exe'
if (!(Test-Path -LiteralPath $phpExe)) { throw 'Primero preparar PHP seguro.' }
$securePassword = Read-Host "Contraseña de $DatabaseUser en MariaDB (no se presupone vacía)" -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
try {
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    @{host=$DatabaseHost;port=$DatabasePort;user=$DatabaseUser;password=$password} | ConvertTo-Json -Compress | & $phpExe (Join-Path $projectRoot 'scripts\init-db.php') --create
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo preparar la base. No reintentar sin inspeccionar el resultado.' }
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    $password = $null
}
& $phpExe (Join-Path $projectRoot 'database\seed.php') --demo
if ($LASTEXITCODE -ne 0) { throw 'No se pudieron crear las cuentas demo.' }
