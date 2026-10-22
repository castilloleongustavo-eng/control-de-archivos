[CmdletBinding()]
param([Parameter(Mandatory=$true)][string]$Origen,[string]$Destino='C:\AlmacenamientoPrivado\GestorArchivos\documentos',[switch]$Aplicar)
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'comun.ps1')
$origenAbsoluto=[IO.Path]::GetFullPath($Origen).TrimEnd('\')
$destinoAbsoluto=[IO.Path]::GetFullPath($Destino).TrimEnd('\')
$destinoPermitido='C:\AlmacenamientoPrivado\GestorArchivos\documentos'
if($destinoAbsoluto -ne $destinoPermitido -or !(Test-Path -LiteralPath $origenAbsoluto -PathType Container)){throw 'Revisar las rutas de origen/destino antes del traslado.'}
if(Test-Path -LiteralPath $destinoAbsoluto){throw 'El destino existe; no se combina ni sobrescribe automáticamente.'}
$archivos=@(Get-ChildItem -LiteralPath $origenAbsoluto -File -Recurse | Where-Object Name -ne '.gitkeep')
Write-Output "Plan: copiar y verificar $($archivos.Count) documentos a $destinoAbsoluto. Conservar todos los originales."
if(!$Aplicar){return}
$null=New-Item -ItemType Directory -Path $destinoAbsoluto -Force
foreach($archivo in $archivos){
    $relativa=$archivo.FullName.Substring($origenAbsoluto.Length+1)
    $destinoArchivo=[IO.Path]::GetFullPath((Join-Path $destinoAbsoluto $relativa))
    if(!$archivo.FullName.StartsWith($origenAbsoluto+'\') -or !$destinoArchivo.StartsWith($destinoAbsoluto+'\')){throw 'Ruta fuera del ámbito autorizado.'}
    New-Item -ItemType Directory -Force -Path (Split-Path $destinoArchivo -Parent)|Out-Null
    Copy-Item -LiteralPath $archivo.FullName -Destination $destinoArchivo
    if((Get-FileHash -LiteralPath $archivo.FullName -Algorithm SHA256).Hash -ne (Get-FileHash -LiteralPath $destinoArchivo -Algorithm SHA256).Hash){throw 'La copia no coincide; se conserva el origen y no se activa la nueva ruta.'}
}
$rutaEntorno=Join-Path $raizProyecto '.env'
$texto=[IO.File]::ReadAllText($rutaEntorno)
$linea='RUTA_DOCUMENTOS='+$destinoAbsoluto.Replace('\','/')
if($texto -match '(?m)^RUTA_DOCUMENTOS='){$texto=[regex]::Replace($texto,'(?m)^RUTA_DOCUMENTOS=.*$',$linea)}else{$texto+="`n$linea`n"}
[IO.File]::WriteAllText($rutaEntorno,$texto,$codificacionUtf8)
Write-Output 'Copias verificadas y ruta activada en .env; originales intactos.'
