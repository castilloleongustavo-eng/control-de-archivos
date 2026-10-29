<?php
/**
 * Archivo: aplicacion/nucleo/Conexion.php
 * Propósito: Proporciona una conexión PDO compartida a MariaDB.
 * Funcionamiento: Lee credenciales del entorno y crea la conexión una sola vez por ejecución, con UTF-8 y consultas preparadas nativas.
 */

declare(strict_types=1);
namespace Aplicacion\Nucleo;
use PDO;
/** Conexión compartida de la capa de datos: utf8mb4, errores privados y preparación nativa. */
final class Conexion
{
    // Conserva la conexión durante esta ejecución y evita abrir otra en cada consulta.
    private static ?PDO $conexion = null;
    /** Devuelve PDO usando las credenciales del entorno; no acepta datos del navegador. */
    public static function obtener(): PDO
    {
        if (self::$conexion === null) {
            // La contraseña se lee de configuracion/configuracion.env; nunca se incorpora al código del modelo.
            if (getenv('BD_CONTRASENA') === false) throw new \RuntimeException('Configura la conexión mediante herramientas/configurar.ps1.');
            $servidor = getenv('BD_SERVIDOR') ?: '127.0.0.1';
            $puerto = getenv('BD_PUERTO') ?: '3306';
            $nombre = getenv('BD_NOMBRE') ?: 'documentos';
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $nombre)) throw new \RuntimeException('Nombre de base de datos inválido.');
            // Configura excepciones SQL, filas como arreglos asociativos y preparación nativa de parámetros.
            self::$conexion = new PDO("mysql:host=$servidor;port=$puerto;dbname=$nombre;charset=utf8mb4", getenv('BD_USUARIO') ?: 'documentos_app', getenv('BD_CONTRASENA') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
        }
        return self::$conexion;
    }
}
