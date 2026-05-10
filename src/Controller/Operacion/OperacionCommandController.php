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
use TDW\IPanel\Enum\TipoOperacion;
use TDW\IPanel\Enum\SentidoOperacion;
use TDW\IPanel\Enum\EstadoOperacion;
use TDW\IPanel\Utility\Error;

class OperacionCommandController
{
    use TraitController;

    public function __construct(protected readonly EntityManager $entityManager) {}

    public function post(Request $request, Response $response): Response
    {
        
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $data = $request->getParsedBody();

        
        $operador = $this->entityManager->getRepository(Operador::class)->find($data['operadorId'] ?? 0);
        $punto = $this->entityManager->getRepository(Punto::class)->find($data['puntoId'] ?? 0);

        if (!$operador || !$punto) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }

        try {
            $tipo = TipoOperacion::from($data['tipo']);
            $sentido = SentidoOperacion::from($data['sentido']);
            $estado = EstadoOperacion::from($data['estado'] ?? 'programado');

            $operacion = new Operacion(
                $tipo,
                $data['codigo'],
                $sentido,
                $data['origen'],
                $data['destino'],
                $operador,
                $punto,
                $estado,
                isset($data['horaProgramada']) ? new DateTime($data['horaProgramada']) : null,
                isset($data['horaEstimada']) ? new DateTime($data['horaEstimada']) : null
            );

            $this->entityManager->persist($operacion);
            $this->entityManager->flush();

            return $response
            ->withAddedHeader(
                'Location',
                $request->getUri() . '/' . $operacion->getId()
            )
            ->withJson($operacion, StatusCode::STATUS_CREATED);
        } catch (\ValueError $e) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        }
    }

    public function put(Request $request, Response $response, array $args): Response
    {
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $operacion = $this->entityManager->getRepository(Operacion::class)->find($args['operationId']);
        if (!$operacion) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        
        $etag = md5((string) json_encode($operacion));
        if ($request->getHeaderLine('If-Match') !== $etag) {
            return Error::createResponse($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
        }

        $data = $request->getParsedBody();

        try {
            
            if (isset($data['tipo'])) {
                $operacion->setTipo(TipoOperacion::from($data['tipo']));
            }
            if (isset($data['sentido'])) {
                $operacion->setSentido(SentidoOperacion::from($data['sentido']));
            }
            if (isset($data['estado'])) {
                $operacion->setEstado(EstadoOperacion::from($data['estado']));
            }

            
            if (isset($data['codigo'])) $operacion->setCodigo($data['codigo']);
            if (isset($data['origen'])) $operacion->setOrigen($data['origen']);
            if (isset($data['destino'])) $operacion->setDestino($data['destino']);
            if (isset($data['horaProgramada'])) $operacion->setHoraProgramada(new DateTime($data['horaProgramada']));
            if (isset($data['horaEstimada'])) $operacion->setHoraEstimada(new DateTime($data['horaEstimada']));

            
            if (isset($data['operadorId'])) {
                $newOp = $this->entityManager->getRepository(Operador::class)->find($data['operadorId']);
                if ($newOp) $operacion->setOperador($newOp);
            }
            if (isset($data['puntoId'])) {
                $newPt = $this->entityManager->getRepository(Punto::class)->find($data['puntoId']);
                if ($newPt) $operacion->setPunto($newPt);
            }

            $this->entityManager->flush();
            return $response->withStatus(209)->withJson($operacion);
        } catch (\ValueError $e) {
            return Error::createResponse($response, StatusCode::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return Error::createResponse($response, StatusCode::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        if (!$this->checkGestorScope($request)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
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