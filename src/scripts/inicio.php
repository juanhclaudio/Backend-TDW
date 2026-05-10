<?php

/**
 * src/scripts/inicio.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

$projectRootDir = dirname(__DIR__, 2);
require_once $projectRootDir . '/vendor/autoload.php';

use TDW\IPanel\Utility\Utils;

Utils::loadEnv($projectRootDir);
echo ">> Reinstalando base de datos..." . PHP_EOL;
Utils::updateSchema();
echo ">> Esquema actualizado." . PHP_EOL;

$adminId = Utils::loadUserData($_ENV['ADMIN_USER_EMAIL'], $_ENV['ADMIN_USER_PASSWD'], true);
echo ">> Admin creado (ID: $adminId)." . PHP_EOL;

$publicoId = Utils::loadUserData("publico@example.com", "publico123", false);
echo ">> Usuario público creado (ID: $publicoId)." . PHP_EOL;

$operadorId = Utils::loadOperatorData('Iberia', 'IB', '#ff0000', 'https://vuelos.com/logo.png');
echo ">> Operador creado (ID: $operadorId)." . PHP_EOL;

$puntoId = Utils::loadSpotData('PUERTA', 'P20');
echo ">> Punto creado (ID: $puntoId)." . PHP_EOL;

$opData = [
    'tipo' => 'vuelo',
    'codigo' => 'IB1234',
    'sentido' => 'salida',
    'origen' => 'Madrid',
    'destino' => 'Tokio',
    'estado' => 'programado',
    'operadorId' => $operadorId,
    'puntoId' => $puntoId,
    'horaProgramada' => date('c'),
    'horaEstimada' => date('c', strtotime('+5 hours')),
];
$opId = Utils::loadOperationData($opData);
echo ">> Operación creada (ID: $opId)." . PHP_EOL;

echo ">> Proceso de carga inicial finalizado con éxito." . PHP_EOL;