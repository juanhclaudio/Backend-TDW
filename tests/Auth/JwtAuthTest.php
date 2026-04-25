<?php

/**
 * tests/Auth/JwtAuthTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

declare(strict_types=1);

namespace TDW\Test\IPanel\Auth;

use DI\ContainerBuilder;
use Exception;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Faker\Provider\es_ES as FakerProvider;
use Lcobucci\JWT\Token\Plain;
use PHPUnit\Framework\Attributes\{ CoversClass, Depends };
use PHPUnit\Framework\TestCase;
use TDW\IPanel\Auth\JwtAuth;
use TDW\IPanel\Model\User;
use Throwable;

#[CoversClass(JwtAuth::class)]
class JwtAuthTest extends TestCase
{
    protected static JwtAuth $jwtAuth;
    protected static mixed $settings = [];
    protected static FakerGenerator $faker;
    /** @phpstan-var non-empty-string $email */
    protected static string $email;

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

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$faker = self::getFaker();
        $email = self::$faker->email();
        self::assertNotEmpty($email);
        self::$email = $email;
        try {
            $containerBuilder = new ContainerBuilder();
            $container = $containerBuilder
                ->addDefinitions(
                    __DIR__ .  '/../../config/container.php'
                )
                ->build();
            self::$settings = $container->get('settings')['jwt'];
            self::$jwtAuth = $container->get(JwtAuth::class);
        } catch (Throwable $e) {
            die('code:' . $e->getCode() . ', msg=' . $e->getMessage());
        }
    }

    public function testGetLifetime(): void
    {
        self::assertSame(
            self::$settings['lifetime'],
            self::$jwtAuth->getLifetime()
        );
    }

    /**
     * @return Plain jwt
     *
     * @throws \DateMalformedStringException
     */
    public function testCreateJwt(): Plain
    {
        $user = new User(email: self::$email);
        $plainJwt = self::$jwtAuth->createJwt($user);
        self::assertNotEmpty(self::$settings['issuer']);
        self::assertTrue(
            $plainJwt->hasBeenIssuedBy(self::$settings['issuer'])
        );
        self::assertNotEmpty(self::$settings['client-id']);
        self::assertTrue(
            $plainJwt->isPermittedFor(self::$settings['client-id'])
        );
        self::assertTrue(
            $plainJwt->isRelatedTo(self::$email)
        );

        return $plainJwt;
    }

    #[Depends('testCreateJwt')]
    public function testCreateParsedToken(Plain $token): void
    {
        self::assertNotEmpty(self::$settings['issuer']);
        $parsedToken = self::$jwtAuth->createParsedToken($token->toString());
        self::assertTrue(
            $parsedToken->hasBeenIssuedBy(self::$settings['issuer'])
        );
        self::assertNotEmpty(self::$settings['client-id']);
        self::assertTrue(
            $parsedToken->isPermittedFor(self::$settings['client-id'])
        );
        self::assertTrue($parsedToken->isRelatedTo(self::$email));
    }

    #[Depends('testCreateJwt')]
    public function testClaimsOk(Plain $accessToken): void
    {
        $claims = $accessToken->claims();
        self::assertSame(
            0,
            $claims->get('uid')
        );
        self::assertSame(
            self::$email,
            $claims->get('email')
        );
        self::assertIsArray($claims->get('scopes'));
    }

    #[Depends('testCreateJwt')]
    public function testValidateTokenOK(Plain $accessToken): void
    {
        self::assertNotEmpty($accessToken->toString());
        self::assertTrue(
            self::$jwtAuth->validateToken($accessToken->toString())
        );
    }

    #[Depends('testCreateJwt')]
    public function testValidateTokenNotOk(Plain $accessToken): void
    {
        $this->expectException(Exception::class);
        self::$jwtAuth->validateToken($accessToken->toString() . 'x');
    }
}
