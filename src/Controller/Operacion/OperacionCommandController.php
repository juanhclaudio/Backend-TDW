<?php

namespace TDW\IPanel\Controller\Operacion;

use DateTime;
use Doctrine\ORM\EntityManager;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Model\Operacion;
use TDW\IPanel\Model\Operador;
use TDW\IPanel\Model\Punto;
use TDW\IPanel\Utility\Error;

class OperacionCommandController
{
    use TraitController;

    public function __construct(protected readonly EntityManager $entityManager) {}

    public function post(Request $request, Response $response): Response
    {
        // 1. Verificar rol GESTOR
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $data = $request->getParsedBody();

        // 2. Validar entidades relacionadas
        $operador = $this->entityManager->getRepository(Operador::class)->find($data['operadorId'] ?? 0);
        $punto = $this->entityManager->getRepository(Punto::class)->find($data['puntoId'] ?? 0);

        if (!$operador || !$punto) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }

        try {
            $operacion = new Operacion(
                $data['tipo'],
                $data['codigo'],
                $data['sentido'],
                $data['origen'],
                $data['destino'],
                $operador,
                $punto,
                $data['estado'] ?? 'programado',
                isset($data['horaProgramada']) ? new DateTime($data['horaProgramada']) : null,
                isset($data['horaEstimada']) ? new DateTime($data['horaEstimada']) : null
            );

            $this->entityManager->persist($operacion);
            $this->entityManager->flush();

            return $response->withStatus(StatusCode::STATUS_CREATED)->withJson($operacion);
        } catch (\Exception $e) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }
    }

    public function put(Request $request, Response $response, array $args): Response
    {
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $operacion = $this->entityManager->getRepository(Operacion::class)->find($args['operationId']);
        if (!$operacion) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Validación de ETag (If-Match) para asegurar integridad
        $etag = md5((string) json_encode($operacion));
        if ($request->getHeaderLine('If-Match') !== $etag) {
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
        }

        $data = $request->getParsedBody();

        // Actualización de campos simples
        if (isset($data['tipo'])) $operacion->setTipo($data['tipo']);
        if (isset($data['codigo'])) $operacion->setCodigo($data['codigo']);
        if (isset($data['sentido'])) $operacion->setSentido($data['sentido']);
        if (isset($data['origen'])) $operacion->setOrigen($data['origen']);
        if (isset($data['destino'])) $operacion->setDestino($data['destino']);
        if (isset($data['estado'])) $operacion->setEstado($data['estado']);
        if (isset($data['horaProgramada'])) $operacion->setHoraProgramada(new DateTime($data['horaProgramada']));
        if (isset($data['horaEstimada'])) $operacion->setHoraEstimada(new DateTime($data['horaEstimada']));

        // Reasignar relaciones si vienen en el body
        if (isset($data['operadorId'])) {
            $newOp = $this->entityManager->getRepository(Operador::class)->find($data['operadorId']);
            if ($newOp) $operacion->setOperador($newOp);
        }
        if (isset($data['puntoId'])) {
            $newPt = $this->entityManager->getRepository(Punto::class)->find($data['puntoId']);
            if ($newPt) $operacion->setPunto($newPt);
        }

        $this->entityManager->flush();
        return $response->withStatus(209)->withJson($operacion); // 209 Content Returned
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_FORBIDDEN);
        }

        $operacion = $this->entityManager->getRepository(Operacion::class)->find($args['operationId']);
        if (!$operacion) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $this->entityManager->remove($operacion);
        $this->entityManager->flush();

        return $response->withStatus(StatusCode::STATUS_NO_CONTENT);
    }
}