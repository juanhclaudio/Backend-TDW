<?php

/**
 * src/Controller/Operator/OperatorCommandController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Controller\Operator;

use Doctrine\ORM;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Model\Operador;
use TDW\IPanel\Utility\Error;

/**
 * Class OperatorCommandController
 */
class OperatorCommandController
{
    use TraitController;

    // constructor - receives the EntityManager from container instance
    public function __construct(
        protected ORM\EntityManager $entityManager
    ) { }

    /**
     * Summary: Creates a new Operator
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORM\Exception\ORMException
     */
    public function post(Request $request, Response $response): Response
    {
        assert($request->getMethod() === 'POST');
        if (!$this->checkGestorScope($request)) { // 403 => 404 por seguridad
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $req_data = (array) $request->getParsedBody();

        if (!$this->verifyStringInput($req_data['nombre'] ?? '', 80)    // 422 - Faltan datos o exceden los límites
             || !$this->verifyStringInput($req_data['siglas'] ?? '', 6)) {
            return Error::createResponse($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
        }

        // hay datos -> procesarlos comprobando que no exista ya el nombre o las siglas
        $opRepository = $this->entityManager->getRepository(Operador::class);
        $opExists = $this->findByAttribute($opRepository, 'nombre', $req_data['nombre']);
        $opExists += $this->findByAttribute($opRepository, 'siglas', $req_data['siglas']);
        // STATUS_BAD_REQUEST 400: element name already exists
        if ($opExists !== 0) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }

        // 201
        $element = new Operador($req_data['nombre'], $req_data['siglas']);
        $this->updateElement(element: $element, data: $req_data);
        $this->entityManager->persist($element);
        $this->entityManager->flush();

        return $response
            ->withAddedHeader(
                'Location',
                $request->getUri() . '/' . $element->getId()
            )
            ->withJson($element, StatusCode::STATUS_CREATED);
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
        if (!$this->checkGestorScope($request)) { // 403 => 404 por seguridad
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $req_data = (array) $request->getParsedBody();
        // recuperar el elemento
        if (!$this->verifyInputId($args['operatorId'] ?? 0)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        $this->entityManager->beginTransaction();
        /** @var Operador|null $element */
        $element = $this->entityManager->getRepository(Operador::class)->find($args['operatorId']);

        if (!$element instanceof Operador) {    // 404
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Optimistic Locking (strong validation) - https://httpwg.org/specs/rfc6585.html#status-428
        $etag = md5((string) json_encode($element));
        $ifMatch = trim(current($request->getHeader('If-Match')));
        if ($ifMatch !== $etag) {
            $this->entityManager->rollback();
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);   // 428
        }

        // Update element name
        if (isset($req_data['nombre']) && $this->verifyStringInput($req_data['nombre'], 80)) { // 400
            $elementId = $this->findByAttribute(
                $this->entityManager->getRepository(Operador::class),
                'nombre',
                $req_data['nombre']
            );
            if (($elementId !== 0) && (intval($args['operatorId']) !== $elementId)) {
                // 400 BAD_REQUEST: elementname already exists
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
            $element->nombre =  $req_data['nombre'];
        }

        // Update element acronym
        if (isset($req_data['siglas']) && $this->verifyStringInput($req_data['siglas'], 6)) { // 400
            $elementId = $this->findByAttribute(
                $this->entityManager->getRepository(Operador::class),
                'siglas',
                $req_data['siglas']
            );
            if (($elementId !== 0) && ($elementId !== intval($args['operatorId']))) {
                // 400 BAD_REQUEST: element acronym already exists
                $this->entityManager->rollback();
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
            $element->siglas =  $req_data['siglas'];
        }

        $this->updateElement($element, $req_data);
        $this->entityManager->flush();
        $this->entityManager->commit();

        return $response
            ->withStatus(209, 'Content Returned')
            ->withJson($element);
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
        if (!$this->checkGestorScope($request)) { // 403 => 404 por seguridad
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        if (!$this->verifyInputId($args['operatorId'] ?? 0)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        $element = $this->entityManager->getRepository(Operador::class)->find($args['operatorId']);

        if (!$element instanceof Operador) {    // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $this->entityManager->remove($element);
        $this->entityManager->flush();

        return $response
            ->withStatus(StatusCode::STATUS_NO_CONTENT);  // 204
    }

    /**
     * Update $element with $data attributes
     *
     * @param Operador $element
     * @param array<string, string> $data
     */
    private function updateElement(Operador $element, array $data): void
    {
        foreach ($data as $attr => $datum) {
            switch ($attr) {
                case 'color':
                    $element->color = $datum;
                    break;
                case 'urlIcono':
                    $element->urlIcono = $datum;
                    break;
            }
        }
    }
}
