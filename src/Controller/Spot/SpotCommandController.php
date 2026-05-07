<?php

namespace TDW\IPanel\Controller\Spot;

use Doctrine\ORM\EntityManager;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Enum\TipoPunto;
use TDW\IPanel\Model\Punto;
use TDW\IPanel\Utility\Error;

class SpotCommandController
{
    use TraitController;

    public function __construct(
        protected EntityManager $entityManager
    ) { }

    public function post(Request $request, Response $response): Response
    {
        // 1. Seguridad: Solo gestores
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $data = $request->getParsedBody();

        // 2. Validación de datos obligatorios
        if (!isset($data['codigo'], $data['tipo'])) {
            return Error::createResponse($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
        }

        try {
            // Validar que el tipo sea un valor válido del Enum (PUERTA o VIA)
            $tipo = TipoPunto::from(strtoupper($data['tipo']));
            
            $punto = new Punto($tipo, $data['codigo']);

            $this->entityManager->persist($punto);
            $this->entityManager->flush();

            return $response->withStatus(StatusCode::STATUS_CREATED)->withJson($punto);
        } catch (\ValueError $e) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return Error::createResponse($response, StatusCode::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    public function put(Request $request, Response $response, array $args): Response
    {
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $id = (int) $args['spotId'];
        $punto = $this->entityManager->getRepository(Punto::class)->find($id);

        if (!$punto) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Validación de Integridad (ETag / If-Match)
        $etag = md5((string) json_encode($punto));
        if ($request->getHeaderLine('If-Match') !== $etag) {
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
        }

        $data = $request->getParsedBody();

        // Actualización parcial de campos
        if (isset($data['codigo'])) {
            $punto->setCodigo($data['codigo']);
        }
        if (isset($data['tipo'])) {
            try {
                $punto->setTipo(TipoPunto::from(strtoupper($data['tipo'])));
            } catch (\ValueError $e) {
                return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
            }
        }

        $this->entityManager->flush();
        return $response->withStatus(209)->withJson($punto); // 209 Content Returned
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $id = (int) $args['spotId'];
        $punto = $this->entityManager->getRepository(Punto::class)->find($id);

        if (!$punto) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($punto);
            $this->entityManager->flush();
            return $response->withStatus(StatusCode::STATUS_NO_CONTENT);
        } catch (\Exception $e) {
            // Error común: No se puede borrar si hay operaciones que dependen de este punto
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }
    }
}