<?php
declare(strict_types=1);
namespace Aplicacion\Validadores;
use Aplicacion\Nucleo\ExcepcionHttp;
/** Inspecciona contenido real antes de moverlo a disco; rechaza MIME fingido y contenedores peligrosos. */
final class ValidadorDocumento
{
    public const LIMITE_BYTES = PARAMETROS_SEGURIDAD['limite_documento_bytes'];
    public const TIPOS_PERMITIDOS = FORMATOS_PERMITIDOS;
    /** Recibe ruta temporal y nombre; devuelve metadatos tras validar MIME, tamaño, extensión y nombre UTF-8. */
    public function inspeccionar(string $ruta, string $nombre): array
    {
        // Primero se valida el nombre completo para impedir rutas, caracteres de control y extensiones engañosas.
        if ($nombre === '' || !mb_check_encoding($nombre, 'UTF-8') || mb_strlen($nombre) > 180 || preg_match('/[\x00-\x1f\x7f<>:"\/\\\\|?*]/u', $nombre) || str_contains($nombre, '..') || trim($nombre, " .") !== $nombre) throw new ExcepcionHttp(422, 'El nombre del archivo contiene caracteres no permitidos.');
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!isset(self::TIPOS_PERMITIDOS[$extension]) || preg_match('/\.(php\d*|phtml|phar|exe|dll|bat|cmd|ps1|js|html?|svg|com|scr)(\.|$)/i', $nombre)) throw new ExcepcionHttp(422, 'Formato no permitido. Usa PDF, JPG, PNG, TXT, DOCX o XLSX.');
        $tamano = filesize($ruta);
        if ($tamano === false || $tamano === 0) throw new ExcepcionHttp(422, 'No se permiten archivos vacíos.');
        if ($tamano > self::LIMITE_BYTES) throw new ExcepcionHttp(413, 'El archivo supera el máximo de 10 MB.');
        $tipoMime = (new \finfo(FILEINFO_MIME_TYPE))->file($ruta);
        // MIME inspecciona el contenido real. DOCX y XLSX son ZIP y requieren validar también su estructura interna.
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            if (!in_array($tipoMime, [self::TIPOS_PERMITIDOS[$extension], 'application/zip', 'application/x-zip-compressed'], true)) throw new ExcepcionHttp(422, 'El contenido no corresponde a la extensión.');
            $this->validarOffice($ruta, $extension);
            $tipoMime = self::TIPOS_PERMITIDOS[$extension];
        } elseif ($tipoMime !== self::TIPOS_PERMITIDOS[$extension]) throw new ExcepcionHttp(422, 'El contenido no corresponde a la extensión.');
        if (in_array($extension, ['jpg','jpeg','png'], true) && @getimagesize($ruta) === false) throw new ExcepcionHttp(422, 'La imagen no es válida.');
        if ($extension === 'txt') {
            // Solo se admite texto UTF-8; el visor lo presenta mediante textContent, nunca como HTML.
            $texto = file_get_contents($ruta);
            if (!mb_check_encoding($texto, 'UTF-8') || str_contains($texto, "\0") || preg_match('/<\?(php|=)|<script\b/i', $texto)) throw new ExcepcionHttp(422, 'El archivo de texto debe ser UTF-8 y no contener scripts.');
        }
        return ['nombre_original' => $nombre, 'extension' => $extension, 'tipo_mime' => $tipoMime, 'tamano_bytes' => $tamano];
    }
    /** Verifica el contenedor Office sin extraerlo; bloquea macros, incrustaciones y expansión excesiva. */
    private function validarOffice(string $ruta, string $extension): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) throw new ExcepcionHttp(422, 'El documento Office no es válido.');
        try {
            $principal = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            if ($zip->locateName('[Content_Types].xml') === false || $zip->locateName('_rels/.rels') === false || $zip->locateName($principal) === false || $zip->numFiles > 2000) throw new ExcepcionHttp(422, 'El documento Office está incompleto.');
            $total = 0;
            // Se suma el tamaño descomprimido antes de aceptar el contenedor y se bloquean macros e incrustaciones.
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entrada = $zip->statIndex($i); $nombreEntrada = $entrada['name']; $total += $entrada['size'];
                if ($total > 52428800 || str_contains($nombreEntrada, '..') || str_starts_with($nombreEntrada, '/') || str_contains($nombreEntrada, '\\') || preg_match('/vbaProject|embeddings\/|\.(exe|dll|js|bin)$/i', $nombreEntrada)) throw new ExcepcionHttp(422, 'El documento contiene elementos no permitidos o excede el límite expandido.');
            }
        } finally { $zip->close(); }
    }
}
