<?php

/**
 * src/Model/TipoPunto.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Enum;

/**
 * @Enum({ "PUERTA", "VIA" })
 *
 * @psalm-immutable
 */
enum TipoPunto: string
{
    case PUERTA = 'PUERTA';
    case VIA = 'VIA';

    /**
     * All possible values of the TipoPunto enum.
     */
    public final const array ALL_VALUES = [
        TipoPunto::PUERTA->value,
        TipoPunto::VIA->value,
    ];
}
