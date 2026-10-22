# Evidencias de pruebas

Entorno: PHP 8.5.12 CLI y `apache2handler`, Apache 2.4.58, MariaDB 10.4.32. Ejecuciones locales del 21 y 22/10/2026. Los detalles y marcas temporales de cada ejecución están en los JSON adjuntos.

## Resultados ejecutados

| Área | Resultado |
| --- | --- |
| Sintaxis PHP | 28 archivos revisados sin errores |
| Sintaxis PowerShell | Scripts revisados mediante Parser de PowerShell |
| Unitarias | 19 correctas, 0 fallidas |
| HTTP contra Apache, base test | 43 correctas, 0 fallidas |
| Runtime HTTP | PHP 8.5.12, x64 TS, extensiones requeridas activas, carga 10M / POST 12M |
| Chrome real, escritorio y móvil | Login, carga, selección, PDF renderizado en canvas, tabla, diálogo, foco, eliminación y logout |

`unit-results.json` y `http-results.json` registran únicamente resultados obtenidos al ejecutar los scripts. Las primeras ejecuciones de las unitarias detectaron un fixture que libmagic clasificaba como binario; se sustituyó por texto real y se repitió la suite. El primer arranque Apache detectó dependencias de cURL/OpenSSL; se cargaron bibliotecas del PHP nuevo mediante rutas explícitas y cURL quedó limitado al CLI de pruebas. No se cambiaron binarios de XAMPP.

## Casos obligatorios

| Caso | Evidencia |
| --- | --- |
| Registro correcto | 303; cuenta nueva persistida |
| Registro duplicado | 422 |
| Contraseña menor de 8 caracteres | 422, incluidos cuatro caracteres Unicode de ocho bytes |
| Login válido | 303, cookie regenerada con HttpOnly y SameSite |
| Contraseña incorrecta / SQL Injection login | 422 |
| Acceso sin sesión | 303 al login |
| Subida de PDF / PNG | 303, registro y binario propios |
| Más de 10 MB | 413; prueba unitaria exacta de 10 MiB y +1 byte |
| Extensión ejecutable / MIME fingido | 422 |
| Archivo vacío / nombre XSS | 422 |
| Vista propia | 200, contenido PDF idéntico y disposición inline |
| Descarga propia | 200, contenido idéntico y attachment |
| Eliminación propia | 303, binario y fila eliminados, acceso posterior 404 |
| Logout | 303; descarga posterior sin sesión redirigida |
| Expiración | 303 al login tras inactividad de 5 s en perfil de 4 s |
| Búsqueda, filtros / SQL Injection búsqueda | 200; filtrado y cero resultados para payload SQL |
| Traversal / ID inválido | Rechazo sin exponer configuración |
| Sin token CSRF | 403 |
| Acceso directo a storage y .env | 404 / 403 |
| GET de eliminación | 405 |
| Limitación de intentos | Primeros 10 inválidos: 422; siguiente: 429 y Retry-After |

## Aislamiento entre dos cuentas

1. La suite inicia sesión como prueba1 y crea un PDF con nombre único.
2. Registra el identificador, propietario y SHA-256 del binario.
3. Cierra sesión e inicia sesión como prueba2.
4. Comprueba la ausencia del nombre en el listado, incluso con `user_id=1` en query string.
5. Prueba `{id}/view`, `{id}/preview` y `{id}/download`: **404**.
6. Envía POST `{id}/delete` con token válido de prueba2 y propietario ajeno en el cuerpo: **404**. Sin token: **403**.
7. Comprueba que la fila sigue siendo del propietario original y que el hash no cambió.
8. Regresa al propietario y elimina correctamente.

Los resultados de todos esos pasos son correctos en `http-results.json`. Un `user_id` falsificado en carga también se ignora: la fila queda vinculada a la sesión.

## Comprobaciones manuales reproducibles

- Abrir la URL local, probar registro, login y logout, navegar con teclado y confirmar el foco visible.
- Subir PDF/JPEG/PNG/TXT/DOCX/XLSX legítimos del evaluador; abrir/descargar los propios. Imágenes directas y PDF en canvas; Office y texto se descargan. La vista PDF se renderiza con PDF.js local; comprobar sus páginas y el botón de descarga.
- Probar búsquedas sin resultados y archivos con nombres largos y acentos.
- En pantalla móvil, recorrer navegación, formulario, filtros y scroll interno de tabla.
- Abrir el diálogo, cancelar con botón o Escape y confirmar eliminación de otro archivo.
- Repetir aislamiento con dos perfiles de navegador y peticiones editadas desde herramientas de desarrollo.
- Confirmar que `.env`, `storage/`, `app/`, `database/` y logs no se sirven por HTTP.
- Para despliegues HTTPS, verificar además la cookie Secure; la entrega loopback HTTP prueba HttpOnly y SameSite.

Estos pasos son una guía adicional para el evaluador. No se presentan como ejecutadas las pruebas de formatos reales del evaluador, HTTPS, concurrencia, inyección de fallos del disco o recuperación tras un corte de energía.

## Capturas

- `screenshots/login-desktop.png`, `registro-desktop.png` y `login-mobile.png`.
- `screenshots/dashboard-desktop.png`, `dashboard-mobile.png`.
- `screenshots/upload-desktop.png`, `upload-mobile.png`.
- `screenshots/files-desktop.png`, `delete-dialog.png` y `pdf-preview-desktop.png`.

Las capturas contienen únicamente cuentas y documentos de demostración. No se incluye el perfil Chrome, cookies ni secretos de la base.

La revisión visual inicial detectó que el iframe con sandbox bloqueaba el visor PDF de Chrome pese a responder HTTP 200. Se sustituyó por PDF.js local sin scripts de documento y se comprobó el canvas renderizado mediante automatización y captura visual. Los informes finales corresponden a la implementación corregida.
