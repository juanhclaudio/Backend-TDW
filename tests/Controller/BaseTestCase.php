<?php

/**
 * tests/Controller/BaseTestCase.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

declare(strict_types=1);

namespace TDW\Test\IPanel\Controller;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Faker\Provider\es_ES as FakerProvider;
use JetBrains\PhpStorm\ArrayShape;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Http\Factory\DecoratedServerRequestFactory;
use Slim\Psr7\Environment;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use TDW\IPanel\Utility\{ Error, Utils };
use Throwable;

class BaseTestCase extends TestCase
{
    protected static FakerGenerator $faker;

    protected static function getFaker(): FakerGenerator
    {
        if (!isset(self::$faker)) {
            self::$faker = FakerFactory::create('es_ES');
            self::$faker->addProvider(new FakerProvider\Person(self::$faker));
            self::$faker->addProvider(new FakerProvider\Internet(self::$faker));
            // self::$faker->addProvider(new FakerProvider\Text(self::$faker));
            // self::$faker->addProvider(new \Faker\Provider\Image(self::$faker));
        }
        return self::$faker;
    }

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        self::$faker = self::getFaker();
        Utils::updateSchema();
    }

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param array<string, mixed>|string|null $requestData the request data
     * @param array<string, mixed>|null $requestHeaders the request headers
     *
     * @return Response
     */
    public function runApp(
        string $requestMethod,
        string $requestUri,
        array|string|null $requestData = null,
        array|null $requestHeaders = null
    ): Response {

        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD'     => $requestMethod,
                'REQUEST_URI'        => $requestUri,
                'HTTP_AUTHORIZATION' => $requestHeaders['Authorization'] ?? null,
            ]
        );

        // Set up a request object based on the environment
        $factory = new DecoratedServerRequestFactory(new ServerRequestFactory());
        $request = $factory->createServerRequest(
            $requestMethod,
            $requestUri,
            $environment
        );

        if ($requestHeaders['Content-Type'] === [] || $requestHeaders['Content-Type'] === 'application/x-www-form-urlencoded') {
            $request = $request->withParsedBody((array) $requestData);
        } elseif ($requestHeaders['Content-Type'] === 'application/json') {
            $stream = new StreamFactory();
            !is_array($requestData) ?? $requestData = (string) json_encode($requestData, JSON_PARTIAL_OUTPUT_ON_ERROR);
            $stream = $stream->createStream((string) $requestData);
            $request = $request->withBody($stream);
        }

        // Add request headers, if it exists
        if (null !== $requestHeaders) {
            foreach ($requestHeaders as $header_name => $value) {
                $request = $request->withAddedHeader($header_name, $value);
            }
        }

        // Instantiate the application
        /** @var App $app */
        $app = (require __DIR__ . '/../../config/bootstrap.php');

        // Process the application
        try {
            $response = $app->handle($request);
        } catch (Throwable $exception) {
            die(
                'ERROR: code=' .
                $exception->getCode() .
                ' message=' .
                $exception->getMessage()
            );
        }

        // Return the response
        return $response;
    }

    /**
     * Obtiene la cabecera <tt>Authorization</tt> desde el servicio de autenticación
     *
     * @param string|null $email User e-mail
     * @param string|null $password User password
     *
     * @return array<string, string> cabeceras con el token obtenido
     */
    #[ArrayShape(['Authorization' => "string"])]
    protected function getTokenHeaders(
        ?string $email = null,
        ?string $password = null
    ): array {
        $data = [
            'username' => $email,
            'password' => $password,
        ];
        $response = $this->runApp(
            requestMethod:  'POST',
            requestUri:     $_ENV['RUTA_LOGIN'],
            requestData:    $data,
            requestHeaders: [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        return [ 'Authorization' => $response->getHeaderLine('Authorization') ];
    }

    /**
     * Verifica contenido y estructura de los mensajes HTTP de error
     *
     * @param Response $errorResponse HTTP Error Response object
     * @param int $errorCode Error Code
     *
     * @return void
     */
    protected function internalTestError(Response $errorResponse, int $errorCode): void
    {
        self::assertSame($errorCode, $errorResponse->getStatusCode());
        $r_body = $errorResponse->getBody()->getContents();
        self::assertJson($r_body);
        try {
            /** @var array<string, string> $r_data */
            $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            die('ERROR: ' . $exception->getMessage());
        }
        self::assertArrayHasKey('code', $r_data);
        self::assertArrayHasKey('message', $r_data);
        self::assertSame($errorCode, $r_data['code']);
        self::assertSame(Error::MESSAGES[$errorCode], $r_data['message']);
    }
}
