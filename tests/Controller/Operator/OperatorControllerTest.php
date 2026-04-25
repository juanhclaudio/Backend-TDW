<?php

/**
 * tests/Controller/Operator/OperatorControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\IPanel\Controller\Operator;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestAttr;
use TDW\IPanel\Controller\Operator\OperatorCommandController;
use TDW\IPanel\Controller\Operator\OperatorQueryController;
use TDW\IPanel\Utility\Utils;
use TDW\Test\IPanel\Controller\BaseTestCase;

/**
 * Class OperatorControllerTest
 */
#[TestAttr\CoversClass(OperatorQueryController::class)]
#[TestAttr\CoversClass(OperatorCommandController::class)]
class OperatorControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de operadores */
    protected const string RUTA_API = '/api/v1/operators';

    /** @var array<string, mixed> $gestor */
    protected static array $gestor;

    /** @var array<string, mixed> $publico */
    protected static array $publico;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // user admin (GESTOR) fixtures
        self::$gestor = [
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];
        self::$gestor['id'] = Utils::loadUserData(
            self::$gestor['email'],
            self::$gestor['password'],
            true
        );

        // user PUBLICO fixtures
        self::$publico = [
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$publico['id'] = Utils::loadUserData(
            self::$publico['email'],
            self::$publico['password'],
        );
    }

    /**
     * Test GET /operators 404 NOT FOUND
     */
    public function testCGetOperators404NotFound(): void
    {
        self::$gestor['authHeader'] =
            $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test POST /operators 201 CREATED
     *
     * @return array<string, int|string> OperatorData
     * @throws JsonException
     */
    #[TestAttr\Depends('testCGetOperators404NotFound')]
    public function testPostOperator201Created(): array
    {
        $p_data = [
            'nombre'    => self::$faker->text(80),
            'siglas'    => strtoupper(self::$faker->text(6)),
            'color'     => strtolower(self::$faker->colorName()),
            'urlIcono'  => self::$faker->url(),
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        self::assertSame(201, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Location'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseOperator = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('operador', $responseOperator);
        $operatorData = $responseOperator['operador'];
        self::assertNotEquals(0, $operatorData['id']);
        self::assertSame($p_data['nombre'], $operatorData['nombre']);
        self::assertSame($p_data['siglas'], $operatorData['siglas']);
        self::assertSame($p_data['color'], $operatorData['color']);
        self::assertSame($p_data['urlIcono'], $operatorData['urlIcono']);

        return $operatorData;
    }

    /**
     * Test POST /operators 422 UNPROCESSABLE ENTITY
     */
    #[TestAttr\Depends('testCGetOperators404NotFound')]
    public function testPostOperator422UnprocessableEntity(): void
    {
        $p_data = [
            // 'nombre'    => self::$faker->text(80),
            'siglas'    => strtoupper(self::$faker->text(6)),
            'color'     => strtolower(self::$faker->colorName()),
            'urlIcono'  => self::$faker->url(),
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);

        $p_data = [
            'nombre'    => self::$faker->text(80),
            // 'siglas'    => strtoupper(self::$faker->text(6)),
            'color'     => strtolower(self::$faker->colorName()),
            'urlIcono'  => self::$faker->url(),
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test POST /operators 400 BAD REQUEST
     *
     * @param array<string, int|string> $operator data returned by testPostOperator201Created()
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    public function testPostOperator400BadRequest(array $operator): void
    {
        // Mismo nombre
        $p_data = [
            'nombre'    => $operator['nombre'],
            'siglas'    => strtoupper(self::$faker->text(6))
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);

        // Mismo acrónimo
        $p_data = [
            'nombre'    => self::$faker->text(80),
            'siglas'    => $operator['siglas'],
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test GET /operators 200 OK
     *
     * @param array<string, int|string> $operator data returned by testPostOperator201Created()
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    public function testCGetOperators200Ok(array $operator): array
    {
        self::assertIsString($operator['nombre']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '?name=' . substr($operator['nombre'], 0, -2) . '&order=id&ordering=DESC',
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $etag = $response->getHeader('ETag');
        self::assertNotEmpty($etag);
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('operadores', $r_data);
        self::assertIsArray($r_data['operadores']);

        return $etag;
    }

    /**
     * Test GET /operators 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetoperators200Ok
     */
    #[TestAttr\Depends('testCGetOperators200Ok')]
    public function testCGetOperators304NotModified(array $etag): void
    {
        $headers = array_merge(
            self::$gestor['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /operators/{operatorId} 200 OK
     *
     * @param array<string, int|string> $operator data returned by testPostOperator201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    public function testGetOperator200Ok(array $operator): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $operator['id'],
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $operator_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($operator, $operator_aux['operador']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /operators/{operatorId} 304 NOT MODIFIED
     *
     * @param array<string, int|string> $operator data returned by testPostoperator201Created()
     * @param array<string> $etag returned by testGetoperator200Ok
     *
     * @return string Entity Tag
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    #[TestAttr\Depends('testGetOperator200Ok')]
    public function testGetOperator304NotModified(array $operator, array $etag): string
    {
        $headers = array_merge(
            self::$gestor['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $operator['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /operators/{criterion}/{operatorname} 204 NO CONTENT
     *
     * @param array<string, int|string> $operator data returned by testPostOperator201()
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    public function testGetOperatorname204NoContent(array $operator): void
    {
        // GET /operators/name/{operatorname}
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/name/' . $operator['nombre']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        // GET /operators/acronym/{operatorname}
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/acronym/' . $operator['siglas']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /operators/{operatorId}   209 UPDATED
     *
     * @param array<string, int|string> $operator data returned by testPostOperator201Created()
     * @param string $etag returned by testGetOperator304NotModified
     *
     * @return array<string, int|string> modified operator data
     * @throws JsonException
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    #[TestAttr\Depends('testGetOperator304NotModified')]
    #[TestAttr\Depends('testPostOperator400BadRequest')]
    #[TestAttr\Depends('testCGetOperators304NotModified')]
    #[TestAttr\Depends('testGetOperatorname204NoContent')]
    public function testPutOperator209Updated(array $operator, string $etag): array
    {
        $p_data = [
            'nombre'    => self::$faker->text(80),
            'siglas'    => strtoupper(self::$faker->text(6)),
            'color'     => strtolower(self::$faker->colorName()),
            'urlIcono'  => self::$faker->url(),
        ];

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $operator['id'],
            $p_data,
            array_merge(
                self::$gestor['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $operator_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('operador', $operator_aux);
        self::assertSame($operator['id'], $operator_aux['operador']['id']);
        self::assertSame($p_data['nombre'], $operator_aux['operador']['nombre']);
        self::assertSame($p_data['siglas'], $operator_aux['operador']['siglas']);
        self::assertSame($p_data['color'], $operator_aux['operador']['color']);
        self::assertSame($p_data['urlIcono'], $operator_aux['operador']['urlIcono']);

        return $operator_aux['operador'];
    }

    /**
     * Test PUT /operators/{operatorId} 400 BAD REQUEST
     *
     * @param array<string, int|string> $operator data returned by testPutOperator209Updated()
     */
    #[TestAttr\Depends('testPutOperator209Updated')]
    public function testPutOperator400BadRequest(array $operator): void
    {
        $p_data = [
            'nombre' => self::$faker->text(80),
            'siglas' => strtoupper(self::$faker->text(6))
        ];
        $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $r1 = $this->runApp( // Obtains etag header
            'HEAD',
            self::RUTA_API . '/' . $operator['id'],
            [],
            self::$gestor['authHeader']
        );

        // operatorname already exists
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $operator['id'],
            [ 'nombre' => $p_data['nombre'] ],
            array_merge(
                self::$gestor['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);

        // Operator acronym already exists
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $operator['id'],
            [ 'siglas' => $p_data['siglas'] ],
            array_merge(
                self::$gestor['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test PUT /operator/{operatorId} 428 PRECONDITION REQUIRED
     *
     * @param array<string, int|string> $operator data returned by testPutOperator209Updated()
     */
    #[TestAttr\Depends('testPutOperator209Updated')]
    public function testPutOperator428PreconditionRequired(array $operator): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $operator['id'],
            [],
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test OPTIONS /operators[/{operatorId}] NO CONTENT
     */
    public function testOptionsOperator204NoContent(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$faker->randomDigitNotNull()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test DELETE /operators/{operatorId} 204 NO CONTENT
     *
     * @param array<string, int|string> $operator data returned by testPostOperator201Created()
     *
     * @return int operatorId
     */
    #[TestAttr\Depends('testPostOperator201Created')]
    #[TestAttr\Depends('testPostOperator400BadRequest')]
    #[TestAttr\Depends('testPostOperator422UnprocessableEntity')]
    #[TestAttr\Depends('testPutOperator400BadRequest')]
    #[TestAttr\Depends('testPutOperator428PreconditionRequired')]
    #[TestAttr\Depends('testGetOperatorname204NoContent')]
    public function testDeleteOperator204NoContent(array $operator): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $operator['id'],
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $operator['id'];
    }

    /**
     * Test GET /operators/name/{operatorname} 404 NOT FOUND
     *
     * @param array<string, int|string> $operator data returned by testPutOperator209Updated()
     */
    #[TestAttr\Depends('testPutOperator209Updated')]
    #[TestAttr\Depends('testDeleteOperator204NoContent')]
    public function testGetOperatorname404NotFound(array $operator): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/name/' . $operator['nombre']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);

        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/acronym/' . $operator['siglas']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);

        // Parámetro operatorname nulo
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/name/' # parámetro nulo
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /operators/{operatorId} 404 NOT FOUND
     * Test PUT    /operators/{operatorId} 404 NOT FOUND
     * Test DELETE /operators/{operatorId} 404 NOT FOUND
     *
     * @param mixed $operatorId operator id. returned by testDeleteOperator204NoContent()
     * @param string $method
     *
     * @return void
     */
    #[TestAttr\DataProvider('routeProvider404')]
    #[TestAttr\Depends('testDeleteOperator204NoContent')]
    public function testOperatorStatus404NotFound(string $method, mixed $operatorId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $operatorId,
            null,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /operators 401 UNAUTHORIZED
     * Test POST   /operators 401 UNAUTHORIZED
     * Test PUT    /operators/{operatorId} 401 UNAUTHORIZED
     * Test DELETE /operators/{operatorId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    #[TestAttr\DataProvider('routeProvider401')]
    public function testOperatorStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test POST   /operators 403 FORBIDDEN
     * Test PUT    /operators/{operatorId} 403 FORBIDDEN => 404 NOT FOUND
     * Test DELETE /operators/{operatorId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     *
     * @return void
     */
    #[TestAttr\DataProvider('routeProvider403')]
    public function testOperatorStatus403Forbidden(string $method, string $uri, int $statusCode): void
    {
        self::$publico['authHeader'] = $this->getTokenHeaders(self::$publico['email'], self::$publico['password']);
        $response = $this->runApp(
            $method,
            $uri,
            null,
            self::$publico['authHeader']
        );
        $this->internalTestError($response, $statusCode);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return array<string, mixed> [ method, url ]
     */
    #[ArrayShape([
        'postAction401' => "string[]",
        'putAction401' => "string[]",
        'deleteAction401' => "string[]",
        ])]
    public static function routeProvider401(): array
    {
        return [
            // 'cgetAction401'   => [ 'GET',    self::RUTA_API ],
            // 'getAction401'    => [ 'GET',    self::RUTA_API . '/1' ],
            'postAction401'   => [ 'POST',   self::RUTA_API ],
            'putAction401'    => [ 'PUT',    self::RUTA_API . '/1' ],
            'deleteAction401' => [ 'DELETE', self::RUTA_API . '/1' ],
        ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN (security) => 404 NOT FOUND)
     *
     * @return array<string, mixed> [ method, url, statusCode ]
     */
    #[ArrayShape([
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array",
        ])]
    public static function routeProvider403(): array
    {
        return [
            'postAction403'   => [ 'POST',   self::RUTA_API, StatusCode::STATUS_NOT_FOUND ],
            'putAction403'    => [ 'PUT',    self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ],
            'deleteAction403' => [ 'DELETE', self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND  ],
        ];
    }

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string, mixed> [ method ]
     */
    public static function routeProvider404(): array
    {
        return [
            'getAction404'    => [ 'GET' ],
            'getAction404bigId'    => [ 'GET', 999999999999999999 ],
            'putAction404'    => [ 'PUT' ],
            'putAction404bigId'    => [ 'PUT', 999999999999999999 ],
            'deleteAction404' => [ 'DELETE' ],
            'deleteAction404bigId'    => [ 'DELETE', 999999999999999999 ],
        ];
    }
}
