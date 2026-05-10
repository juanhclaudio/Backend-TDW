<?php

namespace TDW\Test\IPanel\Controller\Operacion;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\Attributes as TestAttr;
use TDW\IPanel\Controller\Operacion\OperacionCommandController;
use TDW\IPanel\Controller\Operacion\OperacionQueryController;
use TDW\IPanel\Utility\Utils;
use TDW\Test\IPanel\Controller\BaseTestCase;

#[TestAttr\CoversClass(OperacionQueryController::class)]
#[TestAttr\CoversClass(OperacionCommandController::class)]
#[TestAttr\Group('operacion')]
class OperacionControllerTest extends BaseTestCase
{
    protected const string RUTA_API = '/api/v1/operations';
    protected static array $gestor;
    protected static array $publico;
    protected static array $operacion;

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

        Utils::updateSchema();
        Utils::loadUserData(self::$gestor['email'], self::$gestor['password'], true);
        Utils::loadUserData(self::$publico['email'], self::$publico['password'], false);
        Utils::loadOperatorData('Iberia', 'IB', '#ff0000');
        Utils::loadSpotData('PUERTA', 'P20');

        self::$operacion = [
            'tipo' => 'vuelo',
            'codigo' => 'IB' . rand(1000, 9999),
            'sentido' => 'salida',
            'origen' => 'Madrid',
            'destino' => 'Tokio',
            'estado' => 'programado',
            'operadorId' => 1,
            'puntoId' => 1,
            'horaProgramada' => date('Y-m-d H:i:s')
        ];
    }

    public function testPost201(): string
    {
        $headers = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $response = $this->runApp('POST', self::RUTA_API, self::$operacion, $headers);
        self::assertSame(StatusCode::STATUS_CREATED, $response->getStatusCode());
        
        $r_body = json_decode((string) $response->getBody(), true);
        return $r_body['operacion']['operacionId'];
    }

    #[TestAttr\Depends('testPost201')]
    public function testCGet200(string $operacionId): string
    {
        $response = $this->runApp('GET', self::RUTA_API);
        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        self::assertStringContainsString('operaciones', (string) $response->getBody());
        return $operacionId;
    }

    #[TestAttr\Depends('testCGet200')]
    public function testGet200(string $operacionId): string
    {
        $response = $this->runApp('GET', self::RUTA_API . '/' . $operacionId);
        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        return $operacionId;
    }

    #[TestAttr\Depends('testGet200')]
    public function testPut209(string $operacionId): string
    {
        $headers = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $responseGet = $this->runApp('GET', self::RUTA_API . '/' . $operacionId);
        $etag = $responseGet->getHeaderLine('ETag');

        $headers['If-Match'] = $etag;
        $response = $this->runApp('PUT', self::RUTA_API . '/' . $operacionId, ['estado' => 'en ruta'], $headers);
        self::assertSame(209, $response->getStatusCode());
        return $operacionId;
    }

    #[TestAttr\Depends('testPut209')]
    public function testDelete204(string $operacionId): void
    {
        $headers = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $response = $this->runApp('DELETE', self::RUTA_API . '/' . $operacionId, null, $headers);
        self::assertSame(StatusCode::STATUS_NO_CONTENT, $response->getStatusCode());
    }

    #[TestAttr\DataProvider('routeProvider401')]
    public function testOperaciones401(string $method, string $uri): void
    {
        $response = $this->runApp($method, $uri);
        self::assertSame(StatusCode::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }

    #[TestAttr\DataProvider('routeProvider404')]
    public function testOperaciones404(string $method, string $uri): void
    {
        $headers = $this->getTokenHeaders(self::$publico['email'], self::$publico['password']);
        $response = $this->runApp($method, $uri, self::$operacion, $headers);
        self::assertSame(StatusCode::STATUS_NOT_FOUND, $response->getStatusCode());
    }

    public static function routeProvider401(): \Generator
    {
        yield ['POST',   self::RUTA_API];
        yield ['PUT',    self::RUTA_API . '/01AN4V07BY7WZYG88G9DAZ3PGR'];
        yield ['DELETE', self::RUTA_API . '/01AN4V07BY7WZYG88G9DAZ3PGR'];
    }

    public static function routeProvider404(): \Generator
    {
        yield ['POST',   self::RUTA_API];
        yield ['PUT',    self::RUTA_API . '/01AN4V07BY7WZYG88G9DAZ3PGR'];
        yield ['DELETE', self::RUTA_API . '/01AN4V07BY7WZYG88G9DAZ3PGR'];
    }

    public function testOptions(): void
    {
        $response = $this->runApp('OPTIONS', self::RUTA_API);
        self::assertSame(204, $response->getStatusCode());
    }
}