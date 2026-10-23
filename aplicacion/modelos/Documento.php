<?php
declare(strict_types=1);
namespace Aplicacion\Modelos;
use Aplicacion\Nucleo\Conexion;
/** Repositorio de documentos: todas las operaciones web filtran por propietario. */
final class Documento
{
    /** Recibe ID y propietario autenticado; devuelve la fila propia o false y evita IDOR. */
    public static function delPropietario(string $identificador, int $usuario): array|false
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $identificador)) return false;
        // Se exige ID y propietario en la misma consulta; conocer una URL no concede acceso al archivo.
        $consulta = Conexion::obtener()->prepare('SELECT * FROM documentos WHERE id_documento=? AND id_usuario=?');
        $consulta->execute([$identificador, $usuario]);
        return $consulta->fetch();
    }
    /** Devuelve hasta 25 documentos del propietario con filtros preparados y comodines escapados. */
    public static function listar(int $usuario, string $busqueda = '', string $tipo = '', int $pagina = 1): array
    {
        $sql = 'SELECT * FROM documentos WHERE id_usuario=?'; $parametros = [$usuario];
        // Los filtros solo se agregan cuando tienen valor. Los comodines del texto se escapan para buscar literalmente.
        if ($busqueda !== '') { $sql .= " AND nombre_original LIKE ? ESCAPE '='"; $parametros[] = '%' . strtr($busqueda, ['=' => '==', '%' => '=%', '_' => '=_']) . '%'; }
        if ($tipo !== '') { $sql .= ' AND extension=?'; $parametros[] = $tipo; }
        $desplazamiento = (max(1, min(100000, $pagina)) - 1) * 25;
        // OFFSET se calcula con enteros acotados; los datos escritos por el usuario siguen siendo parámetros preparados.
        $consulta = Conexion::obtener()->prepare($sql . ' ORDER BY fecha_carga DESC, id_documento DESC LIMIT 25 OFFSET ' . $desplazamiento);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }
    /** Recibe el propietario y devuelve cantidades y bytes de su espacio sin consultar otras cuentas. */
    public static function estadisticas(int $usuario): array
    {
        $consulta = Conexion::obtener()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(tamano_bytes),0) AS utilizado, COUNT(DISTINCT extension) AS tipos FROM documentos WHERE id_usuario=?');
        $consulta->execute([$usuario]); return $consulta->fetch();
    }
    /** Inserta metadatos ya validados; recibe ID, propietario, nombre interno y ruta generados. */
    public static function crear(array $parametros): void
    {
        Conexion::obtener()->prepare('INSERT INTO documentos (id_documento,id_usuario,nombre_original,nombre_almacenado,tipo_mime,extension,tamano_bytes,ruta_relativa) VALUES (?,?,?,?,?,?,?,?)')->execute($parametros);
    }
    /** Bloquea una fila propia dentro de la transacción de eliminación; nunca busca solo por ID. */
    public static function bloquearPropietario(string $identificador,int $usuario): array|false
    {
        $consulta=Conexion::obtener()->prepare('SELECT * FROM documentos WHERE id_documento=? AND id_usuario=? FOR UPDATE');
        $consulta->execute([$identificador,$usuario]); return $consulta->fetch();
    }
    /** Elimina la fila bloqueada únicamente si sigue perteneciendo al usuario autenticado. */
    public static function eliminar(string $identificador,int $usuario): void
    {
        Conexion::obtener()->prepare('DELETE FROM documentos WHERE id_documento=? AND id_usuario=?')->execute([$identificador,$usuario]);
    }
    /** Devuelve metadatos para conciliación CLI administrativa; no se usa en rutas web. */
    public static function paraMantenimiento(): array { return Conexion::obtener()->query('SELECT * FROM documentos')->fetchAll(); }
    /** Comprueba un nombre físico durante recuperación CLI, sin aceptar rutas del navegador. */
    public static function existeNombre(int $usuario,string $nombre): bool
    {
        $consulta=Conexion::obtener()->prepare('SELECT id_documento FROM documentos WHERE id_usuario=? AND nombre_almacenado=?');
        $consulta->execute([$usuario,$nombre]); return (bool)$consulta->fetch();
    }
}
