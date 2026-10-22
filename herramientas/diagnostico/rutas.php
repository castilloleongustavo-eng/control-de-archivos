<?php
/** Expone únicamente las rutas al lanzador CLI; nunca credenciales ni información HTTP. */
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__,2).'/aplicacion/inicio.php';
echo json_encode(RUTAS,JSON_UNESCAPED_SLASHES);
