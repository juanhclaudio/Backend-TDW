<?php

/**
 * src/Controller/Spot/SpotCommandController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Controller\Spot;

use Doctrine\ORM;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Enum\TipoPunto;
use TDW\IPanel\Model\Punto;
use TDW\IPanel\Utility\Error;

/**
 * Class SpotCommandController
 */
class SpotCommandController
{
    use TraitController;

    // constructor - receives the EntityManager from container instance
    public function __construct(
        protected ORM\EntityManager $entityManager
    ) { }

    /**
     * Summary: Creates a new Spot
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function post(Request $request, Response $response): Response
    {
        assert($request->getMethod() === 'POST');

        return Error::createResponse($response, StatusCode::STATUS_NOT_IMPLEMENTED);
    }

    /**
     * Summary: Updates an element
     *
     * @param Request $request
     * @param Response $response
     * @param array<string, mixed> $args
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function put(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'PUT');

        return Error::createResponse($response, StatusCode::STATUS_NOT_IMPLEMENTED);
    }

    /**
     * Summary: Remove an item
     *
     * @param Request $request
     * @param Response $response
     * @param array<string, mixed> $args
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'DELETE');

        return Error::createResponse($response, StatusCode::STATUS_NOT_IMPLEMENTED);
    }
}
