<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class StoredFile
{
    public static function owned(string $id, int $user): array|false
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) return false;
        $q = Database::get()->prepare('SELECT * FROM files WHERE id=? AND user_id=?');
        $q->execute([$id, $user]);
        return $q->fetch();
    }
    public static function listing(int $user, string $search = '', string $type = '', int $page = 1): array
    {
        $sql = 'SELECT * FROM files WHERE user_id=?'; $params = [$user];
        if ($search !== '') { $sql .= " AND original_name LIKE ? ESCAPE '='"; $params[] = '%' . strtr($search, ['=' => '==', '%' => '=%', '_' => '=_']) . '%'; }
        if ($type !== '') { $sql .= ' AND extension=?'; $params[] = $type; }
        $offset = (max(1, min(100000, $page)) - 1) * 25;
        $q = Database::get()->prepare($sql . ' ORDER BY created_at DESC, id DESC LIMIT 25 OFFSET ' . $offset);
        $q->execute($params);
        return $q->fetchAll();
    }
    public static function stats(int $user): array
    {
        $q = Database::get()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(file_size),0) AS used, COUNT(DISTINCT extension) AS types FROM files WHERE user_id=?');
        $q->execute([$user]); return $q->fetch();
    }
}
