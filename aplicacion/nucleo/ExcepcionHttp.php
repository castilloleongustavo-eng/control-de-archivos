<?php
/**
 * Archivo: aplicacion/nucleo/ExcepcionHttp.php
 * Propósito: Representa un error que puede mostrarse al usuario.
 * Funcionamiento: Transporta el estado HTTP y el mensaje público hasta el manejador de errores de publico/index.php.
 */

declare(strict_types=1);
namespace Aplicacion\Nucleo;
/** Error público con estado HTTP; las trazas técnicas permanecen en registros privados. */
final class ExcepcionHttp extends \RuntimeException
{
    /** Recibe el estado HTTP y un mensaje público; conserva la separación de errores técnicos y visibles. */
    public function __construct(public readonly int $estado, string $mensaje) { parent::__construct($mensaje); }
}
