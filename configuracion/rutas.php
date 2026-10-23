<?php
declare(strict_types=1);
/** Separa código, configuración local y datos privados; los datos de ejecución siempre son externos. */
$raiz = dirname(__DIR__);
$predeterminada = PHP_OS_FAMILY === 'Windows' ? 'C:/AlmacenamientoPrivado/GestorArchivos' : sys_get_temp_dir() . '/gestor-documentos';
$almacenamiento = rtrim(getenv('RUTA_DATOS') ?: $predeterminada, '/\\');
// Se normalizan segmentos para rechazar incluso rutas como «proyecto/otro/../temporal».
$normalizar = static function (string $ruta): string {
    $ruta = str_replace('\\', '/', $ruta);
    if (!preg_match('~^(?:[a-zA-Z]:/|/)~', $ruta)) throw new RuntimeException('Las rutas privadas deben ser absolutas.');
    $partes = [];
    foreach (explode('/', $ruta) as $parte) {
        if ($parte === '..') array_pop($partes);
        elseif ($parte !== '' && $parte !== '.') $partes[] = $parte;
    }
    return strtolower(implode('/', $partes));
};
$comprobar = static function (string $ruta) use ($raiz, $normalizar): string {
    // Comparar rutas normalizadas evita que una variante de barras o mayúsculas eluda la separación.
    $base = $normalizar(realpath($raiz) ?: $raiz);
    $destino = $normalizar(realpath($ruta) ?: $ruta);
    if ($destino === $base || str_starts_with($destino, $base . '/')) throw new RuntimeException('El almacenamiento debe permanecer fuera del proyecto.');
    return $ruta;
};
// La configuración puede estar en el proyecto, pero nunca en la carpeta que Apache publica.
$publicoNormalizado = $normalizar($raiz . '/publico');
if (str_starts_with($normalizar(ARCHIVO_ENTORNO), $publicoNormalizado . '/')) throw new RuntimeException('La configuración no puede estar en publico/.');
return [
    'raiz' => $raiz,
    'aplicacion' => $raiz . '/aplicacion',
    'vistas' => $raiz . '/aplicacion/vistas',
    'publico' => $raiz . '/publico',
    'datos' => $comprobar($almacenamiento),
    // El archivo contiene secretos locales: permanece en configuracion/ y se excluye de Git.
    'entorno' => ARCHIVO_ENTORNO,
    'documentos' => $comprobar(getenv('RUTA_DOCUMENTOS') ?: $almacenamiento . '/documentos'),
    'sesiones' => $comprobar($almacenamiento . '/sesiones'),
    'registros' => $comprobar($almacenamiento . '/registros'),
    'temporal' => $comprobar($almacenamiento . '/temporal'),
    'estructura_sql' => $raiz . '/base_datos/estructura',
];
