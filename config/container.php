<?php

use Doctrine\ORM\EntityManager;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\{HasClaim, IssuedBy, PermittedFor, SignedWith, StrictValidAt};
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use TDW\IPanel\Auth\JwtAuth;
use TDW\IPanel\Utility\DoctrineConnector;

return [
    // Application settings
    'settings' => fn() => require __DIR__ . '/settings.php',

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    // HTTP factories
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    },

    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    LoggerInterface::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['logger'];
        $logger = new Logger('app');

        $filename = sprintf('%s/app.log', $settings['path']);
        $level = $settings['level'];
        $rotatingFileHandler = new RotatingFileHandler($filename, 0, $level, true, 0777);
        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, false, true));
        $logger->pushHandler($rotatingFileHandler);

        return $logger;
    },

    EntityManager::class => DoctrineConnector::getEntityManager(),

    // And add this entry
    JwtAuth::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');

        $issuer = $settings['jwt']['issuer'];
        $clientId = $settings['jwt']['client-id'];
        $lifetime = $settings['jwt']['lifetime'];
        $privateKeyFile = $settings['jwt']['private_key_file'];
        $publicKeyFile = $settings['jwt']['public_key_file'];
        $secretPhrase = $settings['app']['secret'];

        $jwtConfig = Lcobucci\JWT\Configuration::forAsymmetricSigner(
            // You may use RSA or ECDSA and all their variations (256, 384, and 512) and EdDSA over Curve25519
            new Signer\Ecdsa\Sha256(),
            InMemory::file($privateKeyFile),
            InMemory::base64Encoded($secretPhrase)
        )->withValidationConstraints(
            new IssuedBy($issuer),
            new PermittedFor($clientId),
            new SignedWith(new Signer\Ecdsa\Sha256(), InMemory::file($publicKeyFile)),
            new StrictValidAt(SystemClock::fromSystemTimezone()),
            new HasClaim('uid'),
            new HasClaim('email'),
            new HasClaim('scopes'),
        );

        return new JwtAuth($jwtConfig, $issuer, $clientId, $lifetime);
    },
];
