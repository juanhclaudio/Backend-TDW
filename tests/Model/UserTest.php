<?php

/**
 * tests/Model/UserTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\IPanel\Model;

use Faker\Factory;
use Faker\Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes as Tests;
use PHPUnit\Framework\TestCase;
use TDW\IPanel\Model\{TDW\IPanel\Enum\Role, User};

/**
 * Class UserTest
 */
#[Tests\Group('users')]
#[Tests\CoversClass(User::class)]
class UserTest extends TestCase
{
    private static User $user;
    private static Generator $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$faker = Factory::create('es_ES');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $email = self::$faker->email();
        self::assertNotEmpty($email);
        self::$user  = new User(
            email: $email,
        );
    }

    public function testConstructorOK(): void
    {
        $email = self::$faker->email();
        self::assertNotEmpty($email);
        $passwd = self::$faker->password();
        self::$user = new User(email: $email, password: $passwd);
        static::assertSame(0, self::$user->getId());
        static::assertSame($email, self::$user->getEmail());
        static::assertTrue(self::$user->validatePassword($passwd));
        static::assertTrue(self::$user->hasRole(\TDW\IPanel\Enum\Role::PUBLICO));
        static::assertFalse(self::$user->hasRole(\TDW\IPanel\Enum\Role::GESTOR));
    }

    public function testConstructorInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$user = new User(role: self::$faker->word());
    }

    public function testGetId(): void
    {
        static::assertSame(
            0,
            self::$user->getId()
        );
    }

    public function testGetSetEmail(): void
    {
        $userEmail = self::$faker->email();
        static::assertNotEmpty($userEmail);
        self::$user->setEmail($userEmail);
        static::assertSame(
            $userEmail,
            self::$user->getEmail()
        );
    }

    public function testRoles(): void
    {
        self::$user->setRole(\TDW\IPanel\Enum\Role::INACTIVO);
        static::assertTrue(self::$user->hasRole(\TDW\IPanel\Enum\Role::INACTIVO));
        static::assertFalse(self::$user->hasRole(\TDW\IPanel\Enum\Role::PUBLICO));
        static::assertFalse(self::$user->hasRole(\TDW\IPanel\Enum\Role::GESTOR));
        static::assertFalse(self::$user->hasRole(self::$faker->word()));
        $roles = self::$user->getRoles();
        static::assertTrue(in_array(\TDW\IPanel\Enum\Role::INACTIVO, $roles, true));
        static::assertFalse(in_array(\TDW\IPanel\Enum\Role::PUBLICO, $roles, true) === true);
        static::assertFalse(in_array(\TDW\IPanel\Enum\Role::GESTOR, $roles, true) === true);

        self::$user->setRole(\TDW\IPanel\Enum\Role::PUBLICO);
        static::assertTrue(self::$user->hasRole(\TDW\IPanel\Enum\Role::PUBLICO));
        static::assertFalse(self::$user->hasRole(\TDW\IPanel\Enum\Role::INACTIVO));
        static::assertFalse(self::$user->hasRole(\TDW\IPanel\Enum\Role::GESTOR));
        static::assertFalse(self::$user->hasRole(self::$faker->word()));
        $roles = self::$user->getRoles();
        static::assertTrue(in_array(\TDW\IPanel\Enum\Role::PUBLICO, $roles, true));
        static::assertFalse(in_array(\TDW\IPanel\Enum\Role::INACTIVO, $roles, true));
        static::assertFalse(in_array(\TDW\IPanel\Enum\Role::GESTOR, $roles, true));

        self::$user->setRole(\TDW\IPanel\Enum\Role::GESTOR->value);
        static::assertTrue(self::$user->hasRole(\TDW\IPanel\Enum\Role::GESTOR));
        static::assertTrue(self::$user->hasRole(\TDW\IPanel\Enum\Role::PUBLICO));
        static::assertFalse(self::$user->hasRole(self::$faker->word()));
        $roles = self::$user->getRoles();
        static::assertFalse(in_array(\TDW\IPanel\Enum\Role::INACTIVO, $roles, true));
        static::assertTrue(in_array(\TDW\IPanel\Enum\Role::PUBLICO, $roles, true));
        static::assertTrue(in_array(\TDW\IPanel\Enum\Role::GESTOR, $roles, true));
    }

    public function testRoleExpectInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$user->setRole(self::$faker->word());
    }

    public function testGetSetValidatePassword(): void
    {
        $password = self::$faker->password();
        self::$user->setPassword($password);
        static::assertTrue(password_verify($password, self::$user->getPassword()));
        static::assertTrue(self::$user->validatePassword($password));
    }

    public function testToString(): void
    {
        $email = self::$faker->email();
        static::assertNotEmpty($email);
        self::$user->setEmail($email);
        static::assertStringContainsString(
            $email,
            self::$user->__toString()
        );
    }

    public function testJsonSerialize(): void
    {
        $json = (string) json_encode(self::$user, JSON_PARTIAL_OUTPUT_ON_ERROR);
        static::assertJson($json);
        $data = json_decode($json, true);
        static::assertArrayHasKey(
            'user',
            $data
        );
        static::assertArrayHasKey(
            'id',
            $data['user']
        );
        static::assertArrayHasKey(
            'email',
            $data['user']
        );
        static::assertArrayHasKey(
            'role',
            $data['user']
        );
    }
}
