<?php

/**
 * src/Utility/Utils.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Utility;

use Doctrine\ORM\{EntityManager, Tools\SchemaTool};
use Dotenv\Dotenv;
use TDW\IPanel\Enum\Role;
use TDW\IPanel\Model\{User};
use Throwable;

/**
 * Class Utils
 */
class Utils
{
    /**
     * Load the environment/configuration variables
     * defined in .env file + (.env.docker || .env.local)
     *
     * @param string $dir   project root directory
     */
    public static function loadEnv(string $dir): void
    {
        require_once $dir . '/vendor/autoload.php';

        if (!class_exists(Dotenv::class)) {
            fwrite(STDERR, 'ERROR: No se ha cargado la clase Dotenv' . PHP_EOL);
            exit(1);
        }

        try {
            // Load environment variables from .env file
            if (file_exists($dir . '/.env')) {
                $dotenv = Dotenv::createMutable($dir, '.env');
                $dotenv->load();
            } else {
                fwrite(STDERR, 'ERROR: no existe el fichero .env' . PHP_EOL);
                exit(1);
            }

            // Overload (if they exist) with .env.docker or .env.local
            if (isset($_SERVER['DOCKER']) && file_exists($dir . '/.env.docker')) {
                $dotenv = Dotenv::createMutable($dir, '.env.docker');
                $dotenv->load();
            } elseif (file_exists($dir . '/.env.local')) {
                $dotenv = Dotenv::createMutable($dir, '.env.local');
                $dotenv->load();
            }

            // Requiring Variables to be set
            $dotenv->required([ 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWD', 'SERVER_VERSION' ]);
            $dotenv->required([ 'ENTITY_DIR' ]);
        } catch (Throwable $e) {
            fwrite(
                STDERR,
                'EXCEPCIÓN: ' . $e->getCode() . ' - ' . $e->getMessage()
            );
            exit(1);
        }
    }

    /**
     * Drop & Update database schema
     *
     * @return void
     */
    public static function updateSchema(): void
    {
        try {
            /** @var EntityManager $e_manager */
            $e_manager = DoctrineConnector::getEntityManager();
            $e_manager->clear();
            $metadata = $e_manager->getMetadataFactory()->getAllMetadata();
            $sch_tool = new SchemaTool($e_manager);
            $sch_tool->dropDatabase();
            $sch_tool->updateSchema($metadata);
        } catch (Throwable $e) {
            fwrite(
                STDERR,
                'EXCEPCIÓN: ' . $e->getCode() . ' - ' . $e->getMessage()
            );
            exit(1);
        }
    }

    /**
     * Load user data fixtures
     *
     * @param string $email user email
     * @param string $password user password
     * @param bool $gestor isAdmin
     *
     * @return int user_id
     */
    public static function loadUserData(
        string $email,
        string $password,
        bool $gestor = false
    ): int {
        assert($email !== '');
        $user = new User(
            email: $email,
            password: $password,
            role: $gestor ? Role::GESTOR : Role::PUBLICO
        );
        try {
            $e_manager = DoctrineConnector::getEntityManager();
            $e_manager->persist($user);
            $e_manager->flush();
        } catch (Throwable $e) {
            fwrite(
                STDERR,
                'EXCEPCIÓN: ' . $e->getCode() . ' - ' . $e->getMessage()
            );
            exit(1);
        }

        return $user->getId();
    }
    
    public static function loadOperatorData(
        string $nombre,
        string $siglas,
        ?string $color = null,
        ?string $urlIcono = null
    ): int {
        $entityManager = DoctrineConnector::getEntityManager();
        $operator = new \TDW\IPanel\Model\Operador($nombre, $siglas, $color, $urlIcono);
        $entityManager->persist($operator);
        $entityManager->flush();
        return $operator->getId();
    }

    public static function loadSpotData(
        string $tipo,
        string $codigo
    ): int {
        $entityManager = DoctrineConnector::getEntityManager();
        $tipoPunto = \TDW\IPanel\Enum\TipoPunto::from(strtoupper($tipo));
        $spot = new \TDW\IPanel\Model\Punto($tipoPunto, $codigo);
        $entityManager->persist($spot);
        $entityManager->flush();
        return $spot->getId();
    }

    public static function loadOperationData(array $data): string
    {
        $entityManager = DoctrineConnector::getEntityManager();
        $operador = $entityManager->getRepository(\TDW\IPanel\Model\Operador::class)->find($data['operadorId']);
        $punto = $entityManager->getRepository(\TDW\IPanel\Model\Punto::class)->find($data['puntoId']);
        
        $operacion = new \TDW\IPanel\Model\Operacion(
            \TDW\IPanel\Enum\TipoOperacion::from($data['tipo']),
            $data['codigo'],
            \TDW\IPanel\Enum\SentidoOperacion::from($data['sentido']),
            $data['origen'],
            $data['destino'],
            $operador,
            $punto,
            \TDW\IPanel\Enum\EstadoOperacion::from($data['estado'] ?? 'programado'),
            isset($data['horaProgramada']) ? new \DateTime($data['horaProgramada']) : null,
            isset($data['horaEstimada']) ? new \DateTime($data['horaEstimada']) : null
        );
        
        $entityManager->persist($operacion);
        $entityManager->flush();
        return $operacion->getId();
    }

}
