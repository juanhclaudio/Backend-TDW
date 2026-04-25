<?php

/**
 * tests/Controller/LoginControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\IPanel\Controller\Login;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as Tests;
use TDW\IPanel\Controller\Login\{LoginController, OptionsQuery};
use TDW\IPanel\Enum\Role;
use TDW\IPanel\Utility\Utils;
use TDW\Test\IPanel\Controller\BaseTestCase;
use function base64_decode;

/**
 * Class LoginControllerTest
 */
#[Tests\CoversClass(OptionsQuery::class)]
#[Tests\CoversClass(LoginController::class)]
class LoginControllerTest extends BaseTestCase
{
    private static string $ruta_base;   // path de login

    /** @var array<string, mixed> $gestor */
    protected static array $gestor;     // usuario gestor
    /** @var array<string, mixed> $publico */
    protected static array $publico;     // usuario publico

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$ruta_base = $_ENV['RUTA_LOGIN'];

        // Fixture: GESTOR
        self::$gestor = [
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$gestor['id'] = Utils::loadUserData(
            self::$gestor['email'],
            self::$gestor['password'],
            gestor: true
        );

        // Fixture: PUBLICO
        self::$publico = [
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$publico['id'] = Utils::loadUserData(
            self::$publico['email'],
            self::$publico['password'],
            // isWriter: false
        );
    }

    /**
     * Test OPTIONS /access_token 204 NO CONTENT
     */
    public function testOptionsUser204NoContent(): void
    {
        // OPTIONS /access_token
        $response = $this->runApp(
            'OPTIONS',
            self::$ruta_base
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test POST /access_token 404 NOT FOUND
     *
     * @param array<string, string>|null $data
     * @return void
     * @throws JsonException
     */
    #[Tests\DataProvider('proveedorUsuarios404')]
    public function testPostLogin404NotFound(?array $data): void
    {
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            $data,
            [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        self::assertSame(StatusCode::STATUS_BAD_REQUEST, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        /** @var array<string, string> $r_data */
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $r_data);
        self::assertArrayHasKey('error_description', $r_data);
    }

    /**
     * Test POST /access_token 200 OK application/x-www-form-urlencoded
     * @throws JsonException
     */
    public function testPostLogin200OkUrlEncoded(): void
    {
        $data = [
            'username' => self::$gestor['email'],
            'password' => self::$gestor['password']
        ];
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            $data,
            [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertTrue($response->hasHeader('Authorization'));
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($r_data['access_token']);
        self::assertSame('Bearer', $r_data['token_type']);
        self::assertNotEmpty($r_data['expires_in']);
    }

    /**
     * Test POST /access_token 200 OK (application/json)
     *
     * @throws JsonException
     */
    public function testPostLogin200OkApplicationJson(): void
    {
        $data = [
            'username' => self::$gestor['email'],
            'password' => self::$gestor['password']
        ];
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            json_encode($data, JSON_THROW_ON_ERROR),
            [ 'Content-Type' => 'application/json' ]
        );

        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        self::assertTrue($response->hasHeader('Cache-Control'));
        self::assertTrue($response->hasHeader('Authorization'));
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertNotEmpty($r_data['access_token']);
        self::assertSame('Bearer', $r_data['token_type']);
        self::assertNotEmpty($r_data['expires_in']);
    }

    /**
     * @param string $user [ publico | gestor ]
     * @param array<Role> $reqScopes
     * @param Role $expectedScope
     *
     * @throws JsonException
     */
    #[Tests\DataProvider('proveedorAmbitos')]
    public function testLoginWithScopes200Ok(string $user, array $reqScopes, Role $expectedScope): void
    {
        $userData = ('publico' === $user) ? self::$publico : self::$gestor;
        $requestedScopes = [];
        foreach ($reqScopes as $role) {
            $requestedScopes[] = $role->value;
        }
        $post_data = [
            'username' => $userData['email'],
            'password' => $userData['password'],
            'scope'    => implode('+', $requestedScopes),
        ];
        $response = $this->runApp(
            'POST',
            self::$ruta_base,
            $post_data,
            [ 'Content-Type' => 'application/x-www-form-urlencoded' ]
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->hasHeader('Authorization'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Bearer', $r_data['token_type']);
        self::assertGreaterThan(0, $r_data['expires_in']);
        self::assertNotEmpty($r_data['access_token']);

        $payload = explode('.', $r_data['access_token']);
        $data = json_decode((string) base64_decode($payload[1], true), true, 512, JSON_THROW_ON_ERROR);
        self::assertContains($expectedScope->value, $data['scopes']);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Proveedor de usuarios 404
     *
     * @return Generator array<string, array>
     */
    #[ArrayShape([
        'empty_user' => "array[array[string, string]]",
        'no_password' => "array[array[string, string]]",
        'no_username' => "array[array[string, string]]",
        'incorrect_username' => "array[array[string, string]]",
        'incorrect_passwd' => "array[array[string, string]]",
        ])]
    public static function proveedorUsuarios404(): Generator
    {
        self::$faker = self::getFaker();
        $fakeUseremail = self::$faker->email();
        $fakePasswd = self::$faker->password();

        yield 'no_data'  => [ null ];

        yield 'empty_user'  =>
                [ [ ] ];

        yield 'no_password' =>
                [ [ 'username' => $fakeUseremail ] ];

        yield 'no_username' =>
                [ [ 'password' => $fakePasswd ] ];

        yield 'incorrect_username' =>
                [ [ 'username' => $fakeUseremail, 'password' => $fakePasswd ] ];

        yield 'incorrect_passwd' =>
                [ [ 'username' => $fakeUseremail, 'password' => $fakePasswd ] ];
    }

    /**
     * Genera diferentes casos de prueba con roles solicitados -> roles efectivos incluídos
     * para los usuarios de tipo 'publico' y 'gestor'
     *
     * @return Generator array<string, array> 'name' => [ userdata, requestedScope[], expectedScope]
     */
    public static function proveedorAmbitos(): Generator
    {
        yield 'publico -- r' => ['publico', [], Role::PUBLICO];
        yield 'publico r- r' => ['publico', [Role::PUBLICO], Role::PUBLICO];
        yield 'publico rw r' => ['publico', [Role::PUBLICO, Role::GESTOR], Role::PUBLICO];
        yield 'publico -w r' => ['publico', [Role::GESTOR], Role::PUBLICO];
        yield 'gestor -- r' => ['gestor', [], Role::PUBLICO];
        yield 'gestor -- w' => ['gestor', [], Role::GESTOR];
        yield 'gestor r- r' => ['gestor', [Role::PUBLICO], Role::PUBLICO];
        yield 'gestor -w r' => ['gestor', [Role::GESTOR], Role::PUBLICO];
        yield 'gestor -w w' => ['gestor', [Role::GESTOR], Role::GESTOR];
        yield 'gestor rw r' => ['gestor', [Role::PUBLICO, Role::GESTOR], Role::PUBLICO];
        yield 'gestor rw w' => ['gestor', [Role::PUBLICO, Role::GESTOR], Role::GESTOR];
    }
}
