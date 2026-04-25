<?php

/**
 * config/routes.php - Define app routes
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

use Slim\App;
use TDW\IPanel\Controller\Login\{ LoginController, OptionsQuery };

return function (App $app) {

    // Redirection / -> /api-docs/index.html
    $app->redirect(
        '/',
        '/api-docs/index.html'
    )->setName('tdw_home_redirect');

    /**
     * ############################################################
     * routes /access_token
     * OPTIONS /access_token
     * POST /access_token
     * ############################################################
     */
    $app->options(
        $_ENV['RUTA_LOGIN'],
        OptionsQuery::class
    )->setName('api_options_login');

    $app->post(
        $_ENV['RUTA_LOGIN'],
        LoginController::class
    )->setName('api_post_login');
};
