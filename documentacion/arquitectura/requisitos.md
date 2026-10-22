# Requisitos de la prueba técnica

Fuente: PDF oficial ADMOSA, “Proyecto Técnico de Evaluación: Gestión de archivos por usuario”, versión 1.0 / 2026, leído desde Descargas. La referencia oficial se conserva como cita; no es la marca de la aplicación. El PDF no se incorpora a Git.

| Requisito | Implementación |
| --- | --- |
| Registro, duplicados y contraseña de ocho caracteres | ControladorAutenticacion, Usuario y restricciones UNIQUE |
| Inicio y cierre de sesión | Sesion, Seguridad y formularios con CSRF |
| Dos cuentas de prueba | Semilla CLI; credenciales en README |
| Carga de máximo 10 MB | ValidadorDocumento y límites PHP/Apache |
| Identificador único | 128 bits aleatorios; nombre físico de 256 bits |
| Listar solo archivos propios | Documento::listar y estadisticas por id_usuario de sesión |
| Abrir, descargar y eliminar propios | Consulta id_documento + id_usuario en cada operación |
| Rechazar URL y petición manipulada | 404 uniforme e integridad SHA-256 comprobada en pruebas |
| Archivos inaccesibles directamente | Almacenamiento físico fuera de publico/ |
| Separación de responsabilidades | MVC, validador independiente y SQL en modelos |
| Interfaz clara | Paneles, sidebar, filtros, diálogo, teclado y adaptación móvil |
| SQL, README, usuarios e historial Git | base_datos/, README, semilla e historial conservado |

La organización, código y nombres de base de datos en español responden a las instrucciones adicionales del usuario. No se elimina ninguna funcionalidad del PDF. Las bases originales se conservan para recuperación y no se usan en las peticiones activas. Publicar o enviar el enlace al evaluador requiere autorización adicional.
