<?php
/**
 * Archivo: aplicacion/inicio.php
 * Propósito: Inicializa los recursos compartidos de toda la aplicación.
 * Funcionamiento: Lee el entorno, registra la carga de clases, prepara directorios privados y define funciones de presentación.
 */

declare(strict_types=1);
/** Prepara el entorno antes de atender peticiones: configuración, autocarga, rutas y utilidades. */
// Punto de referencia para resolver archivos sin depender de la carpeta desde la que se ejecuta PHP.
define('RAIZ', dirname(__DIR__));
// DOCUMENTOS_ENTORNO permite otra instalación; por defecto se lee el archivo privado de configuracion/.
define('ARCHIVO_ENTORNO', getenv('DOCUMENTOS_ENTORNO') ?: RAIZ . '/configuracion/configuracion.env');
// Cada línea aporta una variable; los valores del proceso prevalecen para admitir configuración del servidor.
foreach (is_file(ARCHIVO_ENTORNO) ? file(ARCHIVO_ENTORNO, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $linea) {
    if (str_starts_with(trim($linea), '#') || !str_contains($linea, '=')) continue;
    [$clave, $valor] = explode('=', $linea, 2);
    if (getenv(trim($clave)) === false) putenv(trim($clave) . '=' . trim($valor));
}
// Carga automática: busca cada clase de Aplicacion en su carpeta solo cuando se utiliza.
spl_autoload_register(function (string $clase): void {
    /** Resuelve únicamente clases propias; el espacio de nombres determina su responsabilidad. */
    if (!str_starts_with($clase, 'Aplicacion\\')) return;
    // Se retira «Aplicacion\» y se convierte la primera carpeta al nombre físico en minúsculas.
    $partes = explode('\\', substr($clase, 11));
    $partes[0] = strtolower($partes[0]);
    $ruta = RAIZ . '/aplicacion/' . implode('/', $partes) . '.php';
    if (is_file($ruta)) require $ruta;
});
// Configuración compartida: rutas físicas, límites de seguridad y catálogo de formatos.
define('RUTAS', require RAIZ . '/configuracion/rutas.php');
define('PARAMETROS_SEGURIDAD', require RAIZ . '/configuracion/seguridad/parametros.php');
define('FORMATOS_PERMITIDOS', require RAIZ . '/configuracion/almacenamiento/formatos.php');
foreach (['documentos', 'sesiones', 'registros', 'temporal'] as $directorio) {
    // Solo estas carpetas de datos se crean al ejecutar; todas quedan fuera del proyecto.
    if (!is_dir(RUTAS[$directorio]) && !mkdir(RUTAS[$directorio], 0700, true)) throw new RuntimeException('No se pudo preparar el almacenamiento interno.');
}
// Fechas de Guatemala y errores técnicos enviados al registro privado.
date_default_timezone_set('America/Guatemala');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', RUTAS['registros'] . '/php.log');

/** Escapa un valor dinámico para HTML; impide que nombres y mensajes incorporen etiquetas. */
function escapar(mixed $valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
/** Devuelve el campo CSRF de la sesión que debe acompañar cualquier formulario POST. */
function campoCsrf(): string { return '<input type="hidden" name="_token" value="' . escapar(\Aplicacion\Nucleo\Sesion::token()) . '">'; }
/** Redirige después de una operación; recibe una ruta local y termina la petición. */
function redirigir(string $ruta): never { header('Location: ' . $ruta, true, 303); exit; }
/** Convierte bytes a una descripción legible para estadísticas y metadatos. */
function tamanoLegible(int $tamano): string { return $tamano >= 1048576 ? number_format($tamano / 1048576, 1) . ' MB' : number_format($tamano / 1024, 1) . ' KB'; }
