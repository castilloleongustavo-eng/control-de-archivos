# Correspondencia con la prueba ADMOSA

Fuente leída: `Analista Desarrollador Jr  - Prueba Técnica.pdf`, Descargas, versión 1.0 / 2026, cuatro páginas. No se incluye el PDF en Git por contener material de terceros.

| Requisito del PDF | Implementación / evidencia |
| --- | --- |
| Registro y usuario/correo únicos, contraseña de 8 caracteres | AuthController, User, índices UNIQUE |
| Sesión por usuario | Session, Security::user |
| Dos cuentas de prueba | database/seed.php --demo; credenciales en README |
| Carga de máximo 10 MB | UploadValidator::MAX: 10 × 1024 × 1024 bytes |
| Identificador único | 16 bytes aleatorios, 32 dígitos hexadecimales |
| Ver solo archivos propios | WHERE user_id derivado de sesión en listado y estadísticas |
| Abrir, descargar y eliminar propios | FileController; consulta id + user_id; middleware |
| Impedir acceso ajeno con URL o petición manipulada | 404 uniforme; suite HTTP con dos usuarios |
| No servir archivos directamente | DocumentRoot public; almacenamiento fuera de public |
| Contraseñas cifradas de forma apropiada | password_hash y password_verify; no cifrado reversible |
| Separar presentación, lógica y datos | MVC ligero, Models, Services y Views |
| Interfaz clara | Bootstrap local, responsive, formularios etiquetados, diálogo |
| README, SQL y credenciales demo | README, schema.sql, seed.php |
| Historial Git y enlace | Repositorio local; requiere identidad para commits y autorización para publicar |

El PDF permite escoger lenguaje, formatos y motor. Se usa la elección más específica del texto adjunto: PHP nativo, MariaDB, Bootstrap 5 y siete extensiones permitidas. No hay contradicción con el PDF. El correo de entrega indicado por el PDF no autoriza envío automático: la publicación y el envío quedan a cargo del usuario salvo instrucción expresa.
