USE documentos;
-- Identidad de las cuentas. El hash no permite recuperar la contraseña original.
CREATE TABLE usuarios (
 id_usuario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre_usuario VARCHAR(40) NOT NULL,
 correo VARCHAR(254) NOT NULL,
 hash_contrasena VARCHAR(255) NOT NULL,
 fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cuentas con contraseñas irreversibles';
-- Solo metadatos: los binarios permanecen físicamente fuera del DocumentRoot.
CREATE TABLE documentos (
 id_documento CHAR(32) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 id_usuario BIGINT UNSIGNED NOT NULL,
 nombre_original VARCHAR(180) NOT NULL,
 nombre_almacenado CHAR(64) CHARACTER SET ascii NOT NULL,
 tipo_mime VARCHAR(100) NOT NULL,
 extension VARCHAR(5) NOT NULL,
 tamano_bytes BIGINT UNSIGNED NOT NULL,
 ruta_relativa VARCHAR(100) NOT NULL,
 fecha_carga TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos privados vinculados a una cuenta';
-- Persistencia de límites por IP y categoría; no almacena datos de las contraseñas.
CREATE TABLE intentos_autenticacion (
 clave CHAR(64) CHARACTER SET ascii PRIMARY KEY,
 contador INT UNSIGNED NOT NULL DEFAULT 0,
 inicio_ventana BIGINT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Ventanas de intentos de autenticación';
