<?php

/**
 * src/Model/EstadoOperacion.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Enum;

/**
 * @Enum({ "PROGRAMADO", "EMBARCANDO", "RETRASADO", "CANCELADO", "EN_RUTA", "LLEGADO" })
 *
 * @psalm-immutable
 */
enum EstadoOperacion: string
{
    case PROGRAMADO = 'programado';
    case EMBARCANDO = 'embarcando';
    case RETRASADO  = 'retrasado';
    case CANCELADO  = 'cancelado';
    case EN_RUTA    = 'en ruta';
    case LLEGADO    = 'llegado';

    /**
     * All possible values of the EstadoOperacion enum.
     */
    public final const array ALL_VALUES = [
        EstadoOperacion::PROGRAMADO->value,
        EstadoOperacion::EMBARCANDO->value,
        EstadoOperacion::RETRASADO->value,
        EstadoOperacion::CANCELADO->value,
        EstadoOperacion::EN_RUTA->value,
        EstadoOperacion::LLEGADO->value,
    ];
}
