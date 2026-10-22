[CmdletBinding()]
param([string]$Usuario='root',[string]$Servidor='127.0.0.1',[int]$Puerto=3306,[switch]$Pruebas)
. (Join-Path (Split-Path $PSScriptRoot -Parent) 'comun.ps1')
$secreta=Read-Host "Contraseña de $Usuario en MariaDB" -AsSecureString
$puntero=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($secreta)
try{
    $argumentos=@((Join-Path $PSScriptRoot 'crear_base_datos.php'),'--crear');if($Pruebas){$argumentos+='--pruebas'}
    @{servidor=$Servidor;puerto=$Puerto;usuario=$Usuario;contrasena=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($puntero)}|ConvertTo-Json -Compress|& $ejecutablePhp @argumentos
    if($LASTEXITCODE -ne 0){throw 'La base no se preparó; revisar el estado antes de repetir.'}
}finally{[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($puntero)}
if(!$Pruebas){& $ejecutablePhp (Join-Path $raizProyecto 'base_datos\datos_prueba\usuarios.php') --demostracion}
