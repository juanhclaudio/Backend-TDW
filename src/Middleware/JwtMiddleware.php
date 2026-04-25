<?php

/**
 * src/Middleware/JwtMiddleware.php
 *
 * @license ttps://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 *
 * @link    https://odan.github.io/2019/12/02/slim4-oauth2-jwt.html
 */

namespace TDW\IPanel\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use InvalidArgumentException;
use Lcobucci\JWT\Token;
use Psr\Container\ContainerInterface;
use Psr\Http\{ Message, Server };
use TDW\IPanel\Auth\JwtAuth;
use TDW\IPanel\Utility\Error;
use Throwable;

/**
 * Jwt Middleware
 */
final class JwtMiddleware implements Server\MiddlewareInterface
{
    private JwtAuth $jwtAuth;

    private Message\ResponseFactoryInterface $responseFactory;

    public function __construct(ContainerInterface $container)
    {
        try {
            $this->jwtAuth = $container->get(JwtAuth::class);
            $this->responseFactory = $container->get(Message\ResponseFactoryInterface::class);
        } catch (Throwable) {
            die('ERROR en la configuración del contenedor');
        }
    }

    /**
     * Invoke middleware.
     *
     * @param Message\ServerRequestInterface $request The request
     * @param Server\RequestHandlerInterface $handler The handler
     *
     * @return Message\ResponseInterface The response
     */
    public function process(
        Message\ServerRequestInterface $request,
        Server\RequestHandlerInterface $handler
    ): Message\ResponseInterface {
        $authorization = explode(' ', $request->getHeaderLine('Authorization'));
        $token = $authorization[1] ?? '';

        try {
            if ('' === $token || !$this->jwtAuth->validateToken($token)) {
                throw new InvalidArgumentException('Invalid token provided');
            }
        } catch (InvalidArgumentException) {
            return Error::createResponse(
                $this->responseFactory->createResponse(),
                StatusCode::STATUS_UNAUTHORIZED
            );
        }

        // Append valid token
        /** @var Token\Plain $parsedToken */
        $parsedToken = $this->jwtAuth->createParsedToken($token);

        return $handler->handle(
            $request
                ->withAttribute('token', $parsedToken)
                ->withAttribute('uid', $parsedToken->claims()->get('uid'))
        );
    }
}
