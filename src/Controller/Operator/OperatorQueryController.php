<?php

/**
 * src/Controller/Operator/OperatorQueryController.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Controller\Operator;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use Slim\Routing\RouteContext;
use TDW\IPanel\Controller\TraitController;
use TDW\IPanel\Model\Operador;
use TDW\IPanel\Utility\Error;

/**
 * Class OperatorQueryController
 */
class OperatorQueryController
{
    use TraitController;

    const string PATH_OPERATORS = '/operators';

    // constructor - receives the EntityManager from container instance
    public function __construct(
        protected readonly EntityManager $entityManager
    ) { }

    /**
     * Summary: Returns all elements
     *
     * @todo add pagination
     */
    public function cget(Request $request, Response $response): Response
    {
        assert(in_array($request->getMethod(), [ 'GET', 'HEAD' ], true));
        /** @var array<string, string> $params */
        $params = $request->getQueryParams();
        $criteria = $this->buildCriteria($params);

        $elements = $this->entityManager
            ->getRepository(Operador::class)
            ->matching($criteria)
            ->getValues();

        if (0 === count($elements)) {    // 404
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Caching with ETag
        $etag = md5((string) json_encode($elements));
        if ($request->hasHeader('If-None-Match') && in_array($etag, $request->getHeader('If-None-Match'), true)) {
            return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED); // 304
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson([ 'operadores' => $elements ]);
    }

    /**
     * Summary: Returns a element based on a single id
     *
     * @param Request $request
     * @param Response $response
     * @param array<string, mixed> $args
     *
     * @return Response
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        assert(in_array($request->getMethod(), [ 'GET', 'HEAD' ], true));
        $id = intval($request->getAttribute('operatorId', 0));
        if (!$this->verifyInputId($id)) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }
        $element = $this->entityManager->getRepository(Operador::class)
            ->find($id);
        if (!$element instanceof Operador) {
            return Error::createResponse($response, StatusCode::STATUS_NOT_FOUND);
        }

        // Caching with ETag
        $etag = md5((string) json_encode($element));
        if (in_array($etag, $request->getHeader('If-None-Match'), true)) {
            return $response->withStatus(StatusCode::STATUS_NOT_MODIFIED); // 304
        }

        return $response
            ->withAddedHeader('ETag', $etag)
            ->withAddedHeader('Cache-Control', 'private')
            ->withJson($element);
    }

    /**
     * Summary: Returns status code 204 if _operatorName_ exists
     * Path: /operators/name/{name} || /operators/acronym/{name}
     *
     * @param Request $request
     * @param Response $response
     * @param array<string, mixed> $args
     *
     * @return Response 204 if _$args['operatorName']_ exists, 404 otherwise
     */
    public function getElementByName(Request $request, Response $response, array $args): Response
    {
        // Search by name or by acronym depending on the value in the path (criterion)
        $attrBuscado = match ($request->getAttribute('criterion')) {
            'acronym' => 'siglas',
            default => 'nombre'
        };

        $elementId = $this->findByAttribute(
            $this->entityManager->getRepository(Operador::class),
            $attrBuscado,
            $args['operatorName']
        );

        return ($elementId !== 0)
            ? $response->withStatus(StatusCode::STATUS_NO_CONTENT)       // 204
            : Error::createResponse($response, StatusCode::STATUS_NOT_FOUND); // 404
    }

    /**
     * Summary: Provides the list of HTTP supported methods
     */
    public function options(Request $request, Response $response): Response
    {
        assert($request->getMethod() === 'OPTIONS');
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();

        return $response
            ->withStatus(204)
            ->withAddedHeader('Cache-Control', 'private')
            ->withAddedHeader(
                'Allow',
                implode(',', $methods)
            );
    }

    /**
     * Builds a criteria based on the parameters received
     *
     * @param array<string, string> $params order | ordering | name
     * @return Criteria
     */
    private function buildCriteria(array $params): Criteria
    {
        $criteria = new Criteria();
        if (array_key_exists('order', $params)) { // Sorting criteria
            $order = (in_array($params['order'], ['id', 'nombre'], true)) ? $params['order'] : null;
        }
        if (array_key_exists('ordering', $params)) {
            $ordering = ('DESC' === $params['ordering']) ? 'DESC' : null;
        }
        $criteria->orderBy([$order ?? 'id' => $ordering ?? 'ASC']);
        if (array_key_exists('name', $params)) { // Search by name
            $txtName = $params['name'];
            assert(preg_match('^[a-zA-Z0-9()áéíóúÁÉÍÓÚñÑ %$.+-]+$^', $txtName) !== false);
            $expressionBuilder = Criteria::expr();
            $expression = $expressionBuilder->contains('nombre', $txtName);
            $criteria->andWhere($expression);
        }

        return $criteria;
    }
}
