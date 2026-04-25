<?php

use DI\ContainerBuilder;
use Slim\App;

$containerBuilder = new ContainerBuilder();

// Set up settings
$containerBuilder->addDefinitions(__DIR__ . '/container.php');

try {
    // Build PHP-DI Container instance
    $container = $containerBuilder->build();

    // Create App instance
    $app = $container->get(App::class);
} catch (Throwable $e) {
    fwrite(STDERR, 'EXCEPCIÓN: ' . $e->getCode() . ' - ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Register routes
(require __DIR__ . '/routes.php')($app);
(require __DIR__ . '/routesUsers.php')($app);
(require __DIR__ . '/routesOperators.php')($app);
(require __DIR__ . '/routesSpots.php')($app);

// Register middleware
(require __DIR__ . '/middleware.php')($app);

return $app;
