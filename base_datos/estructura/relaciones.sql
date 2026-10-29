-- Archivo: base_datos/estructura/relaciones.sql
-- Propósito: Añade restricciones e índices al esquema.
-- Funcionamiento: Evita identidades duplicadas, vincula documentos con usuarios y agiliza listados y limpieza de intentos.

USE documentos;
-- Unicidad de identidad y nombre físico; optimización del listado por propietario.
-- Impide repetir usuario o correo aunque dos registros ocurran simultáneamente.
ALTER TABLE usuarios ADD CONSTRAINT usuarios_nombre_unico UNIQUE (nombre_usuario), ADD CONSTRAINT usuarios_correo_unico UNIQUE (correo);
-- La clave foránea exige un usuario existente; RESTRICT impide borrarlo mientras conserva documentos.
ALTER TABLE documentos ADD CONSTRAINT documentos_nombre_unico UNIQUE (nombre_almacenado), ADD INDEX documentos_propietario_fecha (id_usuario,fecha_carga), ADD CONSTRAINT documentos_propietario_fk FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT;
-- El índice temporal agiliza la búsqueda y eliminación de ventanas vencidas.
ALTER TABLE intentos_autenticacion ADD INDEX intentos_ventana (inicio_ventana);
