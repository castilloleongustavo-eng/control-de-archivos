<?php
declare(strict_types=1);
namespace BaseDatos;
use PDO;
/** Prepara exclusivamente la base de esta aplicación; no depende de pruebas ni respaldos Git. */
final class Instalador
{
    /** Usa administración solo en CLI y guarda para la web una cuenta de permisos limitados. */
    public static function ejecutar(array $administrador, bool $crear = false): void
    {
        if (PHP_SAPI !== 'cli') throw new \RuntimeException('Operación disponible solo por consola.');
        if (is_file(RUTAS['entorno'])) throw new \RuntimeException('La configuración privada ya existe; no se sobrescribe.');
        $servidor = (string)($administrador['servidor'] ?? '127.0.0.1');
        $puerto = (int)($administrador['puerto'] ?? 3306);
        if (strpbrk($servidor, ";\r\n") !== false || $puerto < 1 || $puerto > 65535) throw new \RuntimeException('Servidor o puerto inválido.');
        $conexion = new PDO("mysql:host=$servidor;port=$puerto;charset=utf8mb4", $administrador['usuario'], $administrador['contrasena'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $existe = $conexion->query("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='documentos'")->fetchColumn();
        if ($crear) {
            if ($existe) throw new \RuntimeException('La base ya existe; utiliza --configurar para conservarla.');
            foreach (['crear_base.sql','esquema.sql','relaciones.sql'] as $archivo) $conexion->exec(file_get_contents(RUTAS['estructura_sql'].'/'.$archivo));
        } elseif (!$existe) throw new \RuntimeException('No existe la base documentos. Utiliza --crear para una instalación nueva.');
        // Se comprueba la estructura antes de cambiar la cuenta técnica; nunca se borran filas.
        foreach (['usuarios','documentos','intentos_autenticacion'] as $tabla) $conexion->query("SELECT 1 FROM documentos.$tabla LIMIT 1");
        $cantidad = (int)$conexion->query('SELECT COUNT(*) FROM documentos.documentos')->fetchColumn();
        $contrasena = bin2hex(random_bytes(24));
        $cuenta = "'documentos_app'@'127.0.0.1'";
        $conexion->exec("CREATE USER IF NOT EXISTS $cuenta IDENTIFIED BY ".$conexion->quote($contrasena));
        $conexion->exec("ALTER USER $cuenta IDENTIFIED BY ".$conexion->quote($contrasena));
        $conexion->exec("GRANT SELECT,INSERT,UPDATE ON documentos.usuarios TO $cuenta");
        $conexion->exec("GRANT SELECT,INSERT,DELETE ON documentos.documentos TO $cuenta");
        $conexion->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON documentos.intentos_autenticacion TO $cuenta");
        // La contraseña administrativa no se guarda. El archivo local de configuracion/ queda excluido de Git.
        $entorno = "NOMBRE_APLICACION=Control de archivos\nBD_SERVIDOR=$servidor\nBD_PUERTO=$puerto\nBD_NOMBRE=documentos\nBD_USUARIO=documentos_app\nBD_CONTRASENA=$contrasena\nSESION_INACTIVIDAD=1800\nRUTA_DATOS=".RUTAS['datos']."\nRUTA_DOCUMENTOS=".RUTAS['documentos']."\n";
        $directorio = dirname(RUTAS['entorno']);
        if (!is_dir($directorio) && !mkdir($directorio,0700,true)) throw new \RuntimeException('No se pudo crear el directorio de configuración.');
        $archivo = fopen(RUTAS['entorno'],'x');
        if ($archivo === false) throw new \RuntimeException('No se pudo guardar la configuración local.');
        try { if (fwrite($archivo,$entorno) !== strlen($entorno)) throw new \RuntimeException('La configuración no se guardó completa.'); }
        finally { fclose($archivo); }
        chmod(RUTAS['entorno'],0600);
        echo json_encode(['base'=>'documentos','documentos_conservados'=>$cantidad,'configuracion'=>RUTAS['entorno']],JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}
