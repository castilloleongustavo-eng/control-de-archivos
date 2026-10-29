# Archivo: herramientas/configurar.ps1
# Propósito: Solicita credenciales administrativas y prepara la conexión de MariaDB.
# Funcionamiento: Lee la contraseña sin mostrarla, envía JSON por stdin a PHP y libera los recursos utilizados para manejar la clave.

<#
.SYNOPSIS
Solicita credenciales administrativas y prepara la conexion local de Control de archivos.
.PARAMETER Crear
Crea una base nueva. Si se omite, utiliza la base documentos existente sin borrar sus filas.
#>
[CmdletBinding()]
param([string]$Usuario='root',[string]$Servidor='127.0.0.1',[int]$Puerto=3306,[switch]$Crear)
$ErrorActionPreference='Stop'
. (Join-Path $PSScriptRoot 'entorno-php.ps1')
# Read-Host mantiene la contraseña fuera del historial de comandos y de los argumentos del proceso.
# La contraseña no aparece en pantalla ni en los argumentos del proceso.
$secreta=Read-Host "Contrasena administrativa de $Usuario en MariaDB" -AsSecureString
$puntero=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($secreta)
# El puntero permite enviar la clave por stdin; finally libera esa memoria aunque la configuracion falle.
$codificacionAnterior=$OutputEncoding
try {
    # El JSON por stdin conserva contraseñas con tildes y otros caracteres Unicode.
    $OutputEncoding=[Text.UTF8Encoding]::new($false)
# Selecciona la operación y envía credenciales en JSON por la entrada estándar del proceso PHP.
    $modo=if($Crear){'--crear'}else{'--configurar'}
    $datos=@{servidor=$Servidor;puerto=$Puerto;usuario=$Usuario;contrasena=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($puntero)}
    $datos | ConvertTo-Json -Compress | & $php (Join-Path $PSScriptRoot 'instalacion\crear_base_datos.php') $modo
    if ($LASTEXITCODE -ne 0) { throw 'No se completo la configuracion. Revisa el registro privado y la conexion a MariaDB.' }
} finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($puntero); $datos=$null; $OutputEncoding=$codificacionAnterior }
