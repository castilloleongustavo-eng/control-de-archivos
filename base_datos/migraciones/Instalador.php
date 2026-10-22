<?php
declare(strict_types=1);
namespace BaseDatos;
use PDO;
/** Crea una base nueva y copia datos sin alterar las tablas ni cuentas del origen. Solo CLI. */
final class Instalador
{
    /** Recibe credenciales administrativas por stdin y un perfil; preserva IDs, hashes y rutas. */
    public static function ejecutar(array $administrador,bool $pruebas,bool $migrar,bool $continuar=false): void
    {
        $nombre=$pruebas?'documentos_pruebas':'documentos'; $usuario=$pruebas?'documentos_pruebas_app':'documentos_app';
        $rutaEntorno=$pruebas?RAIZ.'/pruebas/.runtime/.env':RAIZ.'/.env';
        $servidor=$administrador['servidor']??'127.0.0.1';$puerto=(int)($administrador['puerto']??3306);
        $conexion=new PDO("mysql:host=$servidor;port=$puerto;charset=utf8mb4",$administrador['usuario'],$administrador['contrasena'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $consulta=$conexion->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');$consulta->execute([$nombre]);
        $baseExistente=(int)$consulta->fetchColumn()>0;
        if($baseExistente&&!$continuar) throw new \RuntimeException('La base destino ya existe; no se reemplaza.');
        $consulta=$conexion->prepare('SELECT COUNT(*) FROM mysql.user WHERE User=? AND Host=?');$consulta->execute([$usuario,'127.0.0.1']);
        if((int)$consulta->fetchColumn()) throw new \RuntimeException('La cuenta destino ya existe; no se modifica.');
        $origen=[];
        if($migrar){
            $resguardo=RAIZ.'/.git/resguardos-reorganizacion/'.($pruebas?'entorno-pruebas-original.env':'entorno-original.env');
            foreach(file($resguardo,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)as$linea){if(str_contains($linea,'=')){[$clave,$valor]=explode('=',$linea,2);$origen[$clave]=$valor;}}
            if(!preg_match('/^[a-zA-Z0-9_]+$/D',$origen['DB_NAME']??'')) throw new \RuntimeException('Base origen inválida.');
        }elseif(is_file($rutaEntorno)){throw new \RuntimeException('El entorno ya existe; no se sobrescribe.');}
        if(!$baseExistente) foreach(['crear_base.sql','esquema.sql','relaciones.sql']as$archivo){$sql=file_get_contents(RUTAS['estructura_sql'].'/'.$archivo);$sql=str_replace(['CREATE DATABASE IF NOT EXISTS documentos','USE documentos;'],['CREATE DATABASE IF NOT EXISTS '.$nombre,'USE '.$nombre.';'],$sql);$conexion->exec($sql);}
        $cantidades=[];
        if($migrar){
            // Única frontera de compatibilidad con columnas de la instalación previa.
            // Estas denominaciones identifican datos que se copian, no la aplicación activa.
            $tablas=[
                'users'=>['usuarios',['id'=>'id_usuario','username'=>'nombre_usuario','email'=>'correo','password_hash'=>'hash_contrasena','created_at'=>'fecha_registro','updated_at'=>'fecha_actualizacion']],
                'files'=>['documentos',['id'=>'id_documento','user_id'=>'id_usuario','original_name'=>'nombre_original','stored_name'=>'nombre_almacenado','mime_type'=>'tipo_mime','extension'=>'extension','file_size'=>'tamano_bytes','storage_path'=>'ruta_relativa','created_at'=>'fecha_carga']],
                'auth_attempts'=>['intentos_autenticacion',['bucket'=>'clave','attempts'=>'contador','window_start'=>'inicio_ventana']],
            ];
            foreach($tablas as$anterior=>[$nueva,$columnas]){
                $seleccion=implode(',',array_map(fn($antes,$despues)=>"`$antes` AS `$despues`",array_keys($columnas),array_values($columnas)));
                $campos=implode(',',array_map(fn($campo)=>"`$campo`",array_values($columnas)));
                if((int)$conexion->query("SELECT COUNT(*) FROM `$nombre`.`$nueva`")->fetchColumn()===0) $conexion->exec("INSERT INTO `$nombre`.`$nueva` ($campos) SELECT $seleccion FROM `{$origen['DB_NAME']}`.`$anterior`");
                $orden=reset($columnas);
                $datosOrigen=$conexion->query("SELECT $seleccion FROM `{$origen['DB_NAME']}`.`$anterior` ORDER BY 1")->fetchAll(PDO::FETCH_NUM);
                $datosDestino=$conexion->query("SELECT $campos FROM `$nombre`.`$nueva` ORDER BY 1")->fetchAll(PDO::FETCH_NUM);
                if(array_map(fn($fila)=>array_map('strval',$fila),$datosOrigen)!==array_map(fn($fila)=>array_map('strval',$fila),$datosDestino)) throw new \RuntimeException('La copia de datos no coincide; no se activa el nuevo entorno.');
                $cantidades[$nueva]=count($datosDestino);
            }
        }
        $contrasena=bin2hex(random_bytes(24));
        $conexion->exec("CREATE USER '$usuario'@'127.0.0.1' IDENTIFIED BY ".$conexion->quote($contrasena));
        $conexion->exec("GRANT SELECT,INSERT,UPDATE ON `$nombre`.usuarios TO '$usuario'@'127.0.0.1'");
        $conexion->exec("GRANT SELECT,INSERT,DELETE ON `$nombre`.documentos TO '$usuario'@'127.0.0.1'");
        $conexion->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON `$nombre`.intentos_autenticacion TO '$usuario'@'127.0.0.1'");
        $rutaDocumentos=$administrador['ruta_documentos']??(getenv('RUTA_DOCUMENTOS')?:'C:/AlmacenamientoPrivado/GestorArchivos/documentos');
        $perfil=$pruebas?'pruebas':'local';$inactividad=$pruebas?4:1800;
        $entorno="NOMBRE_APLICACION=Documentos\nBD_SERVIDOR=$servidor\nBD_PUERTO=$puerto\nBD_NOMBRE=$nombre\nBD_USUARIO=$usuario\nBD_CONTRASENA=$contrasena\nSESION_INACTIVIDAD=$inactividad\nPERFIL_APLICACION=$perfil\nRUTA_DOCUMENTOS=$rutaDocumentos\n";
        if(!is_dir(dirname($rutaEntorno)))mkdir(dirname($rutaEntorno),0700,true);
        if(is_file($rutaEntorno))copy($rutaEntorno,RAIZ.'/.git/resguardos-reorganizacion/entorno-antes-migracion-'.($pruebas?'pruebas':'local').'.env');
        if(file_put_contents($rutaEntorno,$entorno)===false)throw new \RuntimeException('No se pudo guardar el entorno.');
        if($pruebas&&!is_dir(RAIZ.'/pruebas/.runtime/almacenamiento/documentos'))mkdir(RAIZ.'/pruebas/.runtime/almacenamiento/documentos',0700,true);
        echo json_encode(['base'=>$nombre,'filas_verificadas'=>$cantidades,'origen_conservado'=>true],JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
