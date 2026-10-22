USE documentos;
-- Unicidad de identidad y nombre físico; optimización del listado por propietario.
ALTER TABLE usuarios ADD CONSTRAINT usuarios_nombre_unico UNIQUE (nombre_usuario), ADD CONSTRAINT usuarios_correo_unico UNIQUE (correo);
ALTER TABLE documentos ADD CONSTRAINT documentos_nombre_unico UNIQUE (nombre_almacenado), ADD INDEX documentos_propietario_fecha (id_usuario,fecha_carga), ADD CONSTRAINT documentos_propietario_fk FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT;
ALTER TABLE intentos_autenticacion ADD INDEX intentos_ventana (inicio_ventana);
