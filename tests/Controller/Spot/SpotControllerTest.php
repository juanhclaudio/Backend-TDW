<?php

namespace TDW\Test\IPanel\Controller\Spot;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PHPUnit\Framework\Attributes as TestAttr;
use TDW\IPanel\Controller\Spot\SpotCommandController;
use TDW\IPanel\Controller\Spot\SpotQueryController;
use TDW\Test\IPanel\Controller\BaseTestCase;

#[TestAttr\CoversClass(SpotQueryController::class)]
#[TestAttr\CoversClass(SpotCommandController::class)]
class SpotControllerTest extends BaseTestCase
{
    protected const string RUTA_API = '/api/v1/spots';

    protected static array $gestor;
    protected static array $spot;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$gestor = [
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];
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
        $result = json_decode((string) $response->getBody(), true);
        self::assertArrayHasKey('puntoId', $result);
        return $result['puntoId'];
    }

    #[TestAttr\Depends('testPostSpot201')]
    public function testCGet200(): void
    {
        $response = $this->runApp('GET', self::RUTA_API);
        self::assertSame(StatusCode::STATUS_OK, $response->getStatusCode());
        self::assertStringContainsString('puntos', (string) $response->getBody());
    }

    public function testOptions(): void
    {
        $response = $this->runApp('OPTIONS', self::RUTA_API);
        self::assertSame(StatusCode::STATUS_NO_CONTENT, $response->getStatusCode());
    }
}