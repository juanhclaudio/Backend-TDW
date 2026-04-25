<?php

/**
 * src/Model/Role.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Enum;

/**
 * @Enum({ "inactivo", "publico", "gestor" })
 *
 * @psalm-immutable
 */
enum Role: string
{
    // scope names (roles)
    case INACTIVO = 'inactivo';
    case PUBLICO = 'publico';
    case GESTOR = 'gestor';

    /**
     * All possible values of the Role enum.
     */
    public final const array ALL_VALUES = [
        Role::INACTIVO->value,
        Role::PUBLICO->value,
        Role::GESTOR->value,
    ];

    /**
     * Checks if the current role matches the given role.
     *
     * @param Role $role The role to compare with.
     * @return bool True if the roles match, otherwise false.
     */
    public function is(Role $role): bool
    {
        return $this->value === $role->value;
    }
}
