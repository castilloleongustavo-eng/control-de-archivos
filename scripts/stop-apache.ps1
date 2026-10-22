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
# En Windows, -k shutdown busca un servicio instalado. Esta instancia es un
# proceso local: detener solo el PID comprobado y sus hijos con la misma configuración.
$children = @(Get-CimInstance Win32_Process -Filter "ParentProcessId=$serverPid" | Where-Object { $_.Name -eq 'httpd.exe' -and $_.CommandLine.Contains($configPath) })
foreach ($child in $children) { Stop-Process -Id $child.ProcessId -ErrorAction Stop }
Stop-Process -Id $serverPid -ErrorAction Stop
if (Test-Path -LiteralPath $pidPath) { Remove-Item -LiteralPath $pidPath }
Write-Output "Instancia $instance detenida."
