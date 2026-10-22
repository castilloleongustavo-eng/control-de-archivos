[CmdletBinding()]
param([string]$Xampp = 'C:\xampp', [switch]$TestMode)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$instance = if ($TestMode) { 'test' } else { 'demo' }
$configPath = Join-Path $projectRoot "storage\runtime\httpd-$instance.conf"
$pidPath = Join-Path $projectRoot "storage\runtime\apache-$instance.pid"
if (!(Test-Path -LiteralPath $pidPath)) { throw 'No hay PID de Apache del proyecto.' }
$serverPid = [int](Get-Content -LiteralPath $pidPath -Raw)
$process = Get-CimInstance Win32_Process -Filter "ProcessId=$serverPid"
if (!$process -or $process.Name -ne 'httpd.exe' -or !$process.CommandLine.Contains($configPath)) { throw 'El PID no corresponde al Apache aislado. No se detiene ningún proceso.' }
& (Join-Path $Xampp 'apache\bin\httpd.exe') -k shutdown -f $configPath
if ($LASTEXITCODE -ne 0) { throw 'No se pudo detener la instancia local.' }
