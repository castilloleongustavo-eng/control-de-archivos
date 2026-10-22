<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Session, View};
use App\Models\User;
use App\Services\RateLimiter;
final class AuthController
{
    public function form(string $mode): void { View::render('auth', ['mode' => $mode, 'title' => $mode === 'login' ? 'Iniciar sesión' : 'Crear cuenta']); }
    public function register(): void
    {
        RateLimiter::hit('register');
        $name = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]{3,40}$/D', $name)) $error = 'El usuario debe tener entre 3 y 40 letras, números o guiones bajos.';
        elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Ingresa un correo electrónico válido.';
        elseif (!mb_check_encoding($password, 'UTF-8') || mb_strlen($password) < 8 || strlen($password) > 72) $error = 'La contraseña debe tener al menos 8 caracteres y un máximo de 72 bytes.';
        elseif ($password !== ($_POST['password_confirmation'] ?? '')) $error = 'Las contraseñas no coinciden.';
        else {
            try { User::create($name, $email, $password); }
            catch (\PDOException $ex) { if ($ex->getCode() !== '23000') throw $ex; $error = 'El usuario o correo electrónico ya está registrado.'; }
        }
        if (isset($error)) { http_response_code(422); View::render('auth', ['mode' => 'register', 'title' => 'Crear cuenta', 'error' => $error]); return; }
        Session::flash('Cuenta creada. Ya puedes iniciar sesión.', 'success');
        redirect('/login');
    }
    public function login(): void
    {
        RateLimiter::hit('login');
        $login = substr(trim((string)($_POST['login'] ?? '')), 0, 254);
        $password = (string)($_POST['password'] ?? '');
        $user = User::find($login);
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (!password_verify($password, $hash) || !$user) {
            http_response_code(422);
            View::render('auth', ['mode' => 'login', 'title' => 'Iniciar sesión', 'error' => 'Usuario o contraseña incorrectos.']);
            return;
        }
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) \App\Core\Database::get()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        $_SESSION = ['user' => ['id' => (int)$user['id'], 'username' => $user['username']], 'last_activity' => time()];
        redirect('/');
    }
    public function logout(): void { Session::destroy(); redirect('/login'); }
}
