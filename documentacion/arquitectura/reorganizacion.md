# Reporte de organización y limpieza

## Estructura definitiva

La aplicación activa está en `documentos/`. Su estructura está descrita en README: aplicacion (controladores, modelos, vistas, servicios, intermediarios, validadores, nucleo); configuracion (rutas, seguridad, almacenamiento, servidor); base_datos (estructura, migraciones, datos_prueba); publico/recursos (estilos, scripts, externos); pruebas (unitarias, integracion, soporte, interfaz); documentacion (instalacion, arquitectura, base_datos, manuales, evidencias); herramientas (instalacion, servidor, diagnostico, mantenimiento, ejecucion ignorada); almacenamiento interno generado e ignorado.

La raíz solo contiene directorios y archivos técnicos indispensables. No hay PHP, CSS, JS, SQL, imágenes ni scripts sueltos en ella. No se añadieron carpetas vacías. El proyecto `form` permanece en su ubicación original, fuera de la aplicación activa y del paquete; se registraron y compararon los SHA-256 de sus **1,562 archivos**, sin cambios.

## Archivos reorganizados y código

Se reorganizaron inicialmente 49 archivos según su responsabilidad y después se añadieron configuraciones, modelos de datos, migración y documentación necesarias. El análisis por hash no encontró archivos propios idénticos. Se separó el validador del almacenamiento; las plantillas se clasificaron por autenticación, documentos y componentes. Las herramientas se agruparon por instalación, servidor, diagnóstico y mantenimiento; las pruebas separan unitarias, HTTP, soporte de datos e interfaz.

Clases, métodos, variables de aplicación, campos de formularios, rutas HTTP, selectores propios y archivos de código están en español. Se documentaron componentes y operaciones de seguridad, entradas y efectos. Se conservan sin traducción los nombres nativos de PHP/PDO, HTML, Apache, Bootstrap, Chrome DevTools y PDF.js. Las dependencias externas y sus licencias no se refactorizaron.

Las consultas web se concentran en Usuario, Documento e IntentoAutenticacion. Los controladores y servicios no contienen SQL. La instalación/migración tiene su capa dentro de base_datos y las aserciones usan un repositorio exclusivo dentro de pruebas/soporte. Se centralizaron rutas, formatos y límites; se preservan las consultas específicas con bloqueo porque cumplen una función distinta del acceso de lectura.

## Modelo de datos y migración

Base activa `documentos`; base separada `documentos_pruebas`. Tablas: usuarios, documentos e intentos_autenticacion, con campos snake_case en español. Relación documentos.id_usuario → usuarios.id_usuario, ON DELETE RESTRICT; identificadores y nombres físicos únicos; índices por propietario/fecha y caducidad. Los scripts SQL tienen comentarios y se separan en creación de base, tablas y relaciones/índices. Ver diccionario y diagrama en `documentacion/base_datos/modelo.md`.

Se copiaron y verificaron todas las filas desde el origen hacia bases nuevas; no se eliminaron las bases ni cuentas anteriores. En la principal se conservaron **2 usuarios y 1 documento**, con IDs, hashes de contraseñas, fechas y metadatos originales. El adaptador de migración mantiene los identificadores antiguos exclusivamente para leer/copiar instalaciones previas. Las consultas activas utilizan únicamente el modelo en español. La operación DDL no es atómica; una continuación administrativa compara filas y nunca sustituye datos distintos.

## Nombres y presentación

El título visible y la marca provisional son **Documentos**. Se retiraron las denominaciones anteriores de interfaz, títulos, mensajes, código activo, configuración, rutas y documentación de uso. La denominación oficial de la prueba ADMOSA se conserva solo cuando se cita el documento de evaluación. El historial Git no se reescribió. Los respaldos locales y la referencia externa se conservan fuera de la aplicación activa.

La interfaz mantiene navegación lateral, paneles claros, azul, controles de Bootstrap, adaptación móvil, confirmación accesible y visor PDF en canvas. `form` se utilizó exclusivamente para contrastar el estilo.

## Ruta física y conservación

Ruta activa: **`C:\AlmacenamientoPrivado\GestorArchivos\documentos`**, configurada mediante RUTA_DOCUMENTOS en `.env` y resuelta desde configuracion/rutas.php. Está fuera de publico/. Se autorizó la copia, se verificó SHA-256 y se conservó el archivo original en su ubicación anterior. El código y las herramientas se trasladaron a la carpeta neutral sin mover `form` ni el respaldo original.

La aplicación verifica sesión, ID, propietario y contención real de la ruta en cada operación. Los binarios no se sirven directamente. Sesiones, registros y temporales están clasificados e ignorados. Ningún secreto, documento privado, respaldo de datos o perfil de navegador se incluye en Git o en el ZIP de código.

## Pruebas y resultados

**19 pruebas unitarias y 43 HTTP correctas**, más Chrome en escritorio y móvil, con captura de PDF realmente renderizado. Se volvió a ejecutar la suite tras reorganizar. Las evidencias JSON indican fecha y resultados finales; el diagnóstico de consistencia no encontró incidencias. Se verificó PHP **8.5.12 x64 TS** por CLI y HTTP con las extensiones requeridas. El XAMPP global no fue actualizado ni se modificaron sus proyectos.

Se verificó la integridad del documento existente antes y después de las operaciones, su descarga propia HTTP 200 y el rechazo de descarga ajena HTTP 404. Las evidencias están en integridad-migracion.json e integridad-referencia.json. Las pruebas HTTP y de interfaz se ejecutan en una base y almacenamiento separados para no modificar ni capturar documentos personales. La matriz de escenarios y limitaciones está en `documentacion/manuales/pruebas.md`.

## Pendientes y límites

No hay una migración de datos ni una autorización de traslado pendiente. Las bases de origen, el documento original y la entrega previa se conservan como respaldo local y no se eliminan automáticamente. `form` permanece intacto en su ubicación original. La actualización global de XAMPP y una eventual publicación en GitHub requieren autorización específica y no forman parte de esta limpieza.

Se mantienen las limitaciones documentadas: sin antivirus/CDR, cuotas totales, MFA ni recuperación de contraseña; sin auditoría externa ni pruebas de carga o de cortes eléctricos reales. El sitio local y el aislamiento comprobado no equivalen a una certificación para exposición a Internet.
