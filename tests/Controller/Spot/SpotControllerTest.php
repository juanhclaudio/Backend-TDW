<?php

namespace TDW\Test\IPanel\Controller\Spot;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\Attributes as TestAttr;
use TDW\IPanel\Controller\Spot\SpotCommandController;
use TDW\IPanel\Controller\Spot\SpotQueryController;
use TDW\IPanel\Utility\Utils;
use TDW\Test\IPanel\Controller\BaseTestCase;

#[TestAttr\CoversClass(SpotQueryController::class)]
#[TestAttr\CoversClass(SpotCommandController::class)]
#[TestAttr\Group('spot')]
class SpotControllerTest extends BaseTestCase
{
    protected const string RUTA_API = '/api/v1/spots';

    protected static array $gestor;
    protected static array $publico;
    protected static array $spot;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$gestor = [
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        self::$publico = [
            'email'    => 'user@infopanel.com',
            'password' => 'user123',
        ];

        Utils::loadUserData(self::$gestor['email'], self::$gestor['password'], true);
        Utils::loadUserData(self::$publico['email'], self::$publico['password'], false);

        self::$spot = [
            'codigo' => 'P' . rand(100, 999),
            'tipo' => 'PUERTA'
        ];
    }

    public function testPostSpot201(): int
    {
        $headers = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $response = $this->runApp('POST', self::RUTA_API, self::$spot, $headers);

        self::assertSame(StatusCode::STATUS_CREATED, $response->getStatusCode());
        $r_body = json_decode((string) $response->getBody(), true);


        self::assertArrayHasKey('punto', $r_body);
        self::assertArrayHasKey('puntoId', $r_body['punto']);

        return $r_body['punto']['puntoId'];
    }

    #[TestAttr\Depends('testPostSpot201')]
    public function testCGet200(int $puntoId): int
    {
        $response = $this->runApp('GET', self::RUTA_API);
        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        self::assertStringContainsString('puntos', (string) $response->getBody());
        return $puntoId;
    }

    #[TestAttr\Depends('testCGet200')]
    public function testGet200(int $puntoId): int
    {
        $response = $this->runApp('GET', self::RUTA_API . '/' . $puntoId);
        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());

        $r_body = json_decode((string) $response->getBody(), true);
        self::assertSame($puntoId, $r_body['punto']['puntoId']);
        return $puntoId;
    }

    #[TestAttr\Depends('testGet200')]
    public function testPut209(int $puntoId): int
    {
        $headers = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);

        $responseGet = $this->runApp('GET', self::RUTA_API . '/' . $puntoId);
        $etag = $responseGet->getHeaderLine('ETag');

        $headers['If-Match'] = $etag;
        $updateData = ['codigo' => 'NEW' . rand(10, 99)];

        $response = $this->runApp('PUT', self::RUTA_API . '/' . $puntoId, $updateData, $headers);
        self::assertSame(209, $response->getStatusCode());
        return $puntoId;
    }

    #[TestAttr\Depends('testPut209')]
    public function testDelete204(int $puntoId): void
    {
        $headers = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $response = $this->runApp('DELETE', self::RUTA_API . '/' . $puntoId, null, $headers);
        self::assertSame(StatusCode::STATUS_NO_CONTENT, $response->getStatusCode());
    }

    #[TestAttr\DataProvider('routeProvider404')]
    public function testSpots404(string $method, string $uri): void
    {
        $headers = $this->getTokenHeaders(self::$publico['email'], self::$publico['password']);
        $response = $this->runApp($method, $uri, self::$spot, $headers);
        self::assertSame(StatusCode::STATUS_NOT_FOUND, $response->getStatusCode());
    }

    public static function routeProvider404(): \Generator
    {
        yield ['POST',   self::RUTA_API];
        yield ['PUT',    self::RUTA_API . '/1'];
        yield ['DELETE', self::RUTA_API . '/1'];
    }

    public function testOptions(): void
    {
        $response = $this->runApp('OPTIONS', self::RUTA_API);
        self::assertSame(204, $response->getStatusCode());
    }
}
