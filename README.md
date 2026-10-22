# ADMOSA File Manager

Aplicación web para registrar usuarios y administrar archivos personales. Cada operación valida la sesión y consulta simultáneamente el identificador del archivo y el propietario autenticado. Conocer un identificador ajeno no permite acceder a su contenido.

Desarrollada conforme al PDF **“Analista Desarrollador Jr - Prueba Técnica”, versión 1.0 / 2026**, leído desde Descargas, y al texto de requisitos adjunto. La correspondencia está en [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md).

## Ejecutar la entrega local

La aplicación está preparada en esta carpeta con una instancia independiente de Apache:

**http://127.0.0.1:8088**

Si la instancia está detenida, abrir PowerShell en la carpeta del proyecto:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-apache.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\verify-http.ps1
```

`-ExecutionPolicy Bypass` se aplica únicamente al proceso del comando: no cambia la política del equipo. Revisar los scripts antes de ejecutarlos. El lanzador comprueba que el puerto esté libre; no detiene procesos existentes ni cambia puertos de otros proyectos. `-Port` permite elegir otro puerto libre.

Credenciales **exclusivamente demostrativas**, creadas explícitamente con `seed.php --demo`:

| Usuario | Contraseña | Correo |
| --- | --- | --- |
| prueba1 | DemoSegura2026!A | prueba1@example.test |
| prueba2 | DemoSegura2026!B | prueba2@example.test |

No usar estas cuentas en producción. El seed calcula los hashes con `password_hash()` y conserva cuentas existentes sin cambiar sus contraseñas.

Para detener solo la instancia del proyecto, cuando no haya operaciones en curso:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\stop-apache.ps1
```

En Windows esta instancia no es un servicio instalado. El script verifica PID, nombre y línea de comandos, y detiene exclusivamente ese proceso y sus hijos asociados a la configuración del proyecto.

## Tecnologías y versiones verificadas

| Componente | Resultado |
| --- | --- |
| Windows | Entorno Windows del usuario |
| PHP local del proyecto, CLI y HTTP | **8.5.12**, VS17 / Visual C++ 2022, x64, Thread Safe |
| SAPI HTTP de la aplicación | `apache2handler` |
| Apache reutilizado desde XAMPP | 2.4.58 Win64 / Apache Lounge VS17 |
| MariaDB de XAMPP | 10.4.32 |
| Extensiones efectivas HTTP | pdo_mysql, fileinfo, mbstring, openssl, zip |
| Bootstrap | 5.3.8, CSS local con SHA-384 oficial verificado |
| PDF.js | 6.4.299, renderizado en canvas; distribución oficial verificada por SHA-256 |
| Dependencias de desarrollo | PHP CLI; cURL CLI para pruebas HTTP; Chrome existente para pruebas visuales |

Sin Laravel, React, Node.js ni Composer. Autocarga de clases propia con `spl_autoload_register`.

**El XAMPP original sigue usando PHP 8.0.30. No está parcheado por este proyecto.** Se confirmó por CLI y por una petición al diagnóstico existente `/dashboard/phpinfo.php`; no se alteró ese archivo ajeno. El PHP corregido se usa exclusivamente en las instancias aisladas del proyecto. No se sustituyeron DLL ni binarios de XAMPP, no se cambió su PATH ni su configuración, y sus servicios y proyectos existentes se conservaron.

