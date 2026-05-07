<?php

namespace TDW\IPanel\Controller\Operacion;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use Slim\Routing\RouteContext;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Model\Operacion;
use TDW\IPanel\Utility\Error;

class OperacionQueryController
{
    use TraitController;

    public function __construct(protected readonly EntityManager $entityManager) {}

    public function cget(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $criteria = $this->buildCriteria($params);

        $elements = $this->entityManager->getRepository(Operacion::class)
            ->matching($criteria)
            ->getValues();

        if (0 === count($elements)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Caching con ETag (Consistente con OperatorQueryController)
        $etag = md5((string) json_encode($elements));
        if ($request->hasHeader('If-None-Match') && in_array($etag, $request->getHeader('If-None-Match'), true)) {
            return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED);
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson([ 'operaciones' => $elements ]);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = $args['operationId'];
        $element = $this->entityManager->getRepository(Operacion::class)->find($id);

        if (!$element instanceof Operacion) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $etag = md5((string) json_encode($element));
        if ($request->hasHeader('If-None-Match') && in_array($etag, $request->getHeader('If-None-Match'), true)) {
            return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED);
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson($element);
    }

    public function options(Request $request, Response $response): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();

        return $response
            ->withStatus(204)
            ->withAddedHeader('Cache-Control', 'private')
            ->withAddedHeader('Allow', implode(',', $methods));
    }

    private function buildCriteria(array $params): Criteria
    {
        $criteria = new Criteria();
        
        // Ordenación: permite id (operacionId), codigo, horaProgramada
        $order = (isset($params['order']) && in_array($params['order'], ['operacionId', 'codigo', 'horaProgramada'])) 
                 ? $params['order'] : 'operacionId';
        $ordering = (isset($params['ordering']) && $params['ordering'] === 'DESC') ? 'DESC' : 'ASC';
        $criteria->orderBy([$order => $ordering]);

        // Búsqueda por código
        if (isset($params['name'])) {
            $criteria->andWhere(Criteria::expr()->contains('codigo', $params['name']));
        }

        return $criteria;
    }
}