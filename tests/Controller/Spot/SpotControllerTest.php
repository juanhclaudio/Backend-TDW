<?php

/**
 * tests/Controller/Spot/SpotControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\IPanel\Controller\Spot;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as TestAttr;
use TDW\IPanel\Controller\Spot\SpotCommandController;
use TDW\IPanel\Controller\Spot\SpotQueryController;
use TDW\IPanel\Enum\TipoPunto;
use TDW\IPanel\Utility\Utils;
use TDW\Test\IPanel\Controller\BaseTestCase;

/**
 * Class SpotControllerTest
 */
#[TestAttr\CoversClass(SpotQueryController::class)]
#[TestAttr\CoversClass(SpotCommandController::class)]
class SpotControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de puntos */
    protected const string RUTA_API = '/api/v1/spots';

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
     * Test GET /spots 404 NOT FOUND
     */
    public function testCGetSpots404NotFound(): void
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
     * Test POST /spots 201 CREATED
     *
     * @return array<string, int|string> SpotData
     * @throws JsonException
     */
    #[TestAttr\Depends('testCGetSpots404NotFound')]
    public function testPostSpot201Created(): array
    {
        $p_data = [
            'codigo' => self::$faker->text(10),
            'tipo'   => self::$faker->randomElement(TipoPunto::ALL_VALUES),
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
        $responseSpot = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('punto', $responseSpot);
        $spotData = $responseSpot['punto'];
        self::assertNotEquals(0, $spotData['puntoId']);
        self::assertSame($p_data['codigo'], $spotData['codigo']);
        self::assertSame($p_data['tipo'], $spotData['tipo']);
        self::assertIsArray($spotData['operaciones']);

        return $spotData;
    }

    /**
     * Test POST /spots 422 UNPROCESSABLE ENTITY
     */
    #[TestAttr\Depends('testCGetSpots404NotFound')]
    public function testPostSpot422UnprocessableEntity(): void
    {
        $p_data = [
            // 'codigo' => self::$faker->text(10),
            'tipo'   => self::$faker->randomElement(TipoPunto::ALL_VALUES),
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_UNPROCESSABLE_ENTITY);

        $p_data = [
            'codigo' => self::$faker->text(10),
            // 'tipo'   => self::$faker->randomElement(TipoPunto::ALL_VALUES),
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
     * Test POST /spots 400 BAD REQUEST
     *
     * @param array<string, int|string> $spot data returned by testPostSpot201Created()
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    public function testPostSpot400BadRequest(array $spot): void
    {
        // Mismo código
        $p_data = [
            'codigo' => $spot['codigo'],
            'tipo'   => self::$faker->randomElement(TipoPunto::ALL_VALUES),
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
     * Test GET /spots 200 OK
     *
     * @param array<string, int|string> $spot data returned by testPostSpot201Created()
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    public function testCGetSpots200Ok(array $spot): array
    {
        self::assertIsString($spot['codigo']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '?name=' . substr($spot['codigo'], 0, -2) . '&order=id&ordering=DESC',
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $etag = $response->getHeader('ETag');
        self::assertNotEmpty($etag);
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('puntos', $r_data);
        self::assertIsArray($r_data['puntos']);

        return $etag;
    }

    /**
     * Test GET /spots 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetspots200Ok
     */
    #[TestAttr\Depends('testCGetSpots200Ok')]
    public function testCGetSpots304NotModified(array $etag): void
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
     * Test GET /spots/{spotId} 200 OK
     *
     * @param array<string, int|string> $spot data returned by testPostSpot201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    public function testGetSpot200Ok(array $spot): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $spot['puntoId'],
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $spot_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($spot, $spot_aux['punto']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /spots/{spotId} 304 NOT MODIFIED
     *
     * @param array<string, int|string> $spot data returned by testPostspot201Created()
     * @param array<string> $etag returned by testGetspot200Ok
     *
     * @return string Entity Tag
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    #[TestAttr\Depends('testGetSpot200Ok')]
    public function testGetSpot304NotModified(array $spot, array $etag): string
    {
        $headers = array_merge(
            self::$gestor['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $spot['puntoId'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /spots/name/{spotname} 204 NO CONTENT
     *
     * @param array<string, int|string> $spot data returned by testPostSpot201()
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    public function testGetSpotname204NoContent(array $spot): void
    {
        // GET /spots/name/{spotname}
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/name/' . $spot['codigo']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /spots/{spotId}   209 UPDATED
     *
     * @param array<string, int|string> $spot data returned by testPostSpot201Created()
     * @param string $etag returned by testGetSpot304NotModified
     *
     * @return array<string, int|string> modified spot data
     * @throws JsonException
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    #[TestAttr\Depends('testGetSpot304NotModified')]
    #[TestAttr\Depends('testPostSpot400BadRequest')]
    #[TestAttr\Depends('testCGetSpots304NotModified')]
    #[TestAttr\Depends('testGetSpotname204NoContent')]
    public function testPutSpot209Updated(array $spot, string $etag): array
    {
        $p_data = [
            'codigo' => self::$faker->text(10),
            'tipo'   => self::$faker->randomElement(TipoPunto::ALL_VALUES),
        ];

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $spot['puntoId'],
            $p_data,
            array_merge(
                self::$gestor['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $spot_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('punto', $spot_aux);
        self::assertSame($spot['puntoId'], $spot_aux['punto']['puntoId']);
        self::assertSame($p_data['codigo'], $spot_aux['punto']['codigo']);
        self::assertSame($p_data['tipo'], $spot_aux['punto']['tipo']);

        return $spot_aux['punto'];
    }

    /**
     * Test PUT /spots/{spotId} 400 BAD REQUEST
     *
     * @param array<string, int|string> $spot data returned by testPutSpot209Updated()
     */
    #[TestAttr\Depends('testPutSpot209Updated')]
    public function testPutSpot400BadRequest(array $spot): void
    {
        $p_data = [
            'codigo' => self::$faker->text(10),
            'tipo'   => self::$faker->randomElement(TipoPunto::ALL_VALUES),
        ];
        $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $r1 = $this->runApp( // Obtains etag header
            'HEAD',
            self::RUTA_API . '/' . $spot['puntoId'],
            [],
            self::$gestor['authHeader']
        );

        // spot codigo already exists
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $spot['puntoId'],
            [ 'codigo' => $p_data['codigo'] ],
            array_merge(
                self::$gestor['authHeader'],
                [ 'If-Match' => $r1->getHeader('ETag') ]
            )
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
    }

    /**
     * Test PUT /spot/{spotId} 428 PRECONDITION REQUIRED
     *
     * @param array<string, int|string> $spot data returned by testPutSpot209Updated()
     */
    #[TestAttr\Depends('testPutSpot209Updated')]
    public function testPutSpot428PreconditionRequired(array $spot): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $spot['puntoId'],
            [],
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test OPTIONS /spots[/{spotId}] NO CONTENT
     */
    public function testOptionsSpot204NoContent(): void
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
     * Test DELETE /spots/{spotId} 204 NO CONTENT
     *
     * @param array<string, int|string> $spot data returned by testPostSpot201Created()
     *
     * @return int spotId
     */
    #[TestAttr\Depends('testPostSpot201Created')]
    #[TestAttr\Depends('testPostSpot400BadRequest')]
    #[TestAttr\Depends('testPostSpot422UnprocessableEntity')]
    #[TestAttr\Depends('testPutSpot400BadRequest')]
    #[TestAttr\Depends('testPutSpot428PreconditionRequired')]
    #[TestAttr\Depends('testGetSpotname204NoContent')]
    public function testDeleteSpot204NoContent(array $spot): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $spot['puntoId'],
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $spot['puntoId'];
    }

    /**
     * Test GET /spots/name/{spotname} 404 NOT FOUND
     *
     * @param array<string, int|string> $spot data returned by testPutSpot209Updated()
     */
    #[TestAttr\Depends('testPutSpot209Updated')]
    #[TestAttr\Depends('testDeleteSpot204NoContent')]
    public function testGetSpotname404NotFound(array $spot): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/name/' . $spot['codigo']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);

        // Parámetro spotname nulo
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/name/' # parámetro nulo
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /spots/{spotId} 404 NOT FOUND
     * Test PUT    /spots/{spotId} 404 NOT FOUND
     * Test DELETE /spots/{spotId} 404 NOT FOUND
     *
     * @param mixed $spotId spot id. returned by testDeleteSpot204NoContent()
     * @param string $method
     *
     * @return void
     */
    #[TestAttr\DataProvider('routeProvider404')]
    #[TestAttr\Depends('testDeleteSpot204NoContent')]
    public function testSpotStatus404NotFound(string $method, mixed $spotId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $spotId,
            null,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /spots 401 UNAUTHORIZED
     * Test POST   /spots 401 UNAUTHORIZED
     * Test PUT    /spots/{spotId} 401 UNAUTHORIZED
     * Test DELETE /spots/{spotId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    #[TestAttr\DataProvider('routeProvider401')]
    public function testSpotStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test POST   /spots 403 FORBIDDEN
     * Test PUT    /spots/{spotId} 403 FORBIDDEN => 404 NOT FOUND
     * Test DELETE /spots/{spotId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     *
     * @return void
     */
    #[TestAttr\DataProvider('routeProvider403')]
    public function testSpotStatus403Forbidden(string $method, string $uri, int $statusCode): void
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