Fuentes oficiales consultadas el 21/10/2026: [aviso de seguridad y versiones corregidas](https://www.php.net/index.php), [descargas Windows, arquitectura, TS/NTS y compiladores](https://www.php.net/downloads.php?os=windows), [versiones soportadas](https://www.php.net/supported-versions.php), [Apache Friends](https://www.apachefriends.org/), [Bootstrap](https://getbootstrap.com/docs/5.3/getting-started/download/). PHP 8.5.12 corrige CVE-2026-109618 y CVE-2026-109619. No basta con que el nombre del paquete indique una versión: comprobar siempre CLI **y HTTP**.

## Instalación reproducible en otra máquina

1. Instalar XAMPP desde Apache Friends si no existe. Requiere autorización del propietario del equipo. Mantenerlo para uso local: no exponer Apache, MariaDB ni phpMyAdmin a Internet.
2. Comprobar rutas y versiones. La ausencia de PHP en PATH no implica que no esté instalado:

   ```powershell
   where.exe php
   php -v
   php --ini
   & C:\xampp\php\php.exe -v
   & C:\xampp\php\php.exe --ini
   & C:\xampp\apache\bin\httpd.exe -v
   & C:\xampp\mysql\bin\mysql.exe --version
   ```

3. Preparar el PHP independiente después de autorizar la instalación:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\prepare-php.ps1
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\prepare-php.ps1 -Install
   & .\tools\php-8.5.12\php.exe .\scripts\check-runtime.php
   ```

   La primera orden muestra el plan sin descargar. La segunda descarga el ZIP oficial `php-8.5.12-Win32-vs17-x64.zip`, exige SHA-256 `660f3ff6ccbdf60f9607ef54bdba85ee22a58d61fd3633b38a93d57fb7c3d184` y extrae en `tools/php-8.5.12`. No reemplaza un destino existente. El runtime y el ZIP quedan fuera de Git.

   Para `mod_php` se necesita **x64 Thread Safe** compatible con Apache x64. NTS corresponde a CGI/FastCGI y no sirve para cargar `php8apache2_4.dll`. La distribución VS17 requiere Visual C++ Redistributable 2015–2022 x64 oficial, si no estuviera instalado. No copiar extensiones de PHP 8.0, ni instalar redistribuibles sin autorización.

4. Iniciar MariaDB desde el panel de XAMPP si está detenido, con autorización para alterar servicios. El puerto esperado es 3306; verificarlo sin detener servicios ajenos.
5. Crear la base y una cuenta de aplicación con permisos mínimos:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\setup-db.ps1 -DatabaseUser root
   ```

   El script solicita la contraseña de la cuenta administrativa sin mostrarla, la transmite a PHP por stdin y no la guarda. No presupone una contraseña vacía. Crea `admosa_file_manager`, las tablas y `admosa_app@127.0.0.1` con contraseña aleatoria; escribe `.env` local y ejecuta el seed. Si la base, la cuenta o `.env` ya existen, se detiene para evitar reemplazarlos. La creación SQL usa DDL no transaccional: ante un fallo, inspeccionar el estado antes de reintentar.

6. Iniciar Apache aislado y confirmar HTTP con las órdenes del inicio. El lanzador usa `C:/xampp/apache` como origen de módulos, `public/` como DocumentRoot, configuración y PID propios, y escucha solamente en loopback. Se verificó `httpd -t`. Se cargan explícitamente las bibliotecas OpenSSL del PHP nuevo para resolver las dependencias de `mod_php`; no se reemplazan las de Apache. cURL solo se carga en CLI durante las pruebas, porque no es necesario para la aplicación.
7. Abrir el enlace local e iniciar sesión. La sonda HTTP tiene nombre aleatorio, solo entrega versión/extensiones y se elimina en `finally`; no crea una página `phpinfo`. Comprobar que no queda ningún archivo `public/__runtime_*.php` si se interrumpe el proceso de verificación.

`scripts/start-apache.ps1 -Xampp D:\xampp` permite otro directorio XAMPP. Si la combinación de Apache y PHP no pasa la validación, detenerse: no sustituir DLL para forzarla.

## Alternativa: importar desde phpMyAdmin

1. Abrir `http://localhost/phpmyadmin` únicamente en el equipo local y autenticarse con la cuenta administrativa configurada.
2. En **Importar**, seleccionar `database/schema.sql`. El script crea la base `admosa_file_manager`, tablas InnoDB y codificación `utf8mb4`; no incluye contraseñas ni elimina datos.
3. En **Cuentas de usuario**, crear una cuenta exclusiva `admosa_app` para host `127.0.0.1` con una contraseña propia. Asignar permisos por tabla: `users`: SELECT, INSERT, UPDATE; `files`: SELECT, INSERT, DELETE; `auth_attempts`: SELECT, INSERT, UPDATE, DELETE. No conceder permisos globales ni usar root como cuenta web.
4. Copiar `.env.example` a `.env` y completar DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS. El formato es `CLAVE=valor`, sin comillas. No publicar `.env`.
5. Ejecutar explícitamente:

   ```powershell
   & .\tools\php-8.5.12\php.exe .\database\seed.php --demo
   ```

6. Iniciar el Apache aislado como arriba. No ejecutar `setup-db.ps1` sobre esta instalación ya creada.

## VirtualHost convencional y actualización global

`config/vhost.example.conf` contiene una plantilla para `admosa.local` con DocumentRoot en `public/`. Antes de aplicarla, se requiere autorización para modificar XAMPP y, si corresponde, el archivo hosts de Windows. Respaldar `httpd.conf`, `httpd-vhosts.conf`, `httpd-xampp.conf` y el `php.ini` activo con fecha. Conservar el VirtualHost predeterminado y los proyectos existentes; verificar x64, TS y ABI de extensiones. Apuntar los módulos a una instalación nueva compatible, ejecutar `httpd -t`, reiniciar con autorización y comprobar PHP por HTTP. No eliminar la instalación anterior ni bases de datos. La entrega utiliza la alternativa aislada para evitar estos cambios globales.

No colocar la raíz del proyecto bajo un DocumentRoot público. La regla `.htaccess` de la raíz deniega acceso como defensa adicional, pero requiere que Apache permita interpretarla; no sustituye un DocumentRoot correcto. El `public/.htaccess` sirve para instalaciones que permitan `mod_rewrite`; la configuración aislada usa `FallbackResource` y no depende de `.htaccess`.

## Arquitectura y estructura

```text
app/
  Core/           Router, PDO, sesiones, vistas y errores HTTP
  Controllers/    Autenticación, dashboard y operaciones de archivos
  Models/         Consultas de usuarios y archivos
  Services/       Validación, almacenamiento y límite de intentos
  Middleware/     Sesión, CSRF y cabeceras
  Views/          HTML escapado, sin consultas SQL
config/           Parámetros y plantilla de VirtualHost
database/         schema.sql y seed.php
public/           Único directorio público: index.php y assets locales
storage/
  private/        Binarios: propietario / nombre aleatorio sin extensión
  sessions/       Sesiones de PHP
  logs/           Errores técnicos privados
  runtime/        Configuración Apache y PID generados
scripts/          Preparación, diagnóstico y recuperación
tests/            Pruebas unitarias, HTTP y Chrome
docs/             Requisitos, evidencias y capturas
tools/            PHP local, ignorado por Git
```

Flujo: `public/index.php → Router → Security → Controller → Model/Service → View`. PDO usa consultas preparadas reales y excepciones. La identidad se obtiene exclusivamente de la sesión, nunca de `user_id` enviado por el cliente.

## Funcionalidades y endpoints

| Método y ruta | Resultado |
| --- | --- |
| GET /register, POST /register | Registro y validación de duplicados |
| GET /login, POST /login | Autenticación |
| POST /logout | Destrucción de sesión y cookie |
| GET / | Resumen personal, estadísticas y archivos recientes |
| GET /files | Listado personal, búsqueda, tipo y paginación de 25 |
| GET /files/upload, POST /files/upload | Formulario y carga |
| GET /files/{id}/preview | Página de vista previa |
| GET /files/{id}/view | Contenido autorizado; inline solo PDF/JPEG/PNG |
| GET /files/{id}/download | Descarga autorizada como attachment |
| POST /files/{id}/delete | Eliminación confirmada mediante diálogo |

Los endpoints de contenido vuelven a comprobar propiedad: acceder a la página de vista previa no concede acceso permanente al binario. TXT, DOCX y XLSX se descargan para abrirlos en su aplicación. Los PDF se dibujan por página en canvas mediante PDF.js local, sin capas de scripts, formularios ni enlaces activos. Incluye navegación por páginas y descarga alternativa. La captura de Chrome confirma el renderizado del documento. Ver [recursos y licencias](docs/THIRD_PARTY.md).

## Decisiones y seguridad

- **Almacenamiento en disco privado:** facilita la demostración y conserva la base pequeña. La base guarda metadatos y una ruta relativa generada por el servidor; `realpath` comprueba pertenencia al directorio permitido.
- **Identificadores y nombres:** ID público aleatorio de 128 bits; nombre físico aleatorio de 256 bits, sin extensión. El nombre original validado se usa solo como metadato. No se ejecutan archivos cargados.
- **Tamaño:** “10 MB” se interpreta como 10 MiB = **10 485 760 bytes**, consistente con PHP `10M`. Exactamente ese tamaño es válido; un byte adicional se rechaza. `post_max_size=12M` permite el formulario multipart. El servidor también limita el cuerpo completo a 12 MiB.
- **Formatos:** PDF para documentos, JPG/JPEG/PNG para imágenes, TXT UTF-8 para notas y DOCX/XLSX para trabajo de oficina. No se permiten ejecutables, HTML, SVG ni formatos Office con macros. Se comprueba extensión, MIME real mediante fileinfo, tamaño, cabecera de imagen válida y estructura básica de contenedores Office; se bloquean macros, objetos incrustados y expansión ZIP mayor de 50 MiB. Nunca se extrae el ZIP al disco.
- **Aislamiento:** todos los listados, estadísticas y accesos filtran por propietario; ver/descargar/eliminar usan ID + propietario. Archivos inexistentes y ajenos responden 404 indistinguible. El acceso sin sesión redirige al login con 303.
- **Autenticación:** contraseñas de al menos ocho caracteres Unicode y máximo 72 bytes, `password_hash`, verificación constante con `password_verify`, regeneración de ID y rehash cuando corresponda. Los nombres de usuario admiten 3–40 caracteres ASCII alfanuméricos o `_`; correo máximo 254 bytes y único. No se implementa recuperación por email.
- **Sesión:** almacenamiento privado, expiración por 30 minutos de inactividad, cookies HttpOnly y SameSite=Lax. Secure se activa en HTTPS; el loopback HTTP de desarrollo no lo activa. No se confía en cabeceras proxy para decidir identidad, IP o HTTPS.
- **CSRF y límites:** token aleatorio de sesión para todos los POST, incluido login, registro y logout. Hasta diez intentos de login o registro por IP y categoría en ventanas de 15 minutos, persistidos en MariaDB. La misma IP en una red compartida comparte el límite.
- **SQL/XSS/cabeceras:** parámetros preparados, búsqueda LIKE con comodines escapados, HTML dinámico con `htmlspecialchars`, CSP sin scripts inline, `nosniff`, no-store, política de referencia y restricciones de frames. Los PDF se entregan con CSP sandbox; su contenido no puede ejecutar scripts en el contexto de la aplicación. Nombres de descarga codificados según RFC 5987, sin concatenar rutas externas.
- **Errores:** mensajes comprensibles sin SQL ni trazas. Los fallos técnicos se registran en logs privados. 422 para validación, 413 para tamaño, 403 para CSRF y 429 para límite de intentos.
- **Consistencia:** si falla la inserción SQL, se elimina el binario recién movido. En borrado se bloquea la fila, se renombra el binario a `.deleting`, se elimina la fila y se confirma SQL; si falla SQL se restaura el binario. Después se purga el archivo apartado. Un corte de proceso puede requerir reconciliación; no se afirma atomicidad entre disco y SQL.

Diagnóstico de consistencia, sin modificar nada:

```powershell
& .\tools\php-8.5.12\php.exe .\scripts\reconcile-storage.php
```

Tras revisar los resultados, autorizar la limpieza y detener Apache, el administrador puede ejecutar `--apply --server-stopped`. Restaura eliminaciones no confirmadas y purga binarios sin fila. Los metadatos sin binario se reportan para revisión manual. No ejecutar mientras se realizan cargas. Respaldar **base y storage juntos** para recuperación coherente.

## Pruebas y evidencias

Se ejecutaron **19 pruebas unitarias y 43 pruebas HTTP: 62 correctas, 0 fallidas** con PHP 8.5.12 y Apache. Evidencias: [docs/TESTING.md](docs/TESTING.md), [resultado HTTP completo](docs/http-results.json), [resultado unitario](docs/unit-results.json), [capturas de Chrome](docs/screenshots/), [resultado visual](docs/visual-results.json) y [verificación HTTP del runtime](docs/runtime-verification.json).

La prueba crítica usa prueba1 y prueba2: PDF creado por prueba1, logout, login como prueba2, ausencia del archivo en su listado, 404 para vista previa/contenido/descarga/borrado, manipulación de `user_id`, verificación posterior de propietario y hash SHA-256 intactos, y eliminación válida únicamente por el propietario.

Se probó también en Chrome existente: login real mediante formulario, carga, selección de archivo, tabla, apertura/cancelación del diálogo, foco, eliminación y logout. Capturas a 1440×1000 y 390×844; sin desbordamiento horizontal de la página móvil. La tabla permite desplazamiento dentro de su contenedor.

Para repetir sin alterar la base de demostración:

1. Crear una vez `admosa_file_manager_test` con `powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\setup-db.ps1 -TestMode`. Solicita las credenciales administrativas y las transmite por stdin. No pasar contraseñas como argumentos ni guardarlas en scripts. Esta base y la cuenta `admosa_test` quedan aisladas. El script genera `tests/.runtime/.env` ignorado y almacenamiento propio; no reemplaza instalaciones anteriores.
2. Ejecutar:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\start-apache.ps1 -TestMode -Port 8089
   powershell -NoProfile -ExecutionPolicy Bypass -File .\tests\run.ps1
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\stop-apache.ps1 -TestMode
   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\lint.ps1
   ```

   El perfil test usa cookies, sesiones, almacenamiento y base separados, con expiración de 4 segundos para comprobar inactividad. La suite elimina sus cargas y restablece exclusivamente el contador de intentos de la base test. Conserva usuarios `qa_...` creados durante el registro para facilitar inspección; no se usa esta base para datos reales.
3. `tests/visual.ps1` usa Chrome ya instalado y la base demo. Usar solo sobre las cuentas demo sin documentos personales: crea y elimina un TXT de prueba, comprueba la interfaz y guarda capturas. No instala navegadores ni modifica el perfil habitual de Chrome.

## Limitaciones conocidas

- No hay antivirus, CDR ni validación exhaustiva semántica de PDF/Office. MIME, estructura básica y sandbox reducen riesgos; no certifican que un documento sea inocuo al abrirlo en otra aplicación.
- No se implementan cuota total por usuario, papelera, antivirus, recuperación de contraseña, MFA ni verificación de correo; quedan fuera de la prueba. La paginación limita el listado por petición.
- El servidor local usa Apache 2.4.58 y MariaDB 10.4.32 existentes. No se certifica su adecuación para producción ni la seguridad del XAMPP global. Un despliegue externo requiere una plataforma mantenida, HTTPS y gestión de secretos y respaldos.
- La rama de PHP se protege mediante una versión mínima y exclusión de prereleases; mantenerla actualizada cuando se publiquen nuevos avisos. La versión fijada para esta entrega es 8.5.12.
- El bloqueo de rutas depende del DocumentRoot configurado. En Windows, los permisos de disco heredan las ACL del usuario: el aislamiento aquí protege usuarios web, no personas con acceso administrativo al sistema operativo.
- Se ejecutaron los escenarios documentados; no se ejecutaron pruebas de carga, auditoría externa, fallos de energía reales ni todos los visores PDF y navegadores posibles.

## Demostración y explicación en vivo

1. Iniciar sesión como prueba1; subir un PDF o imagen de menos de 10 MB.
2. Mostrar estadísticas, búsqueda, vista previa y descarga. Copiar el ID desde un enlace.
3. Cerrar sesión e iniciar como prueba2. Mostrar que no aparece el archivo; visitar su URL y comprobar el 404.
4. Volver a prueba1, eliminar con confirmación y cerrar sesión.
5. Explicar `Security::user`, `StoredFile::owned`, `UploadValidator` y `FileStorage`, el DocumentRoot privado y por qué el ID aleatorio no reemplaza la autorización.

## Git y entrega

Repositorio Git local con commits por etapas y autor configurado solamente aquí como `gcastillol1-byte <gcastillol1-byte@users.noreply.github.com>`. No se ha publicado ni enviado correo. `.gitignore` excluye `.env`, binarios privados, sesiones, logs, runtime, PHP descargado y perfiles de Chrome; las capturas y resultados de pruebas sí se incluyen como evidencias.

Antes de publicar, revisar `git status`, `git diff --cached` y `git log --oneline`, crear el repositorio GitHub autorizado y añadir el remoto. No incluir bases exportadas con datos personales ni cambiar la privacidad sin autorización. El enlace y envío a ADMOSA se realizan únicamente cuando el usuario los autorice. Se incluye código, esquema SQL y seed reproducible; no se incluyen secretos locales.
