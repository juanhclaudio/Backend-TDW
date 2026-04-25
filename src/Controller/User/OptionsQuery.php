<?php

/**
 * src/Controller/User/OptionsQuery.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Controller\User;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use Slim\Routing\RouteContext;

class OptionsQuery
{
    /**
     * OPTIONS /api/v1/users[/{userId}]
     *
     * Summary: Provides the list of HTTP supported methods
     *
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response): Response
    {
        assert($request->getMethod() === 'OPTIONS');
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();

        return $response
            ->withStatus(204)   // "No Content"
            ->withAddedHeader(
                name: 'Cache-Control',
                value: 'private'
            )
            ->withAddedHeader(
                name: 'Allow',
                value: implode(',', $methods)
            );
    }
}
