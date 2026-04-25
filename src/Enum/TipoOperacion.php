<?php

/**
 * src/Model/TipoOperacion.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Enum;

/**
 * @Enum({ "VUELO", "TREN" })
 *
 * @psalm-immutable
 */
enum TipoOperacion: string
{
    case VUELO = 'vuelo';
    case TREN = 'tren';

    /**
     * All possible values of the TipoOperacion enum.
     */
    public final const array ALL_VALUES = [
        TipoOperacion::VUELO->value,
        TipoOperacion::TREN->value,
    ];
}
