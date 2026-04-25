<?php

declare(strict_types=1);

namespace TDW\Test\IPanel\Model;

use DateTime;
use Faker\{ Factory, Generator };
use InvalidArgumentException;
use PHPUnit\Framework\Attributes as Tests;
use PHPUnit\Framework\TestCase;
use TDW\IPanel\Enum\{ EstadoOperacion, SentidoOperacion, TipoOperacion, TipoPunto };
use TDW\IPanel\Model\{Operacion, Operador, Punto};

#[Tests\Group('operacion')]
#[Tests\CoversClass(Operacion::class)]
class OperacionTest extends TestCase
{
    protected static Operacion $operacion;
    protected static Operador $operador;
    protected static Punto $punto;

    private static Generator $faker;

    public static function setUpBeforeClass(): void
    {
        self::$faker = Factory::create('es_ES');
    }

    public function testConstructor(): void
    {
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

        self::assertNotEmpty(self::$operacion->getId());
        self::assertSame($tipo, self::$operacion->getTipo());
        self::assertSame($codigo, self::$operacion->getCodigo());
        self::assertSame($sentido, self::$operacion->getSentido());
        self::assertSame($origen, self::$operacion->getOrigen());
        self::assertSame($destino, self::$operacion->getDestino());
        self::assertSame($estado, self::$operacion->getEstado());
        self::assertSame($horaProgramada, self::$operacion->getHoraProgramada());
        self::assertSame($horaEstimada, self::$operacion->getHoraEstimada());
        self::assertSame(self::$operador, self::$operacion->getOperador());
        self::assertSame(self::$punto, self::$operacion->getPunto());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetId(): void
    {
        self::assertNotEmpty(self::$operacion->getId());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetTipo(): void
    {
        self::$operacion->setTipo(TipoOperacion::TREN);
        self::assertSame(TipoOperacion::TREN, self::$operacion->getTipo());

        self::$operacion->setTipo('vuelo');
        self::assertSame(TipoOperacion::VUELO, self::$operacion->getTipo());
    }

    #[Tests\Depends('testConstructor')]
    public function testTipoExpectInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$operacion->setTipo(self::$faker->word());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetCodigo(): void
    {
        $codigo = self::$faker->text(20);
        self::$operacion->setCodigo($codigo);
        self::assertSame(substr($codigo, 0, 10), self::$operacion->getCodigo());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetSentido(): void
    {
        self::$operacion->setSentido(SentidoOperacion::LLEGADA);
        self::assertSame(SentidoOperacion::LLEGADA, self::$operacion->getSentido());

        self::$operacion->setSentido('salida');
        self::assertSame(SentidoOperacion::SALIDA, self::$operacion->getSentido());
    }

    #[Tests\Depends('testConstructor')]
    public function testSentidoExpectInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$operacion->setSentido(self::$faker->word());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetOrigen(): void
    {
        $origen = self::$faker->text(80);
        self::$operacion->setOrigen($origen);
        self::assertSame(substr($origen, 0, 60), self::$operacion->getOrigen());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetDestino(): void
    {
        $destino = self::$faker->text(80);
        self::$operacion->setDestino($destino);
        self::assertSame(substr($destino, 0, 60), self::$operacion->getDestino());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetHoraProgramada(): void
    {
        $horaProgramada = new DateTime('2026-02-01 11:00:00');
        self::$operacion->setHoraProgramada($horaProgramada);
        self::assertSame($horaProgramada, self::$operacion->getHoraProgramada());

        self::$operacion->setHoraProgramada(null);
        self::assertNull(self::$operacion->getHoraProgramada());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetHoraEstimada(): void
    {
        $horaEstimada = new DateTime('2026-02-01 11:30:00');
        self::$operacion->setHoraEstimada($horaEstimada);
        self::assertSame($horaEstimada, self::$operacion->getHoraEstimada());

        self::$operacion->setHoraEstimada(null);
        self::assertNull(self::$operacion->getHoraEstimada());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetEstado(): void
    {
        self::$operacion->setEstado(EstadoOperacion::PROGRAMADO);
        self::assertSame(EstadoOperacion::PROGRAMADO, self::$operacion->getEstado());

        self::$operacion->setEstado('programado');
        self::assertSame(EstadoOperacion::PROGRAMADO, self::$operacion->getEstado());
    }

    #[Tests\Depends('testConstructor')]
    public function testEstadoExpectInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::$operacion->setEstado(self::$faker->word());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetOperador(): void
    {
        $newOperador = new Operador(self::$faker->company(), self::$faker->text(6));
        self::$operacion->setOperador($newOperador);
        self::assertSame($newOperador, self::$operacion->getOperador());
    }

    #[Tests\Depends('testConstructor')]
    public function testGetSetPunto(): void
    {
        $newPunto = new Punto(TipoPunto::PUERTA, self::$faker->text(10));
        self::$operacion->setPunto($newPunto);
        self::assertSame($newPunto, self::$operacion->getPunto());
    }

    #[Tests\Depends('testConstructor')]
    public function test__toString(): void
    {
        /** @var non-empty-string $operationCode */
        $operationId = self::$operacion->getId();
        self::assertStringContainsString(
            $operationId,
            self::$operacion->__toString()
        );
    }

    #[Tests\Depends('testConstructor')]
    public function testJsonSerialize(): void
    {
        $jsonStr = (string) json_encode(self::$operacion, JSON_PARTIAL_OUTPUT_ON_ERROR);
        static::assertJson($jsonStr);
        $data = json_decode($jsonStr, true);
        static::assertArrayHasKey(
            'operacion',
            $data
        );
        static::assertArrayHasKey(
            'operacionId',
            $data['operacion']
        );
        static::assertArrayHasKey(
            'tipo',
            $data['operacion']
        );
        static::assertArrayHasKey(
            'codigo',
            $data['operacion']
        );
        static::assertArrayHasKey(
            'sentido',
            $data['operacion']
        );
        static::assertArrayHasKey(
            'origen',
            $data['operacion']
        );
        static::assertArrayHasKey(
            'destino',
            $data['operacion']
        );
    }
}
