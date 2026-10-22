<?php
declare(strict_types=1);
namespace Aplicacion\Nucleo;
use PDO;
/** Conexión compartida de la capa de datos: utf8mb4, errores privados y preparación nativa. */
final class Conexion
{
    private static ?PDO $conexion = null;
    /** Devuelve PDO usando las credenciales del entorno; no acepta datos del navegador. */
    public static function obtener(): PDO
    {
        if (self::$conexion === null) {
            $servidor = getenv('BD_SERVIDOR') ?: '127.0.0.1';
            $puerto = getenv('BD_PUERTO') ?: '3306';
            $nombre = getenv('BD_NOMBRE') ?: 'documentos';
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $nombre)) throw new \RuntimeException('Nombre de base de datos inválido.');
            self::$conexion = new PDO("mysql:host=$servidor;port=$puerto;dbname=$nombre;charset=utf8mb4", getenv('BD_USUARIO') ?: 'documentos_app', getenv('BD_CONTRASENA') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        }
        return self::$conexion;
    }
}
