<?php
declare(strict_types=1);
// Solo CLI. Para HTTP, scripts/verify-http.ps1 crea una sonda aleatoria y la elimina.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$extensions = [];
foreach (['pdo_mysql','fileinfo','mbstring','openssl','zip'] as $name) $extensions[$name] = extension_loaded($name);
$ok = PHP_VERSION === '8.5.12' && !in_array(false, $extensions, true);
echo json_encode(['php' => PHP_VERSION, 'sapi' => PHP_SAPI, 'x64' => PHP_INT_SIZE === 8, 'thread_safe' => (bool)PHP_ZTS, 'ini' => php_ini_loaded_file(), 'extensions' => $extensions, 'ready' => $ok], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($ok ? 0 : 1);
