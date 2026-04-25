<?php

/**
 * config/routesUsers.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\IPanel\Controller\User\{ CreateCommand, DeleteCommand, OptionsQuery, ReadAllQuery };
use TDW\IPanel\Controller\User\{ ReadQuery, ReadUseremailQuery, UpdateCommand };
use TDW\IPanel\Middleware\JwtMiddleware;

/**
 * ############################################################
 * routes /api/v1/users
 * ############################################################
 * @param App $app
 */
return function (App $app) {

    $REGEX_USER_ID = '/{userId:[0-9]+}';
    $REGEX_EMAIL = '/{email:[\sa-zA-Z0-9()áéíóúÁÉÍÓÚñÑ%$@_\.+-]*}';

    // CGET|HEAD: Returns all users
    // Path: /users
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . ReadAllQuery::PATH_USERS,
        ReadAllQuery::class
    )->setName('tdw_users_cget')
        ->add(JwtMiddleware::class);

    // GET|HEAD: Returns a user based on a single ID
    // Path: /users/{userId}
    $app->map(
        [ 'GET', 'HEAD' ],
        $_ENV['RUTA_API'] . ReadAllQuery::PATH_USERS . $REGEX_USER_ID,
        ReadQuery::class
    )->setName('tdw_users_read')
        ->add(JwtMiddleware::class);

    // GET: Returns status code 204 if username exists
    // Path: /users/email/{email}
    $app->get(
        $_ENV['RUTA_API'] . ReadAllQuery::PATH_USERS . '/email' . $REGEX_EMAIL,
        ReadUseremailQuery::class
    )->setName('tdw_users_get_useremail');

    // OPTIONS: Provides the list of HTTP supported methods
    // Path: /users[/{userId}]
    $app->options(
        $_ENV['RUTA_API'] . ReadAllQuery::PATH_USERS . '[' . $REGEX_USER_ID . ']',
        OptionsQuery::class
    )->setName('tdw_users_options');

    // DELETE: Deletes a user
    // Path: /users/{userId}
    $app->delete(
        $_ENV['RUTA_API'] . UpdateCommand::PATH_USERS . $REGEX_USER_ID,
        DeleteCommand::class
    )->setName('tdw_users_delete')
        ->add(JwtMiddleware::class);

    // POST: Creates a new PUBLICO user
    // Path: /users
    $app->post(
        $_ENV['RUTA_API'] . UpdateCommand::PATH_USERS,
        CreateCommand::class
    )->setName('tdw_users_create');

    // PUT: Updates a user
    // Path: /users/{userId}
    $app->put(
        $_ENV['RUTA_API'] . UpdateCommand::PATH_USERS . $REGEX_USER_ID,
        UpdateCommand::class
    )->setName('tdw_users_update')
        ->add(JwtMiddleware::class);
};
