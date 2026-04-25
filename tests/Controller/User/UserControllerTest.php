<?php

/**
 * tests/Controller/User/UserControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\IPanel\Controller\User;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use PHPUnit\Framework\Attributes as Tests;
use TDW\IPanel\{Enum\Role, Utility\Utils};
use TDW\Test\IPanel\Controller\BaseTestCase;
use function urlencode;

/**
 * Class UserControllerTest
 */
#[Tests\Group('user')]
class UserControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de usuarios */
    private const string RUTA_API = '/api/v1/users';

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
     * Test OPTIONS /users[/{userId}] 204 NO CONTENT
     */
    public function testOptionsUser204NoContent(): void
    {
        // OPTIONS /api/v1/users
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        // OPTIONS /api/v1/users/{id}
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$faker->randomDigitNotNull()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test POST /users 201 CREATED
     *
     * @return array<string, int|string> user data
     * @throws \JsonException
     */
    #[ArrayShape([
        'id' => "int",
        'email' => "string",
        'role' => "string",
    ])]
    public function testPostUser201Created(): array
    {
        $p_data = [
            'email'     => self::$faker->email(),
            'password'  => self::$faker->password(),
        ];
        self::$gestor['authHeader'] = $this->getTokenHeaders(
            self::$gestor['email'],
            self::$gestor['password']
        );

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
        $responseUser = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        $newUserData = $responseUser['user'];
        self::assertNotEquals(0, $newUserData['id']);
        self::assertSame($p_data['email'], $newUserData['email']);
        self::assertEquals(Role::PUBLICO->name, $newUserData['role']);

        return $newUserData;
    }

    /**
     * Test POST /users 422 UNPROCESSABLE ENTITY
     *
     * @param string|null $email
     * @param string|null $password
     */
    #[Tests\Depends('testPostUser201Created')]
    #[Tests\DataProvider('dataProviderPostUser422')]
    public function testPostUser422UnprocessableEntity(?string $email, ?string $password): void
    {
        $p_data = [
            'email'    => $email,
            'password' => $password,
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
     * Test POST /users 400 BAD REQUEST
     *
     * @param array<string, string|int> $user data returned by testPostUser201Created()
     * @return array<string, string|int>
     */
    #[Tests\Depends('testPostUser201Created')]
    public function testPostUser400BadRequest(array $user): array
    {
        // Mismo email
        $p_data = [
            'email'    => $user['email'],
            'password' => self::$faker->password()
        ];
        $response = $this->runApp(
            'POST',
            self::RUTA_API,
            $p_data,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);

        return $user;
    }

    /**
     * Test GET /users 200 OK
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[Tests\Depends('testPostUser201Created')]
    public function testCGetAllUsers200Ok(): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API,
            null,
            self::$gestor['authHeader']
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        self::assertStringContainsString('users', $r_body);
        $r_data = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('users', $r_data);
        self::assertIsArray($r_data['users']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /users 304 NOT MODIFIED
     *
     * @param array<string> $etag returned by testCGetAllUsers200Ok
     */
    #[Tests\Depends('testCGetAllUsers200Ok')]
    public function testCGetAllUsers304NotModified(array $etag): void
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
     * Test GET /users/{userId} 200 OK
     *
     * @param array<string, string|int> $user data returned by testPostUser201Created()
     *
     * @return array<string> ETag header
     * @throws JsonException
     */
    #[Tests\Depends('testPostUser201Created')]
    public function testGetUser200Ok(array $user): array
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $user['id'],
            null,
            self::$gestor['authHeader']
        );

        self::assertSame(
            200,
            $response->getStatusCode(),
            'Headers: ' . json_encode($this->getTokenHeaders(), JSON_THROW_ON_ERROR)
        );
        self::assertNotEmpty($response->getHeader('ETag'));
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $user_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($user, $user_aux['user']);

        return $response->getHeader('ETag');
    }

    /**
     * Test GET /users/{userId} 304 NOT MODIFIED
     *
     * @param array<string, string|int> $user data returned by testPostUser201Created()
     * @param array<string> $etag returned by testGetUser200Ok
     *
     * @return string Entity Tag
     */
    #[Tests\Depends('testPostUser201Created')]
    #[Tests\Depends('testGetUser200Ok')]
    public function testGetUser304NotModified(array $user, array $etag): string
    {
        $headers = array_merge(
            self::$gestor['authHeader'],
            [ 'If-None-Match' => $etag ]
        );
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . $user['id'],
            null,
            $headers
        );
        self::assertSame(StatusCode::STATUS_NOT_MODIFIED, $response->getStatusCode());

        return $etag[0];
    }

    /**
     * Test GET /users/email/{email} 204 Ok
     *
     * @param array<string, string|int> $user data returned by testPostUser201Created()
     *
     * @return void
     * @throws JsonException
     */
    #[Tests\Depends('testPostUser201Created')]
    public function testGetUseremail204NoContent(array $user): void
    {
        self::assertIsString($user['email']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/email/' . urlencode($user['email'])
        );

        self::assertSame(
            204,
            $response->getStatusCode(),
            'User: ' . json_encode($user, JSON_THROW_ON_ERROR)
        );
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * Test PUT /users/{userId}   209 UPDATED
     *
     * @param array<string, string|int> $user data returned by testPostUser201Created()
     * @param string $etag returned by testGetUser304NotModified()
     *
     * @return array<string, string|int> modified user data
     * @throws JsonException
     */
    #[Tests\Depends('testPostUser201Created')]
    #[Tests\Depends('testGetUser304NotModified')]
    #[Tests\Depends('testPostUser400BadRequest')]
    #[Tests\Depends('testCGetAllUsers304NotModified')]
    #[Tests\Depends('testGetUseremail204NoContent')]
    public function testPutUser209Updated(array $user, string $etag): array
    {
        $p_data = [
            'email'     => self::$faker->email(),
            'password'  => self::$faker->password(),
            'role'      => (0 === self::$faker->numberBetween() % 2)
                ? Role::PUBLICO->name
                : Role::GESTOR->name
        ];

        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $user['id'],
            $p_data,
            array_merge(
                self::$gestor['authHeader'],
                [ 'If-Match' => $etag ]
            )
        );

        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $user_aux = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($user['id'], $user_aux['user']['id']);
        self::assertSame($p_data['email'], $user_aux['user']['email']);
        self::assertEquals($p_data['role'], $user_aux['user']['role']);

        return $user_aux['user'];
    }

    /**
     * Test PUT /users/{userId} 400 BAD REQUEST
     *
     * @param array<string, string|int> $user data returned by testPutUser209Updated()
     */
    #[Tests\Depends('testPutUser209Updated')]
    #[Tests\Depends('testGetUser304NotModified')]
    public function testPutUser400BadRequest(array $user): void
    {
        $p_data = [
                ['email' => self::$publico['email']],        // e-mail already exists
                ['role' => self::$faker->word()],            // unexpected role
            ];
        self::$gestor['authHeader'] = $this->getTokenHeaders(self::$gestor['email'], self::$gestor['password']);
        $r1 = $this->runApp( // Obtains etag header
            'HEAD',
            self::RUTA_API . '/' . $user['id'],
            [],
            self::$gestor['authHeader']
        );
        $headers = array_merge(
            self::$gestor['authHeader'],
            [ 'If-Match' => $r1->getHeader('ETag') ]
        );
        foreach ($p_data as $pair) {
            $response = $this->runApp(
                'PUT',
                self::RUTA_API . '/' . $user['id'],
                $pair,
                $headers
            );
            $this->internalTestError($response, StatusCode::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Test PUT /users/{userId} 428 PRECONDITION REQUIRED
     *
     * @param array<string, string|int> $user data returned by testPutUser209Updated()
     */
    #[Tests\Depends('testPutUser209Updated')]
    public function testPutUser428PreconditionRequired(array $user): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . $user['id'],
            [],
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_PRECONDITION_REQUIRED);
    }

    /**
     * Test DELETE /users/{userId} 204 NO CONTENT
     *
     * @param array<string, string|int> $user data returned by testPostUser400BadRequest()
     *
     * @return int userId
     */
    #[Tests\Depends('testPostUser400BadRequest')]
    #[Tests\Depends('testPutUser428PreconditionRequired')]
    #[Tests\Depends('testGetUseremail204NoContent')]
    #[Tests\Depends('testPutUser400BadRequest')]
    public function testDeleteUser204NoContent(array $user): int
    {
        $response = $this->runApp(
            'DELETE',
            self::RUTA_API . '/' . $user['id'],
            null,
            self::$gestor['authHeader']
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertEmpty($response->getBody()->getContents());

        return (int) $user['id'];
    }

    /**
     * Test GET /users/email/{email} 404 Not Found
     *
     * @param array<string, string|int> $user data returned by testPutUser209Updated()
     */
    #[Tests\Depends('testPutUser209Updated')]
    #[Tests\Depends('testDeleteUser204NoContent')]
    public function testGetUseremail404NotFound(array $user): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/email/' . urlencode((string) $user['email'])
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);

        // Parámetro email nulo
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/email/' # parámetro nulo
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test GET    /users 401 UNAUTHORIZED
     * Test GET    /users/{userId} 401 UNAUTHORIZED
     * Test DELETE /users/{userId} 401 UNAUTHORIZED
     * Test PUT    /users/{userId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    #[Tests\DataProvider('routeProvider401')]
    public function testUserStatus401Unauthorized(string $method, string $uri): void
    {
        $response = $this->runApp(
            $method,
            $uri
        );
        $this->internalTestError($response, StatusCode::STATUS_UNAUTHORIZED);
    }

    /**
     * Test GET    /users/{userId} 404 NOT FOUND
     * Test DELETE /users/{userId} 404 NOT FOUND
     * Test PUT    /users/{userId} 404 NOT FOUND
     *
     * @param int $userId user id. returned by testDeleteUser204()
     * @param string $method
     * @return void
     */
    #[Tests\DataProvider('routeProvider404')]
    #[Tests\Depends('testDeleteUser204NoContent')]
    public function testUserStatus404NotFound(string $method, int $userId): void
    {
        $response = $this->runApp(
            $method,
            self::RUTA_API . '/' . $userId,
            null,
            self::$gestor['authHeader']
        );
        $this->internalTestError($response, StatusCode::STATUS_NOT_FOUND);
    }

    /**
     * Test DELETE /users/{userId} 403 FORBIDDEN => 404 NOT FOUND
     * Test PUT    /users/{userId} 403 FORBIDDEN => 404 NOT FOUND
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     * @return void
     */
    #[Tests\DataProvider('routeProvider403')]
    public function testUserStatus403Forbidden(string $method, string $uri, int $statusCode): void
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
     * @return Generator<string, mixed>
     */
    #[ArrayShape([
        'empty_data' => "array[null]",
        'no_email' => "array[string]",
        'no_passwd' => "array[string]",
        ])]
    public static function dataProviderPostUser422(): Generator
    {
        self::$faker = self::getFaker();
        $fakeEmail = self::$faker->email();
        $fakePasswd = self::$faker->password();

        yield 'empty_data'  => [ null,       null ];
        yield 'no_email'    => [ null,       $fakePasswd ];
        yield 'no_passwd'   => [ $fakeEmail, null ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator<string, mixed> name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "string[]",
        'getAction401' => "string[]",
        'putAction401' => "string[]",
        'deleteAction401' => "string[]",
        ])]
    public static function routeProvider401(): Generator
    {
        yield 'cgetAction401'   => [ 'GET',    self::RUTA_API ];
        yield 'getAction401'    => [ 'GET',    self::RUTA_API . '/1' ];
        yield 'putAction401'    => [ 'PUT',    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ 'DELETE', self::RUTA_API . '/1' ];
    }

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return Generator<string, mixed> 'name' => [ method, id ]
     */
    #[ArrayShape([
        'getAction404' => "string[]",
        'putAction404' => "string[]",
        'deleteAction404' => "string[]",
        ])]
    public static function routeProvider404(): Generator
    {
        yield 'getAction404Id0'    => [ 'GET', 0 ];
        yield 'getAction404NoId'    => [ 'GET' ];
        yield 'putAction404Id0'    => [ 'PUT', 0 ];
        yield 'putAction404NoId'    => [ 'PUT' ];
        yield 'deleteAction404Id0' => [ 'DELETE', 0 ];
        yield 'deleteAction404NoId' => [ 'DELETE' ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN (security) => 404 NOT FOUND)
     *
     * @return Generator<string, mixed> 'name' => [ method, url, statusCode ]
     */
    #[ArrayShape([
        'putAction403' => "array",
        'deleteAction403' => "array",
        ])]
    public static function routeProvider403(): Generator
    {
        yield 'putAction403'    => [ 'PUT',    self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ];
        yield 'deleteAction403' => [ 'DELETE', self::RUTA_API . '/1', StatusCode::STATUS_NOT_FOUND ];
    }
}
