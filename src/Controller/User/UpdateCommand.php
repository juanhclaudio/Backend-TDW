<?php

/**
 * src/Controller/User/UpdateCommand.php
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

/**
 * Class UpdateCommand
 */
class UpdateCommand
{
    use TraitController;

    /** @var string ruta api gestión usuarios  */
    public const string PATH_USERS = '/users';

    // constructor receives container instance
    public function __construct(
        private readonly ORM\EntityManager $entityManager
    ) { }

    /**
     * PUT /api/v1/users/{userId}
     *
     * Summary: Updates a user
     * - A PUBLICO user can only modify their own properties
     * - A PUBLICO user cannot modify his ROLE
     *
     * @param Request $request
     * @param Response $response
     * @param array<non-empty-string,non-empty-string> $args
     *
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        assert($request->getMethod() === 'PUT');
        $isGestor = $this->checkGestorScope($request);
        $userRequestId = $this->getUserId($request);
        if (!$isGestor && intval($args['userId']) !== $userRequestId) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND); // 403 => 404 por seguridad
        }

        // Check the userId range: 2147483647 > userId > 0
        if (!$this->verifyInputId(intval($args['userId']))) { // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        /** @var array<string, string> $req_data */
        $req_data = $request->getParsedBody() ?? [];
        $this->entityManager->beginTransaction();
        /** @var User|null $userToModify */
        $userToModify = $this->entityManager->getRepository(User::class)->find($args['userId']);

        // Check whether the user exists
        if (!$userToModify instanceof User) {    // 404
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Optimistic Locking (strong validation) - https://httpwg.org/specs/rfc6585.html#status-428
        $etag = md5(json_encode($userToModify) . $userToModify->getPassword());
        if (!in_array($etag, $request->getHeader('If-Match'), true)) {
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);   // 428
        }

        // Checks whether the user with _email_ already exists
        if (array_key_exists('email', $req_data) && $this->verifyStringInput($req_data['email'], 60)) {
            $usuarioId = $this->findByAttribute(
                $this->entityManager->getRepository(User::class),
                'email',
                $req_data['email']
            );
            if (($usuarioId !== 0) && intval($args['userId']) !== $usuarioId) {
                $this->entityManager->rollback();
                // 400 BAD_REQUEST: e-mail already exists
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
            $userToModify->setEmail($req_data['email']);
        }
        $this->updatePassword($req_data, $userToModify);

        // Update role
        if ($isGestor && isset($req_data['role'])) {
            try {
                $userToModify->setRole($req_data['role']);
            } catch (Throwable) {    // 400 BAD_REQUEST: unexpected role
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        return $response
            ->withJson($userToModify, 209);
    }

    /**
     * Update the user's password
     *
     * @param array<string, string> $req_data
     * @param User $userToModify
     * @return void
     */
    private function updatePassword(array $req_data, User $userToModify): void
    {
        // Update password
        if (array_key_exists('password', $req_data)) {
            $userToModify->setPassword($req_data['password']);
        }
    }
}
