<?php

/**
 * src/Controller/User/ReadUsernameQuery.php
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

readonly class ReadUseremailQuery
{
    use TraitController;

    // constructor receives container instance
    public function __construct(
        private ORM\EntityManager $entityManager
    ) { }

    /**
     * GET /api/v1/users/email/{email}
     *
     * Summary: Returns status code 204 if _email_ exists (or 404 if not)
     *
     * @param Request $request
     * @param Response $response
     * @param array<string, mixed> $args
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        assert(in_array($request->getMethod(), [ 'GET', 'HEAD' ], true));
        $user = $this->findByAttribute(
            $this->entityManager->getRepository(User::class),
            'email',
            $args['email']
        );

        return ($user !== 0)
            ? $response->withStatus(StatusCode::STATUS_NO_CONTENT)       // 204
            : Error::createResponse($response, StatusCode::STATUS_NOT_FOUND); // 404
    }
}
