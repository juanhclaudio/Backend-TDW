<?php

/**
 * src/Controller/User/DeleteCommand.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Controller\User;

use Doctrine\ORM;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Model\User;
use TDW\IPanel\Utility\Error;

class DeleteCommand
{
    use TraitController;

    public function __construct(
        private readonly ORM\EntityManager $entityManager
    ) { }

    /**
     * DELETE /api/v1/users/{userId}
     *
     * Summary: Deletes a user
     *
     * @param Request $request
     * @param Response $response
     * @param array<string, mixed> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'DELETE');
        if (!$this->checkGestorScope($request)) { // 403 => 404 por seguridad
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        if (!$this->verifyInputId($args['userId'] ?? 0)) { // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        $this->entityManager->beginTransaction();
        $user = $this->entityManager->getRepository(User::class)->find($args['userId']);

        if (!$user instanceof User) {    // 404
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->entityManager->commit();

        return $response
            ->withStatus(StatusCode::STATUS_NO_CONTENT);  // 204
    }
}
