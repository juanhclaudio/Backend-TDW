<?php

/**
 * config/routesOperators.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\IPanel\Controller\Operator\OperatorCommandController;
use TDW\IPanel\Controller\Operator\OperatorQueryController;
use TDW\IPanel\Middleware\JwtMiddleware;

/**
 * ############################################################
 * routes /api/v1/operators
 * ############################################################
 * @param App $app Slim4 application
 */
return function (App $app) {

    $REGEX_OPERATOR_ID = '/{operatorId:[0-9]+}';
    $REGEX_NAME = '/{operatorName:[\sa-zA-Z0-9()áéíóúÁÉÍÓÚñÑ%$@_\.+-]*}';
    $SEARCH_CRITERIA = '/{criterion:(?:name|acronym)}';

    // CGET|HEAD: Returns all operators
    // Path: /operators
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS,
        OperatorQueryController::class . ':cget'
    )->setName('tdw_operators_cget');

    // GET|HEAD: Returns a operator based on a single ID
    // Path: /operators/{operatorId}
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS . $REGEX_OPERATOR_ID,
        OperatorQueryController::class . ':get'
    )->setName('tdw_operators_read');

    // GET: Returns status code 204 if operatorname exists
    // Path: /operators/name/{name} || /operators/acronym/{name}
    $app->get(
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS . $SEARCH_CRITERIA . $REGEX_NAME,
        OperatorQueryController::class . ':getElementByName'
    )->setName('tdw_operators_get_operatorname');

    // OPTIONS: Provides the list of HTTP supported methods
    // Path: /operators[/{operatorId}]
    $app->options(
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS . '[' . $REGEX_OPERATOR_ID . ']',
        OperatorQueryController::class . ':options'
    )->setName('tdw_operators_options');

    // DELETE: Deletes a operator
    // Path: /operators/{operatorId}
    $app->delete(
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS . $REGEX_OPERATOR_ID,
        OperatorCommandController::class . ':delete'
    )->setName('tdw_operators_delete')
        ->add(JwtMiddleware::class);

    // POST: Creates a new PUBLICO operator
    // Path: /operators
    $app->post(
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS,
        OperatorCommandController::class . ':post'
    )->setName('tdw_operators_create')
        ->add(JwtMiddleware::class);

    // PUT: Updates a operator
    // Path: /operators/{operatorId}
    $app->put(
        $_ENV['RUTA_API'] . OperatorQueryController::PATH_OPERATORS . $REGEX_OPERATOR_ID,
        OperatorCommandController::class . ':put'
    )->setName('tdw_operators_update')
        ->add(JwtMiddleware::class);
};
