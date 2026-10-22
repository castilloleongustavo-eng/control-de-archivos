<?php
declare(strict_types=1);
namespace Aplicacion\Nucleo;
/** Error público con estado HTTP; las trazas técnicas permanecen en registros privados. */
final class ExcepcionHttp extends \RuntimeException
{
    /** Recibe el estado HTTP y un mensaje público; conserva la separación de errores técnicos y visibles. */
    public function __construct(public readonly int $estado, string $mensaje) { parent::__construct($mensaje); }
}
