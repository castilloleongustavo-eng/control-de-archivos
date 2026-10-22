<?php
declare(strict_types=1);
use Aplicacion\Nucleo\Conexion;
/** Capa de datos exclusiva de las aserciones CLI; se niega a operar sobre la base principal. */
final class DatosPrueba
{
    /** Requiere perfil y base aislados antes de permitir limpieza de contadores de prueba. */
    public function __construct() { if(PHP_SAPI!=='cli'||getenv('BD_NOMBRE')!=='documentos_pruebas')throw new RuntimeException('Perfil de pruebas requerido.'); }
    /** Reinicia únicamente los intentos de la base de pruebas para permitir repetir la suite. */
    public function limpiarIntentos(): void { Conexion::obtener()->exec('DELETE FROM intentos_autenticacion'); }
    /** Busca el documento recién generado por la suite para capturar su ID real. */
    public function porNombre(string $nombre): array|false { $consulta=Conexion::obtener()->prepare('SELECT * FROM documentos WHERE nombre_original=? ORDER BY fecha_carga DESC LIMIT 1');$consulta->execute([$nombre]);return $consulta->fetch(); }
    /** Devuelve el propietario almacenado para comprobar que una petición no alteró su identidad. */
    public function propietario(string $identificador): int { $consulta=Conexion::obtener()->prepare('SELECT id_usuario FROM documentos WHERE id_documento=?');$consulta->execute([$identificador]);return (int)$consulta->fetchColumn(); }
    /** Devuelve la existencia de la fila tras el POST de eliminación. */
    public function cantidad(string $identificador): int { $consulta=Conexion::obtener()->prepare('SELECT COUNT(*) FROM documentos WHERE id_documento=?');$consulta->execute([$identificador]);return (int)$consulta->fetchColumn(); }
    /** Devuelve exclusivamente IDs del usuario de prueba para limpiar sus cargas HTTP. */
    public function delUsuario(int $usuario): array { $consulta=Conexion::obtener()->prepare('SELECT id_documento FROM documentos WHERE id_usuario=?');$consulta->execute([$usuario]);return $consulta->fetchAll(); }
}
