<?php

namespace TDW\Test\IPanel\Model;

use DateTime;
use Faker\{ Factory, Generator };
use PHPUnit\Framework\Attributes as Tests;
use PHPUnit\Framework\TestCase;
use TDW\IPanel\Enum\EstadoOperacion;
use TDW\IPanel\Enum\SentidoOperacion;
use TDW\IPanel\Enum\TipoOperacion;
use TDW\IPanel\Enum\TipoPunto;
use TDW\IPanel\Model\Operacion;
use TDW\IPanel\Model\Operador;
use TDW\IPanel\Model\Punto;

#[Tests\Group('operador')]
#[Tests\CoversClass(Operador::class)]
class OperadorTest extends TestCase
{
    protected static Operador $operador;
    protected static Punto $punto;
    protected static Operacion $operacion;
    private static Generator $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$faker = Factory::create('es_ES');
        $tipo = TipoOperacion::VUELO;
        $codigo = self::$faker->text(10);
        $sentido = SentidoOperacion::SALIDA;
        $origen = self::$faker->city();
        $destino = self::$faker->city();
        $estado = EstadoOperacion::PROGRAMADO;
        $horaProgramada = new DateTime('2026-01-01 10:00:00');
        $horaEstimada = new DateTime('2026-01-01 10:30:00');

        self::$operador = new Operador(self::$faker->company(), self::$faker->text(6));
        self::$punto = new Punto(TipoPunto::VIA, self::$faker->text(10));
        self::$operacion = new Operacion(
            $tipo,
            $codigo,
            $sentido,
            $origen,
            $destino,
            self::$operador,
            self::$punto,
            $estado,
            $horaProgramada,
            $horaEstimada
        );
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $cia = self::$faker->text(80);
        $siglas = self::$faker->text(6);
        self::assertNotEmpty($cia);
        self::assertNotEmpty($siglas);
        static::$operador = new Operador($cia, $siglas);
        self::assertSame(0, self::$operador->getId());
        self::assertSame(
            $cia,
            self::$operador->nombre
        );
        self::assertSame(
          strtoupper($siglas),
          self::$operador->siglas
        );
    }

    #[Tests\Depends('testConstructor')]
    public function testGetId(): void
    {
        self::assertSame(0, self::$operador->getId());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetNombre(): void
    {
        /** @var non-empty-string $operadorname */
        $operadorname = self::$faker->name();
        self::assertNotEmpty($operadorname);
        self::$operador->nombre = $operadorname;
        static::assertSame(
            $operadorname,
            self::$operador->nombre
        );
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetSiglas(): void
    {
        $missiglas = self::$faker->text(6);
        self::$operador->siglas = $missiglas;
        static::assertSame(
            strtoupper($missiglas),
            self::$operador->siglas
        );
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetUrlIcono(): void
    {
        $imageUrl = self::$faker->url();
        self::$operador->urlIcono = $imageUrl;
        static::assertSame(
            $imageUrl,
            self::$operador->urlIcono
        );
    }

    #[Tests\Depends('testConstructor')]
    public function test__toString(): void
    {
        /** @var non-empty-string $operatorName */
        $operatorName = self::$faker->company();
        self::$operador->nombre = $operatorName;
        self::assertStringContainsString(
            $operatorName,
            self::$operador->__toString()
        );
    }

    public function testOperaciones(): void
    {
        self::$operador->addOperacion(self::$operacion);
        self::assertCount(1, self::$operador->getOperaciones());
        self::$operador->removeOperacion(self::$operacion);
        self::assertCount(0, self::$operador->getOperaciones());
    }

    #[Tests\Depends('testConstructor')]
    public function testJsonSerialize(): void
    {
        $jsonStr = (string) json_encode(self::$operador, JSON_PARTIAL_OUTPUT_ON_ERROR);
        static::assertJson($jsonStr);
        $data = json_decode($jsonStr, true);
        static::assertArrayHasKey(
            'operador',
            $data
        );
        static::assertArrayHasKey(
            'id',
            $data['operador']
        );
        static::assertArrayHasKey(
            'nombre',
            $data['operador']
        );
        static::assertArrayHasKey(
            'siglas',
            $data['operador']
        );
        static::assertArrayHasKey(
            'color',
            $data['operador']
        );
        static::assertArrayHasKey(
            'urlIcono',
            $data['operador']
        );
        static::assertArrayHasKey(
            'operaciones',
            $data['operador']
        );
    }
}
