<?php

/**
 * @link https://odan.github.io/2019/11/24/slim4-cors.html
 */

namespace TDW\IPanel\Middleware;

use Psr\Http\Message\{ ResponseInterface, ServerRequestInterface };
use Psr\Http\Server\{ MiddlewareInterface, RequestHandlerInterface };
use Slim\Routing\RouteContext;

/**
 * CORS middleware.
 *
 * Allows CORS preflight from any domain.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

        $response = $handler->handle($request);

        $response = $response->withHeader('Access-Control-Expose-Headers', [ '*', 'Authorization', 'ETag' ]);

        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $methods));
        if ('' !== $requestHeaders) {
            $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);
        } else {
            $response = $response->withHeader('Access-Control-Allow-Headers', '*');
        }

        // Allow Ajax CORS requests with Authorization header
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');

        // Adds Content Security Policy header
        $response = $response->withHeader('Content-Security-Policy', "frame-ancestors 'none'");

        return $response;
    }
}
