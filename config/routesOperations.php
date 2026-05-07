<?php

/**
 * config/routesOperations.php
 */

use Slim\App;
use TDW\IPanel\Controller\Operacion\OperacionCommandController;
use TDW\IPanel\Controller\Operacion\OperacionQueryController;
use TDW\IPanel\Middleware\JwtMiddleware;

return function (App $app) {

    // Regex para validar un ULID (26 caracteres alfanuméricos Base32)
    $REGEX_OP_ID = '/{operationId:[0-9A-HJKMNP-TV-Z]{26}}';

    // CGET: Retorna todas las operaciones (con filtros opcionales)
    $app->get(
        $_ENV['RUTA_API'] . '/operations',
        OperacionQueryController::class . ':cget'
    )->setName('tdw_operations_cget');

    // GET: Retorna una operación por su ULID
    $app->get(
        $_ENV['RUTA_API'] . '/operations' . $REGEX_OP_ID,
        OperacionQueryController::class . ':get'
    )->setName('tdw_operations_get');

    // POST: Crea una nueva operación (Requiere GESTOR)
    $app->post(
        $_ENV['RUTA_API'] . '/operations',
        OperacionCommandController::class . ':post'
    )->setName('tdw_operations_post')
     ->add(JwtMiddleware::class);

    // PUT: Actualiza una operación existente (Requiere GESTOR)
    $app->put(
        $_ENV['RUTA_API'] . '/operations' . $REGEX_OP_ID,
        OperacionCommandController::class . ':put'
    )->setName('tdw_operations_put')
     ->add(JwtMiddleware::class);

    // DELETE: Elimina una operación (Requiere GESTOR)
    $app->delete(
        $_ENV['RUTA_API'] . '/operations' . $REGEX_OP_ID,
        OperacionCommandController::class . ':delete'
    )->setName('tdw_operations_delete')
     ->add(JwtMiddleware::class);

    // OPTIONS: Soporte para métodos permitidos
    $app->options(
        $_ENV['RUTA_API'] . '/operations' . '[' . $REGEX_OP_ID . ']',
        OperacionQueryController::class . ':options'
    )->setName('tdw_operations_options');
};