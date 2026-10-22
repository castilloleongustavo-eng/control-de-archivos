<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
if (!in_array('--create', $argv, true)) { fwrite(STDERR, "Uso: credenciales JSON por stdin | php scripts/init-db.php --create\n"); exit(1); }
// Credenciales administrativas solo por stdin, nunca argumentos ni archivos del proyecto.
$admin = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$host = $admin['host'] ?? '127.0.0.1';
$port = (int)($admin['port'] ?? 3306);
$test = in_array('--test', $argv, true);
$name = $test ? 'admosa_file_manager_test' : 'admosa_file_manager';
$appUser = $test ? 'admosa_test' : 'admosa_app';
$envPath = $test ? ROOT . '/tests/.runtime/.env' : ROOT . '/.env';
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $admin['user'], $admin['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$exists = $pdo->query("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$name'")->fetchColumn();
if ($exists || is_file($envPath)) { fwrite(STDERR, "La base o .env ya existe; no se reemplaza. Usar importación manual documentada.\n"); exit(1); }
if ((int)$pdo->query("SELECT COUNT(*) FROM mysql.user WHERE User='$appUser' AND Host='127.0.0.1'")->fetchColumn()) { fwrite(STDERR, "La cuenta de aplicación ya existe; no se modifica.\n"); exit(1); }
$password = bin2hex(random_bytes(24));
$pdo->exec(str_replace('admosa_file_manager', $name, file_get_contents(ROOT . '/database/schema.sql')));
$pdo->exec("CREATE USER '$appUser'@'127.0.0.1' IDENTIFIED BY " . $pdo->quote($password));
$pdo->exec("GRANT SELECT, INSERT, UPDATE ON $name.users TO '$appUser'@'127.0.0.1'");
$pdo->exec("GRANT SELECT, INSERT, DELETE ON $name.files TO '$appUser'@'127.0.0.1'");
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON $name.auth_attempts TO '$appUser'@'127.0.0.1'");
$timeout = $test ? 4 : 1800;
$env = "APP_NAME=ADMOSA File Manager\nDB_HOST=$host\nDB_PORT=$port\nDB_NAME=$name\nDB_USER=$appUser\nDB_PASS=$password\nSESSION_TIMEOUT=$timeout\n";
if ($test && !is_dir(dirname($envPath))) mkdir(dirname($envPath), 0700, true);
if ($test) foreach (['private','logs','sessions'] as $dir) {
    $folder = ROOT . '/tests/.runtime/storage/' . $dir;
    if (!is_dir($folder)) mkdir($folder, 0700, true);
}
$env .= 'APP_PROFILE=' . ($test ? 'test' : 'demo') . "\n";
$handle = fopen($envPath, 'x');
if ($handle === false) throw new RuntimeException('No se pudo crear .env; la base permanece para inspección manual.');
fwrite($handle, $env); fclose($handle);
echo "Base, tablas y cuenta de permisos mínimos creadas. .env local generado; secretos omitidos.\n";
