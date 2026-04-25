<?php

/**
 * src/Model/SentidoOperacion.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Enum;

/**
 * @Enum({ "SALIDA", "LLEGADA" })
 *
 * @psalm-immutable
 */
enum SentidoOperacion: string
{
    case SALIDA = 'salida';
    case LLEGADA = 'llegada';

    /**
     * All possible values of the SentidoOperacion enum.
     */
    public final const array ALL_VALUES = [
        SentidoOperacion::SALIDA->value,
        SentidoOperacion::LLEGADA->value,
    ];
}
