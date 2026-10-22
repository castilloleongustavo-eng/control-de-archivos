# Pruebas y demostración

Ejecutar desde la raíz de `documentos/`. La base `documentos_pruebas`, las cookies y las carpetas privadas de pruebas están aisladas de los documentos reales. Las herramientas no se ejecutan sobre la base principal.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\diagnostico\validar-sintaxis.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\iniciar-apache.ps1 -Pruebas -Puerto 8089
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas\ejecutar.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\pruebas\interfaz\navegador.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File .\herramientas\servidor\detener-apache.ps1 -Pruebas
```

Resultado verificado: **19 unitarias y 43 HTTP correctas**, ninguna fallida; además revisión Chrome de escritorio/móvil. Los informes JSON en `documentacion/evidencias/` corresponden a ejecuciones reales. Las capturas se generan usando exclusivamente la base de pruebas y documentos sintéticos.

| Grupo | Casos ejecutados |
| --- | --- |
| Registro | Correcto, duplicado, contraseña corta y caracteres Unicode |
| Acceso | Login correcto/incorrecto, SQL Injection, regeneración de sesión y cookies |
| Sesión | Acceso sin autenticación, logout e inactividad con caducidad de prueba |
| Carga | PDF y PNG, nombre inválido/XSS, MIME fingido, archivo vacío y extensión ejecutable |
| Tamaño | Exactamente 10 MiB, un byte adicional y carga HTTP superior al límite |
| Documentos | Vista, previsualización, descarga y eliminación propios |
| Aislamiento | Listado, estadísticas y endpoints de otro usuario; id_usuario manipulado |
| Seguridad | CSRF ausente, eliminación mediante GET, acceso a almacenamiento/.env y Path Traversal |
| Límites | Diez intentos fallidos y respuesta 429 con Retry-After |
| Office | Contenedores DOCX/XLSX, macros bloqueadas y ruta de metadatos manipulada |
| Interfaz | Formulario, selección/carga, tabla, diálogo con foco, PDF en canvas y salida |

Prueba crítica: prueba1 sube un PDF y sale; prueba2 no lo encuentra y recibe 404 al ver, previsualizar, descargar o eliminar con un token CSRF válido suyo, incluso falseando id_usuario. Sin token recibe 403. Después se verifican propietario y hash SHA-256 intactos; únicamente prueba1 puede eliminarlo.

La interfaz se comprueba a 1440×1000 y 390×844, sin desbordamiento horizontal de la página. La tabla conserva desplazamiento dentro de su contenedor. Los PDF se dibujan mediante PDF.js local; la captura y el atributo de renderizado confirman contenido real, no solo un endpoint 200.

Para revisión manual: iniciar como prueba1, subir un documento propio de menos de 10 MB, buscarlo, abrir su vista previa y descargarlo. Guardar el ID, salir e iniciar como prueba2; probar ese ID para obtener 404. Regresar al propietario y eliminar con confirmación. Probar navegación con teclado, Escape en el diálogo, nombres largos y acentos. Las credenciales de demostración están en README.

No se presentan como ejecutadas pruebas de carga, HTTPS/cookie Secure, recuperación tras corte de energía real, auditoría externa ni todos los documentos Office/PDF posibles. El diagnóstico de consistencia es no destructivo y debe revisarse antes de autorizar reparaciones.
