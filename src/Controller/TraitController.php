<?php

/**
 * src/Controller/User/TraitController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Lcobucci\JWT\Token\Plain;
use Psr\Http\Message\ServerRequestInterface as Request;
use TDW\IPanel\Enum\Role;

trait TraitController
{
    /**
     * Get the `userId` from token request
     *
     * @param Request $request Representation of an incoming server-side HTTP request
     *
     * @return int User id (0 if the information is not available)
     */
    private function getUserId(Request $request): int
    {
        /** @var Plain|null $token */
        $token = $request->getAttribute('token');
        return (int) $token?->claims()->get('uid', 0);
    }

    /**
     * Check from token request if user is GESTOR
     *
     * @param Request $request Representation of an incoming server-side HTTP request
     *
     * @return bool
     */
    private function checkGestorScope(Request $request): bool
    {
        /** @var Plain|null $token */
        $token = $request->getAttribute('token');
        $scopes = $token?->claims()->get('scopes');
        return is_array($scopes) && in_array(Role::GESTOR->value, $scopes, true);
    }

    /**
     * Returns the `id` of an entity if the `attribute` with a given `value` exists in
     * the database. Zero if it does not exist.
     *
     * @param EntityRepository $entityRepository
     * @param string $attribute
     * @param int|string $value
     * @return int entity id or zero
     */
    private function findByAttribute(EntityRepository $entityRepository, string $attribute, int|string $value): int
    {
        $criteria = new Criteria();
        $criteria
            ->where($criteria::expr()->eq($attribute, $value));
        $entity = $entityRepository->matching($criteria)->current();
        return is_object($entity) && method_exists($entity, 'getId') ? $entity->getId() : 0;
    }

    /**
     * Check that an input data item is a valid character string
     *
     * @param string|null $myString Character string
     * @param int $length Maximum length of the string
     * @return bool true if the string is valid, false otherwise
     */
    private function verifyStringInput(?string $myString, int $length = 80): bool
    {
        return is_string($myString)
            && strlen($myString) > 0
            && strlen($myString) <= $length;
    }

    /**
     * Check if the input data is a valid positive integer
     *
     * @param string|int $myInt Positive integer or integer string
     * @return bool true if the input is valid, false otherwise
     */
    private function verifyInputId(string|int $myInt): bool
    {
        return !(@intval($myInt) <= 0 || @intval($myInt) >= 2147483647)
            && $myInt > 0 && $myInt <= 2147483647;
    }
}
