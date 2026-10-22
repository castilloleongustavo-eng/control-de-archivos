<?php
declare(strict_types=1);
/** Rutas únicas de la aplicación. La ubicación privada se configura sin cambiar modelos. */
$raiz = dirname(__DIR__);
$pruebas = getenv('PERFIL_APLICACION') === 'pruebas';
$almacenamiento = $pruebas ? $raiz . '/pruebas/.runtime/almacenamiento' : $raiz . '/almacenamiento';
return [
    'raiz' => $raiz,
    'aplicacion' => $raiz . '/aplicacion',
    'vistas' => $raiz . '/aplicacion/vistas',
    'publico' => $raiz . '/publico',
    'documentos' => $pruebas ? $almacenamiento . '/documentos' : (getenv('RUTA_DOCUMENTOS') ?: 'C:/AlmacenamientoPrivado/GestorArchivos/documentos'),
    'sesiones' => $almacenamiento . '/sesiones',
    'registros' => $almacenamiento . '/registros',
    'temporal' => $almacenamiento . '/temporal',
    'pruebas' => $raiz . '/pruebas/.runtime',
    'evidencias' => $raiz . '/documentacion/evidencias',
    'estructura_sql' => $raiz . '/base_datos/estructura',
];
