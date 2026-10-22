<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\HttpException;
final class UploadValidator
{
    public const MAX = 10485760;
    public const TYPES = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'txt' => 'text/plain', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    public function inspect(string $path, string $name): array
    {
        if ($name === '' || !mb_check_encoding($name, 'UTF-8') || mb_strlen($name) > 180 || preg_match('/[\x00-\x1f\x7f<>:"\/\\\\|?*]/u', $name) || str_contains($name, '..') || trim($name, " .") !== $name) throw new HttpException(422, 'El nombre del archivo contiene caracteres no permitidos.');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext]) || preg_match('/\.(php\d*|phtml|phar|exe|dll|bat|cmd|ps1|js|html?|svg|com|scr)(\.|$)/i', $name)) throw new HttpException(422, 'Formato no permitido. Usa PDF, JPG, PNG, TXT, DOCX o XLSX.');
        $size = filesize($path);
        if ($size === false || $size === 0) throw new HttpException(422, 'No se permiten archivos vacíos.');
        if ($size > self::MAX) throw new HttpException(413, 'El archivo supera el máximo de 10 MB.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (in_array($ext, ['docx', 'xlsx'], true)) {
            if (!in_array($mime, [self::TYPES[$ext], 'application/zip', 'application/x-zip-compressed'], true)) throw new HttpException(422, 'El contenido no corresponde a la extensión.');
            $this->office($path, $ext);
            $mime = self::TYPES[$ext];
        } elseif ($mime !== self::TYPES[$ext]) throw new HttpException(422, 'El contenido no corresponde a la extensión.');
        if (in_array($ext, ['jpg','jpeg','png'], true) && @getimagesize($path) === false) throw new HttpException(422, 'La imagen no es válida.');
        if ($ext === 'txt') {
            $text = file_get_contents($path);
            if (!mb_check_encoding($text, 'UTF-8') || str_contains($text, "\0") || preg_match('/<\?(php|=)|<script\b/i', $text)) throw new HttpException(422, 'El archivo de texto debe ser UTF-8 y no contener scripts.');
        }
        return ['original_name' => $name, 'extension' => $ext, 'mime_type' => $mime, 'file_size' => $size];
    }
    private function office(string $path, string $ext): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) throw new HttpException(422, 'El documento Office no es válido.');
        try {
            $main = $ext === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('_rels/.rels') === false || $zip->locateName($main) === false || $zip->numFiles > 2000) throw new HttpException(422, 'El documento Office está incompleto.');
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i); $n = $entry['name']; $total += $entry['size'];
                if ($total > 52428800 || str_contains($n, '..') || str_starts_with($n, '/') || str_contains($n, '\\') || preg_match('/vbaProject|embeddings\/|\.(exe|dll|js|bin)$/i', $n)) throw new HttpException(422, 'El documento contiene elementos no permitidos o excede el límite expandido.');
            }
        } finally { $zip->close(); }
    }
}
