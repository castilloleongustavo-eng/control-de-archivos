<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
if (!in_array('--demo', $argv, true)) { fwrite(STDERR, "Uso explícito: php database/seed.php --demo (solo desarrollo local)\n"); exit(1); }
$pdo = App\Core\Database::get();
$insert = $pdo->prepare('INSERT INTO users (username,email,password_hash) VALUES (?,?,?)');
$exists = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');
foreach (['prueba1' => 'DemoSegura2026!A', 'prueba2' => 'DemoSegura2026!B'] as $name => $password) {
    $email = $name . '@example.test';
    $exists->execute([$name, $email]);
    if ($exists->fetch()) { echo "$name ya existe; no se modifica.\n"; continue; }
    $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    echo "$name creado como cuenta demostrativa.\n";
}
