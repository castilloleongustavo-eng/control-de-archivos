<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class User
{
    public static function find(string $login): array|false
    {
        $q = Database::get()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $q->execute([$login, $login]);
        return $q->fetch();
    }
    public static function create(string $name, string $email, string $password): void
    {
        Database::get()->prepare('INSERT INTO users (username,email,password_hash) VALUES (?,?,?)')->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    }
}
