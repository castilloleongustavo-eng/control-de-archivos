# Documentos

Aplicación PHP nativa para registrar cuentas y administrar exclusivamente documentos propios. Cumple la prueba técnica oficial de ADMOSA **“Gestión de archivos por usuario”**, con arquitectura MVC ligera, PDO y almacenamiento privado. El nombre visible provisional de la aplicación es **Documentos**.

## Abrir la aplicación local

Desde esta carpeta, ejecutar:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\iniciar-apache.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\diagnostico\verificar-http.ps1
```

Abrir **http://127.0.0.1:8088/ingresar** en esta computadora. MariaDB debe estar iniciado en XAMPP. No es necesario mover el proyecto a `htdocs`: Apache utiliza exclusivamente `publico/` como DocumentRoot. El parámetro `-ExecutionPolicy Bypass` afecta únicamente al proceso invocado; no cambia la política del equipo.

| Usuario demostrativo | Contraseña |
| --- | --- |
| prueba1 | DemoSegura2026!A |
| prueba2 | DemoSegura2026!B |

Las credenciales son exclusivas de demostración local. Los correos son `prueba1@example.test` y `prueba2@example.test`. El seed calcula hashes con `password_hash()` y no sobrescribe cuentas existentes.

Para detener solo el Apache propio, sin operaciones en curso:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\detener-apache.ps1
```

## Estructura y organización

```text
documentos/
├── aplicacion/
│   ├── controladores/       Coordinación de formularios y documentos
│   ├── modelos/             Todas las consultas de la aplicación
│   ├── vistas/              autenticacion/, documentos/, componentes/
│   ├── servicios/           Coordinación de disco y límites de acceso
│   ├── intermediarios/      Sesión, CSRF y cabeceras
│   ├── validadores/         MIME, tamaños y contenedores Office
│   ├── nucleo/              PDO, router, sesiones y composición HTML
│   └── inicio.php           Entorno, autocarga y utilidades documentadas
├── configuracion/
│   ├── rutas.php            Única definición de rutas PHP configurables
│   ├── seguridad/           Límites de aplicación
│   ├── almacenamiento/     Formatos permitidos
│   └── servidor/            VirtualHost convencional de referencia
├── base_datos/
│   ├── estructura/          Creación de base, tablas e índices/relaciones
│   ├── migraciones/         Instalación y migración conservadora
│   └── datos_prueba/        Semilla explícita de usuarios
├── publico/
│   ├── index.php            Única entrada PHP pública
│   └── recursos/            estilos/, scripts/, externos/
├── pruebas/
│   ├── unitarias/           Validaciones de contenido y límites
│   ├── integracion/         Peticiones HTTP y pruebas de aislamiento
│   ├── soporte/             Consultas exclusivas de las aserciones
│   ├── interfaz/            Automatización Chrome y capturas
│   └── ejecutar.ps1         Ejecución en una base aislada
├── documentacion/           instalacion/, arquitectura/, base_datos/, manuales/, evidencias/
├── herramientas/            instalacion/, servidor/, diagnostico/, mantenimiento/
└── almacenamiento/          Sesiones, registros y temporales ignorados por Git
```

El PHP local descargado está en `herramientas/ejecucion/` y queda fuera de Git. Los directorios generados se crean cuando se necesitan; no hay carpetas de relleno. La raíz contiene únicamente directorios y archivos técnicos: README, entorno, atributos/ignore Git y protección Apache.

## Tecnologías y versiones verificadas

PHP **8.5.12 x64 Thread Safe, VS17**, tanto CLI como HTTP `apache2handler`; Apache **2.4.58 Win64** reutilizado desde XAMPP; MariaDB **10.4.32**; Bootstrap **5.3.8** local; PDF.js **6.4.299** local. Extensiones HTTP: `pdo_mysql`, `fileinfo`, `mbstring`, `openssl` y `zip`. cURL se carga únicamente en CLI para las pruebas. No se necesita Laravel, React, Node.js ni Composer.

