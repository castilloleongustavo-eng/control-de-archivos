<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database, HttpException};
final class RateLimiter
{
    public static function hit(string $action): void
    {
        // IP real de la conexión; no confiar en X-Forwarded-For del cliente.
        $bucket = hash('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $pdo = Database::get();
        $pdo->prepare('DELETE FROM auth_attempts WHERE window_start < ?')->execute([time() - 900]);
        $pdo->prepare('INSERT INTO auth_attempts (bucket,attempts,window_start) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=attempts+1')->execute([$bucket, time()]);
        $q = $pdo->prepare('SELECT attempts FROM auth_attempts WHERE bucket=?');
        $q->execute([$bucket]);
        if ((int)$q->fetchColumn() > 10) {
            header('Retry-After: 900');
            throw new HttpException(429, 'Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.');
        }
    }
}
