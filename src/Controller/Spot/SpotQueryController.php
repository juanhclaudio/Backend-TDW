<?php

namespace TDW\IPanel\Controller\Spot;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use Slim\Routing\RouteContext;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Model\Punto;
use TDW\IPanel\Utility\Error;

class SpotQueryController
{
    use TraitController;

    const string PATH_SPOTS = '/spots';

    public function __construct(
        protected readonly EntityManager $entityManager
    ) {}

    public function cget(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $criteria = $this->buildCriteria($params);

        $elements = $this->entityManager->getRepository(Punto::class)
            ->matching($criteria)
            ->getValues();

        if (0 === count($elements)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Caching con ETag
        $etag = md5((string) json_encode($elements));
        if ($request->hasHeader('If-None-Match') && in_array($etag, $request->getHeader('If-None-Match'), true)) {
            return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED);
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson(['puntos' => $elements]);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['spotId'] ?? 0);
        if (!$this->verifyInputId($id)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        $element = $this->entityManager->getRepository(Punto::class)->find($id);

        if (!$element instanceof Punto) {
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
            ->withStatus(StatusCode::STATUS_NO_CONTENT)
            ->withAddedHeader('Cache-Control', 'private')
            ->withAddedHeader('Allow', implode(',', $methods));
    }

    private function buildCriteria(array $params): Criteria
    {
        $criteria = new Criteria();
        
        // Determinar campo de ordenación (mapeo id -> puntoId)
        $orderField = (isset($params['order']) && $params['order'] === 'id') ? 'puntoId' : 'puntoId';
        if (isset($params['order']) && in_array($params['order'], ['puntoId', 'codigo'], true)) {
            $orderField = $params['order'];
        }

        $ordering = (isset($params['ordering']) && $params['ordering'] === 'DESC') ? 'DESC' : 'ASC';
        $criteria->orderBy([$orderField => $ordering]);

        // Búsqueda por código (parámetro name en OpenAPI)
        if (isset($params['name'])) {
            $criteria->andWhere(Criteria::expr()->contains('codigo', $params['name']));
        }

        return $criteria;
    }
}
