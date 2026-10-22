# Instalación y configuración

## Entorno existente

PHP 8.5.12 local dentro del proyecto; XAMPP proporciona Apache x64 y MariaDB. No es necesario modificar XAMPP ni mover el proyecto a htdocs. Ejecutar desde la raíz del proyecto.

```powershell
where.exe php
& .\herramientas\ejecucion\php-8.5.12\php.exe -v
& .\herramientas\ejecucion\php-8.5.12\php.exe --ini
& .\herramientas\ejecucion\php-8.5.12\php.exe .\herramientas\diagnostico\verificar_php.php
& C:\xampp\apache\bin\httpd.exe -v
& C:\xampp\mysql\bin\mysql.exe --version
```

## Nueva máquina

1. Instalar XAMPP oficial con autorización. No exponerlo a Internet. Si ya existe, inspeccionar sus servicios y conservar sus proyectos.
2. Revisar y autorizar la descarga independiente de PHP:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\instalacion\preparar-php.ps1
   powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\instalacion\preparar-php.ps1 -Instalar
   ```

   Solo se admite el ZIP oficial estable PHP 8.5.12 VS17 x64 Thread Safe, SHA-256 `660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184`. El destino existente no se sobrescribe. Se requiere Visual C++ Redistributable oficial x64 compatible; cualquier instalación adicional requiere autorización. No mezclar extensiones ni DLL de versiones anteriores. NTS se utiliza para CGI/FastCGI, no mod_php.
3. Iniciar MariaDB en XAMPP con autorización para alterar servicios y comprobar el puerto, sin detener procesos ajenos.
4. Crear una base nueva mediante `herramientas/instalacion/preparar-base-datos.ps1`, que solicita las credenciales administrativas sin mostrarlas. La cuenta web se genera con contraseña aleatoria y permisos mínimos. No presupone contraseña vacía. Si existe la base, la cuenta o el entorno, se detiene. Para pruebas, añadir `-Pruebas`.
5. Alternativamente, en phpMyAdmin local importar `base_datos/estructura/crear_base.sql`, `esquema.sql` y `relaciones.sql`, en ese orden. Crear una cuenta propia con los permisos del diccionario. Copiar `.env.example` a `.env` y completar BD_SERVIDOR, BD_PUERTO, BD_NOMBRE, BD_USUARIO y BD_CONTRASENA. Formato `CLAVE=valor`, sin comillas.
6. Crear explícitamente usuarios demo:

   ```powershell
   & .\herramientas\ejecucion\php-8.5.12\php.exe .\base_datos\datos_prueba\usuarios.php --demostracion
   ```

7. Configurar `RUTA_DOCUMENTOS`, preparar su directorio con permisos del usuario que ejecuta Apache y mantenerlo fuera del DocumentRoot. No reubicar datos existentes sin autorización. El valor predeterminado es `C:/AlmacenamientoPrivado/GestorArchivos/documentos`.
8. Iniciar Apache y comprobar la versión efectiva:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\iniciar-apache.ps1
   powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\diagnostico\verificar-http.ps1
   ```

   Abre http://127.0.0.1:8088/ingresar. `-Puerto` permite otro puerto libre. `-Xampp` admite otro directorio XAMPP. El lanzador comprueba la sintaxis con `httpd -t`, usa solo loopback y genera configuración propia. La sonda HTTP aleatoria se elimina al terminar y nunca es un phpinfo. Si se interrumpe, revisar que no quede `publico/__verificacion_*.php`.

Límites de php.ini: `file_uploads=On`, `upload_max_filesize=10M`, `post_max_size=12M`. El backend conserva el límite exacto de 10 MiB. Las bibliotecas OpenSSL se cargan desde el PHP nuevo; no se reemplazan las de XAMPP. `herramientas/comun.ps1` actualiza únicamente extension_dir del php.ini propio si se cambia la carpeta del proyecto y conserva un respaldo.

## VirtualHost convencional

La plantilla `configuracion/servidor/virtualhost.ejemplo.conf` utiliza `documentos.local` y `publico/`. Aplicarla exige autorización para cambios globales, respaldos de configuración y archivo hosts, conservación del VirtualHost predeterminado, verificación ABI x64/TS, `httpd -t`, reinicio autorizado y comprobación de PHP por HTTP. La entrega utiliza una instancia aislada. Nunca publicar la raíz con credenciales, código interno o almacenamiento.

## Reorganización de esta instalación

Se verificaron y copiaron filas a bases nuevas y se crearon cuentas de permisos mínimos. El origen se conserva para recuperación. Los secretos de transición se guardan únicamente como respaldo local ignorado, no dentro de los archivos de entrega. El adaptador de migración recibe credenciales por stdin y verifica todas las filas antes de activar el nuevo `.env`. `--continuar` solo se utiliza tras inspección administrativa de una operación interrumpida, nunca para sobrescribir datos distintos.