La instalación global de XAMPP conserva PHP 8.0.30 y no se considera actualizada por este proyecto. La aplicación utiliza su propia instancia Apache con PHP corregido, configuración y PID separados; no modifica proyectos ni DLL globales.

PHP 8.5.12 se verificó por SHA-256 y mediante petición HTTP. Fuentes oficiales consultadas: [aviso PHP de seguridad](https://www.php.net/index.php), [descargas Windows y compatibilidad TS/NTS](https://www.php.net/downloads.php?os=windows), [versiones soportadas](https://www.php.net/supported-versions.php), [Apache Friends](https://www.apachefriends.org/). La revisión incluye los parches indicados para CVE-2026-109618 y CVE-2026-109619. No se certifica la plataforma local para producción.

## Instalación, datos y almacenamiento

Seguir [la guía de instalación](documentacion/instalacion/guia.md). La base activa es `documentos`, con tablas `usuarios`, `documentos` e `intentos_autenticacion`, nombres de columnas en español y restricciones InnoDB/utf8mb4.

El almacenamiento de documentos está en **`C:\AlmacenamientoPrivado\GestorArchivos\documentos`**, físicamente fuera de la carpeta pública. Puede cambiarse mediante `RUTA_DOCUMENTOS` en `.env`; ningún controlador o modelo contiene esa ruta física. La reubicación de esta entrega fue autorizada, se verificó SHA-256 y se conservaron los originales. No cambiar la ruta de una instalación existente sin autorizar y verificar sus copias.

El archivo `.env` contiene credenciales locales y no se publica. `.env.example` documenta las claves en español. No se presupone una contraseña vacía de root; el instalador interactivo la solicita sin mostrarla y la transmite por stdin.

La migración copió datos hacia bases nuevas y verificó los valores de todas las filas. Conservó IDs, propietarios, hashes, fechas, nombres físicos y rutas relativas, así como las bases anteriores para recuperación. La única traducción de columnas antiguas se mantiene en el adaptador de migración; no forma parte de las consultas activas.

## Uso y decisiones de seguridad

Registro, inicio/cierre de sesión, resumen, búsqueda por nombre, filtros por tipo y paginación. Cada usuario puede subir, visualizar, descargar y eliminar sus documentos. Los PDF se renderizan en canvas con navegación de páginas; imágenes se muestran directamente y TXT/DOCX/XLSX se descargan. El borrado exige confirmación mediante diálogo.

| Ruta | Métodos |
| --- | --- |
| /ingresar | GET, POST |
| /registrarse | GET, POST |
| /salir | POST |
| / | GET |
| /documentos | GET |
| /documentos/subir | GET, POST |
| /documentos/{id}/vista-previa | GET |
| /documentos/{id}/ver | GET |
| /documentos/{id}/descargar | GET |
| /documentos/{id}/eliminar | POST |

- Las consultas de listado y estadísticas filtran por propietario. Ver, descargar y eliminar consultan simultáneamente ID y usuario obtenido de sesión; ningún `id_usuario` recibido autoriza una operación. Archivo ajeno e inexistente responden 404.
- PDO usa parámetros preparados, búsqueda LIKE con comodines escapados y restricciones UNIQUE. No hay SQL en vistas, controladores ni servicios; las herramientas SQL y las aserciones tienen sus capas de datos separadas.
- `password_hash()`/`password_verify()`, mínimo ocho caracteres Unicode, máximo 72 bytes, renovación del ID y rehash cuando corresponde. Cookies HttpOnly/SameSite=Lax, Secure bajo HTTPS y expiración de 30 minutos por inactividad.
- Tokens CSRF para todos los POST, incluidos acceso y salida. Máximo diez intentos por IP/categoría en ventanas de quince minutos, persistidos en MariaDB. No se confía en cabeceras proxy del cliente.
- Máximo **10 485 760 bytes (10 MiB, equivalente a PHP 10M)** por documento; cuerpo POST máximo 12 MiB. Se valida el tamaño real, errores PHP, archivo vacío, extensión, MIME mediante fileinfo, correspondencia del contenido, nombres UTF-8 y cabecera de imágenes.
- Se permiten PDF, JPG/JPEG, PNG, TXT UTF-8, DOCX y XLSX para documentos comunes. No se aceptan scripts, ejecutables ni HTML/SVG. Se inspeccionan contenedores Office sin extraerlos; se bloquean macros/incrustaciones y expansión mayor de 50 MiB.
- IDs aleatorios de 128 bits, nombres físicos aleatorios de 256 bits sin extensión y comprobación `realpath` del almacenamiento privado. Los originales solo son metadatos escapados para HTML.
- CSP sin scripts inline, `nosniff`, caché privada desactivada, políticas de frames y referencia. El contenido PDF tiene sandbox y PDF.js dibuja solo páginas, sin capas de scripts/formularios/enlaces; se limita el lienzo a ocho millones de píxeles.
- Carga y eliminación tienen compensación de fallos entre disco y SQL. Los binarios apartados durante eliminación usan el sufijo `.eliminando`. Hay diagnóstico y conciliación administrativa para interrupciones; no se afirma atomicidad entre disco y base.
- Los errores públicos no muestran SQL ni rutas internas; las trazas van a registros privados. La cuenta web tiene permisos mínimos por tabla y no utiliza root.

Diagnóstico no destructivo:

```powershell
& .\herramientas\ejecucion\php-8.5.12\php.exe .\herramientas\mantenimiento\reconciliar_documentos.php
```

La aplicación de correcciones requiere revisar el diagnóstico, autorizar la operación y detener Apache; consultar el script. Respaldar base y documentos conjuntamente. No exponer XAMPP ni phpMyAdmin a Internet.

## Pruebas y entrega

Se ejecutaron **19 pruebas unitarias y 43 HTTP**, todas correctas, más comprobaciones en Chrome a 1440×1000 y 390×844. Incluyen IDOR con dos usuarios, integridad del archivo tras ataques, CSRF, SQL Injection, Path Traversal, sesión expirada, MIME fingido, límite exacto de tamaño y eliminación propia. Ver [pruebas y evidencias](documentacion/manuales/pruebas.md).

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\iniciar-apache.ps1 -Pruebas -Puerto 8089
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas\ejecutar.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas\interfaz\navegador.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\detener-apache.ps1 -Pruebas
```

La base `documentos_pruebas` y su almacenamiento/cookies/sesiones son independientes. La interfaz automatizada utiliza esa base, sin capturar documentos personales. Conserva usuarios de registro de prueba y elimina las cargas creadas por la suite. El perfil usa cuatro segundos de inactividad para probar expiración. Las pruebas y sus consultas se niegan a ejecutarse sobre la base principal.

El [reporte final](documentacion/arquitectura/reorganizacion.md), el [modelo y diccionario](documentacion/base_datos/modelo.md), la [matriz de requisitos](documentacion/arquitectura/requisitos.md) y los [avisos de terceros](documentacion/arquitectura/dependencias.md) forman parte de la entrega. `form` se consultó como referencia visual y se conservó sin cambios; no se incluye en Git ni en el paquete.

La interfaz conserva la navegación lateral, los paneles claros, el azul y la adaptación móvil de la referencia. No añade funciones para inspeccionar cuentas ajenas.

Limitaciones: no hay antivirus/CDR, cuota total, papelera, recuperación de contraseña, MFA ni validación semántica exhaustiva de documentos. PDFs cifrados se descargan para abrirlos en otra aplicación. No se han realizado pruebas de carga, fallos de energía reales, auditoría externa ni certificación de producción. El aislamiento protege usuarios web; no sustituye permisos del sistema operativo frente a un administrador local.

Git conserva su historial y añade commits reales de reorganización; no se reescribe la historia. Los nombres de la aplicación anterior solo pueden persistir en ese historial y en respaldos locales fuera de la aplicación activa. `.gitignore` excluye entornos, documentos, registros, sesiones, binarios PHP, perfiles Chrome y respaldos. No se publica el repositorio ni se envía correo sin autorización.
