<?php
declare(strict_types=1);
namespace Aplicacion\Modelos;
use Aplicacion\Nucleo\Conexion;
/** Centraliza las consultas de usuarios; nunca devuelve contraseñas en texto plano. */
final class Usuario
{
    /** Busca por usuario o correo mediante parámetros preparados; devuelve fila o false. */
    public static function buscar(string $acceso): array|false
    {
        $consulta = Conexion::obtener()->prepare('SELECT * FROM usuarios WHERE nombre_usuario=? OR correo=? LIMIT 1');
        $consulta->execute([$acceso, $acceso]); return $consulta->fetch();
    }
    /** Guarda un usuario validado por el controlador; los índices únicos resuelven carreras. */
    public static function crear(string $nombre, string $correo, string $contrasena): void
    {
        Conexion::obtener()->prepare('INSERT INTO usuarios (nombre_usuario,correo,hash_contrasena) VALUES (?,?,?)')->execute([$nombre, $correo, password_hash($contrasena, PASSWORD_DEFAULT)]);
    }
    /** Actualiza el hash de un usuario ya autenticado cuando cambia el algoritmo recomendado. */
    public static function actualizarHash(int $identificador, string $contrasena): void
    {
        Conexion::obtener()->prepare('UPDATE usuarios SET hash_contrasena=? WHERE id_usuario=?')->execute([password_hash($contrasena, PASSWORD_DEFAULT), $identificador]);
    }
}
