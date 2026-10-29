-- Archivo: base_datos/estructura/crear_base.sql
-- Propósito: Crea y selecciona la base documentos.
-- Funcionamiento: Define la codificación UTF-8 completa y conserva la base si ya existe.

-- Base independiente de la aplicación. No elimina bases ni datos existentes.
-- IF NOT EXISTS conserva la base y sus datos cuando ya existe.
CREATE DATABASE IF NOT EXISTS documentos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Selecciona la base para las sentencias siguientes de esta conexión.
USE documentos;
