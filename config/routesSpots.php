<?php

/**
 * config/routesSpots.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\IPanel\Controller\Spot\SpotCommandController;
use TDW\IPanel\Controller\Spot\SpotQueryController;
use TDW\IPanel\Middleware\JwtMiddleware;

/**
 * ############################################################
 * routes /api/v1/spots
 * ############################################################
 * @param App $app Slim4 application
 */
return function (App $app) {

    $REGEX_SPOT_ID = '/{spotId:[0-9]+}';
    $REGEX_NAME = '/{spotName:[\sa-zA-Z0-9()áéíóúÁÉÍÓÚñÑ%$@_\.+-]*}';
    $SEARCH_CRITERIA = '/{criterion:(?:name|acronym)}';

    // CGET|HEAD: Returns all spots
    // Path: /spots
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS,
        SpotQueryController::class . ':cget'
    )->setName('tdw_spots_cget');

    // GET|HEAD: Returns a spot based on a single ID
    // Path: /spots/{spotId}
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS . $REGEX_SPOT_ID,
        SpotQueryController::class . ':get'
    )->setName('tdw_spots_read');

    // GET: Returns status code 204 if spotname exists
    // Path: /spots/name/{name} || /spots/acronym/{name}
    $app->get(
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS . $SEARCH_CRITERIA . $REGEX_NAME,
        SpotQueryController::class . ':getElementByName'
    )->setName('tdw_spots_get_spotname');

    // OPTIONS: Provides the list of HTTP supported methods
    // Path: /spots[/{spotId}]
    $app->options(
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS . '[' . $REGEX_SPOT_ID . ']',
        SpotQueryController::class . ':options'
    )->setName('tdw_spots_options');

    // DELETE: Deletes a spot
    // Path: /spots/{spotId}
    $app->delete(
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS . $REGEX_SPOT_ID,
        SpotCommandController::class . ':delete'
    )->setName('tdw_spots_delete')
        ->add(JwtMiddleware::class);

    // POST: Creates a new PUBLICO spot
    // Path: /spots
    $app->post(
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS,
        SpotCommandController::class . ':post'
    )->setName('tdw_spots_create')
        ->add(JwtMiddleware::class);

    // PUT: Updates a spot
    // Path: /spots/{spotId}
    $app->put(
        $_ENV['RUTA_API'] . SpotQueryController::PATH_SPOTS . $REGEX_SPOT_ID,
        SpotCommandController::class . ':put'
    )->setName('tdw_spots_update')
        ->add(JwtMiddleware::class);
};
