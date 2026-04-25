<?php

/**
 * src/Controller/User/CreateCommand.php
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
use Throwable;

class CreateCommand
{
    use TraitController;

    // constructor receives container instance
    public function __construct(
        private readonly ORM\EntityManager $entityManager
    ) { }

    /**
     * POST /api/v1/users
     *
     * Summary: Creates a new user (with PUBLICO role)
     *
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function __invoke(Request $request, Response $response): Response
    {
        assert($request->getMethod() === 'POST');
        /** @var array<string, string> $req_data */
        $req_data = $request->getParsedBody() ?? [];

        if (!isset($req_data['email'], $req_data['password'])
          || !$this->verifyStringInput($req_data['email'], 60)
        ) { // 422 - Faltan datos
            return Error::createResponse($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
        }

        // hay datos -> procesarlos
        $userRepository = $this->entityManager->getRepository(User::class);
        // STATUS_BAD_REQUEST 400: e-mail already exists
        if (0 !== $this->findByAttribute($userRepository, 'email', $req_data['email'])) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }

        // 201
        try {
            assert(strlen($req_data['email']) > 0);
            $user = new User(
                email: $req_data['email'],
                password: $req_data['password']
            );
        } catch (Throwable) {    // 400 BAD REQUEST: Unexpected EMAIL
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $response
            ->withAddedHeader(
                'Location',
                $request->getUri() . '/' . $user->getId()
            )
            ->withJson($user, StatusCode::STATUS_CREATED);
    }
}
