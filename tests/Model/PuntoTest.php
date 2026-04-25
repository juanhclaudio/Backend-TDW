<?php

namespace TDW\Test\IPanel\Model;

use DateTime;
use Faker\{ Factory, Generator };
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TDW\IPanel\Enum\EstadoOperacion;
use TDW\IPanel\Enum\SentidoOperacion;
use TDW\IPanel\Enum\TipoOperacion;
use TDW\IPanel\Enum\TipoPunto;
use TDW\IPanel\Model\Operacion;
use TDW\IPanel\Model\Operador;
use TDW\IPanel\Model\Punto;
use function PHPUnit\Framework\assertSame;

class PuntoTest extends TestCase
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
        $tipoPunto = TipoPunto::ALL_VALUES[self::$faker->numberBetween(0, 1)];
        $codigo = self::$faker->text(10);
        self::assertNotEmpty($codigo);
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
        self::$punto = new Punto($tipoPunto, $codigo);
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

    public function test__construct(): void
    {
        $tipo = TipoPunto::ALL_VALUES[self::$faker->numberBetween(0, 1)];
        $codigo = self::$faker->text(10);
        self::assertNotEmpty($codigo);
        self::$punto = new Punto($tipo, $codigo);
        static::assertSame($tipo, self::$punto->getTipo()->value);
        static::assertSame($codigo, self::$punto->getCodigo());
    }

    public function testGetId(): void
    {
        assertSame(0, self::$punto->getId());
    }

    public function testGetSetTipo(): void
    {
        $tipo = TipoPunto::VIA;
        static::$punto->setTipo($tipo);
        static::assertSame($tipo, self::$punto->getTipo());
        $tipo = TipoPunto::PUERTA;
        static::$punto->setTipo($tipo);
        static::assertSame($tipo, self::$punto->getTipo());
    }

    public function testTipoExpectInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$punto->setTipo(self::$faker->word());
    }

    public function testGetSetCodigo(): void
    {
        $codigo = self::$faker->text(10);
        self::assertNotEmpty($codigo);
        static::$punto->setCodigo($codigo);
        static::assertSame($codigo, self::$punto->getCodigo());
    }

    public function testOperaciones(): void
    {
        self::$punto->addOperacion(self::$operacion);
        self::assertSame(1, count(self::$punto->getOperaciones()));
        self::$punto->removeOperacion(self::$operacion);
        self::assertSame(0, count(self::$punto->getOperaciones()));
    }


    public function test__toString(): void
    {
        $tipo = TipoPunto::ALL_VALUES[self::$faker->numberBetween(0, 1)];
        $codigo = self::$faker->text(10);
        self::assertNotEmpty($codigo);
        self::$punto->setTipo($tipo);
        self::$punto->setCodigo($codigo);
        self::assertStringContainsString(
            $tipo,
            self::$punto->__toString()
        );
        self::assertStringContainsString(
            $codigo,
            self::$punto->__toString()
        );
    }

    public function testJsonSerialize(): void
    {
        $tipo = TipoPunto::ALL_VALUES[self::$faker->numberBetween(0, 1)];
        $codigo = self::$faker->text(10);
        self::assertNotEmpty($codigo);
        self::$punto->setTipo($tipo);
        self::$punto->setCodigo($codigo);
        $puntoJson = json_encode(self::$punto, JSON_PARTIAL_OUTPUT_ON_ERROR);
        self::assertNotEmpty($puntoJson);
        self::assertJson($puntoJson);
        self::assertStringContainsString(
            $tipo,
            $puntoJson
        );
        self::assertStringContainsString(
            $codigo,
            $puntoJson
        );
        static::assertStringContainsString(
            'operaciones',
            $puntoJson
        );
    }
}
