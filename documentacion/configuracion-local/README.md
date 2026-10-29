# Configuración local y archivos de apoyo

Esta carpeta reúne plantillas publicables para reproducir la instalación. Los valores privados se completan únicamente en la computadora donde se ejecuta el proyecto.

| Archivo o componente | Plantilla o referencia | Ubicación que utiliza la aplicación |
| --- | --- | --- |
| Variables de entorno | [configuracion.env.example](configuracion.env.example) | `configuracion/configuracion.env` |
| Configuración del motor PHP | [php.ini.example](php.ini.example) | `herramientas/ejecucion/php-8.5.12/php.ini` |
| Reglas de Apache para rutas | [publico/.htaccess](../../publico/.htaccess) | `publico/.htaccess`, cuando Apache permite su lectura |
| Configuración convencional de Apache | [virtualhost.ejemplo.conf](../../configuracion/servidor/virtualhost.ejemplo.conf) | Archivo de sitios de la instalación Apache elegida |
| Exclusiones de Git | [.gitignore](../../.gitignore) | `.gitignore` en la raíz del repositorio |
| Configuración de la instancia local | [servidor.ps1](../../herramientas/servidor.ps1) | `RUTA_DATOS/temporal/apache-local.conf`, generado al arrancar |

## Preparación recomendada

Desde la raíz del proyecto, prepara el motor PHP con [preparar-php.ps1](../../herramientas/preparar-php.ps1) y la conexión con [configurar.ps1](../../herramientas/configurar.ps1), siguiendo la [instalación del README principal](../../README.md#-instalación-desde-cero). Estos scripts generan la configuración que utiliza el proyecto.

La plantilla de entorno de esta carpeta contiene los mismos valores de ejemplo que la [plantilla principal](../../configuracion/configuracion.env.example). Es una copia de consulta; si se incorporan variables nuevas deben actualizarse ambas. `aplicacion/inicio.php` lee por defecto `configuracion/configuracion.env`, por lo que una plantilla guardada aquí no activa ni reemplaza la configuración real.

## Configuración manual

1. Si ya tienes una base preparada y una cuenta técnica con permisos, copia `configuracion.env.example` a `configuracion/configuracion.env`, únicamente si el archivo de destino todavía no existe.
2. Completa `BD_SERVIDOR`, `BD_PUERTO`, `BD_NOMBRE`, `BD_USUARIO` y `BD_CONTRASENA` con los valores de esa instalación.
3. Configura `RUTA_DATOS` y `RUTA_DOCUMENTOS` como rutas absolutas fuera del proyecto. Si existen documentos, conserva la ubicación de su almacén.
4. Usa `php.ini.example` solo como referencia manual: sustituye `RUTA_ABSOLUTA_PHP` y `RUTA_ABSOLUTA_REGISTROS` antes de copiarlo a la instalación del motor. El procedimiento recomendado genera estas rutas automáticamente.
5. Valida el resultado desde la raíz:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor.ps1 -Accion validar
   ```

## Qué se conserva localmente

| Elemento | Motivo y forma de obtenerlo |
| --- | --- |
| `configuracion/configuracion.env` | Contiene la contraseña real de la cuenta técnica. Lo genera el asistente y Git lo excluye. Se comparte su plantilla sin credenciales. |
| `herramientas/ejecucion/` | Contiene motores, DLL y paquetes de instalación descargados. PHP se prepara con el script; las copias que utiliza el navegador de PDF.js y fflate sí están en `publico/recursos/externos/`. |
| `RUTA_DATOS/sesiones/` | Sesiones activas y tokens; los crea PHP durante el uso. |
| `RUTA_DATOS/registros/` | Registros técnicos locales, generados por PHP y Apache. |
| `RUTA_DATOS/temporal/` | PID, configuración Apache y cargas temporales, generados al ejecutar. |
| `RUTA_DOCUMENTOS/` | Documentos de los usuarios, guardados por la aplicación fuera de la carpeta pública. |
| `.git/` | Historial y metadatos locales de Git. Se crea al clonar o inicializar el repositorio; no forma parte del código publicado. |

El proyecto no carga automáticamente un archivo llamado `.env` en la raíz. Para seleccionar otro archivo real de configuración se utiliza la variable de proceso `DOCUMENTOS_ENTORNO`. Los valores ya definidos en el proceso tienen prioridad sobre el archivo.
