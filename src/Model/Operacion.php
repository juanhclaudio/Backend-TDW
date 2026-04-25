<?php

declare(strict_types=1);

/**
 * src/Model/Operacion.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use ReflectionObject;
use Symfony\Component\Uid\Ulid;
use TDW\IPanel\Enum\{ EstadoOperacion, SentidoOperacion, TipoOperacion };
use ValueError;

#[ORM\Entity, ORM\Table(name: 'operaciones')]
class Operacion implements \Stringable, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26, unique: true)]
    protected string $operacionId;

    #[ORM\Column(type: 'enum', nullable: false, enumType: TipoOperacion::class)]
    protected TipoOperacion $tipo;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    protected string $codigo;   // 10 characters max

    #[ORM\Column(type: 'enum', nullable: false, enumType: SentidoOperacion::class)]
    protected SentidoOperacion $sentido;

    #[ORM\Column(type: 'string', length: 60, nullable: false)]
    protected string $origen;   // 60 characters max

    #[ORM\Column(type: 'string', length: 60, nullable: false)]
    protected string $destino;  // 60 characters max

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected DateTime|null $horaProgramada;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected DateTime|null $horaEstimada;

    #[ORM\Column(type: 'enum', nullable: false, enumType: EstadoOperacion::class)]
    protected EstadoOperacion $estado;

    #[ORM\ManyToOne(targetEntity: Operador::class, inversedBy: 'operaciones')]
    #[ORM\JoinColumn(name: 'operador_id', referencedColumnName: 'id')]
    protected Operador $operadorId;

    #[ORM\ManyToOne(targetEntity: Punto::class, inversedBy: 'operaciones')]
    #[ORM\JoinColumn(name: 'punto_id', referencedColumnName: 'id')]
    protected Punto $puntoId;

    /**
     * @param TipoOperacion $tipo
     * @param string $codigo
     * @param SentidoOperacion $sentido
     * @param string $origen
     * @param string $destino
     * @param Operador $operador
     * @param Punto $punto
     * @param EstadoOperacion $estado defaults to EstadoOperacion::PROGRAMADO
     * @param DateTime|null $horaProgramada defaults to null
     * @param DateTime|null $horaEstimada defaults to null
     */
    public function __construct(TipoOperacion $tipo, string $codigo, SentidoOperacion $sentido, string $origen, string $destino, Operador $operador, Punto $punto, EstadoOperacion $estado = EstadoOperacion::PROGRAMADO, ?DateTime $horaProgramada = null, ?DateTime $horaEstimada = null)
    {
        $this->operacionId = new Ulid()->toBase32();
        $this->setTipo($tipo);
        $this->setCodigo($codigo);
        $this->setSentido($sentido);
        $this->setOrigen($origen);
        $this->setDestino($destino);
        $this->setHoraProgramada($horaProgramada);
        $this->setHoraEstimada($horaEstimada);
        $this->setEstado($estado);
        $this->operadorId = $operador;
        $this->operadorId->addOperacion($this);
        $this->puntoId = $punto;
        $this->puntoId->addOperacion($this);
    }

    /** 
     * Returns the operacion's ID' 
     */
    public function getId(): string
    {
        return $this->operacionId;
    }

    /** 
     * Returns the operacion's tipo' 
     */
    public function getTipo(): TipoOperacion
    {
        return $this->tipo;
    }

    /**
     * Assign the new `tipo` to the Operacion
     *
     * @param TipoOperacion|string $newTipo [ TipoOperacion::VUELO | TipoOperacion::TREN | 'vuelo' | 'tren' ]
     * @return void
     * @throws InvalidArgumentException if the newTipo is invalid
     */
    public function setTipo(TipoOperacion|string $newTipo): void
    {
        try {
            $this->tipo = ($newTipo instanceof TipoOperacion)
                ? $newTipo
                : TipoOperacion::from(strtolower($newTipo));
        } catch (ValueError) {
            throw new InvalidArgumentException('Invalid Role');
        }
    }

    /**
     * Returns the operacion's codigo' (max 10 characters)
     */
    public function getCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Sets the operacion's codigo' (max 10 characters)
     *
     * @param string $codigo Use only the first 10 characters
     * @return void
     */
    public function setCodigo(string $codigo): void
    {
        $this->codigo = substr($codigo, 0, 10);
    }

    /** 
     * Returns the operacion's sentido' 
     */
    public function getSentido(): SentidoOperacion
    {
        return $this->sentido;
    }

    /**
     * Assign the new `sentido` to the Operacion
     *
     * @param SentidoOperacion|string $newSentido [ SentidoOperacion::SALIDA | SentidoOperacion::LLEGADA | 'salida' | 'llegada' ]
     * @return void
     * @throws InvalidArgumentException if the newSentido is invalid
     */
    public function setSentido(SentidoOperacion|string $newSentido): void
    {
        try {
            $this->sentido = ($newSentido instanceof SentidoOperacion)
                ? $newSentido
                : SentidoOperacion::from(strtolower($newSentido));
        } catch (ValueError) {
            throw new InvalidArgumentException('Invalid SentidoOperacion');
        }
    }

    /**
     * Returns the operacion's origen' (max 60 characters)
     */
    public function getOrigen(): string
    {
        return $this->origen;
    }

    /**
     * Sets the operacion's origen' (max 60 characters)
     *
     * @param string $origen Use only the first 60 characters
     * @return void
     */
    public function setOrigen(string $origen): void
    {
        $this->origen = substr($origen, 0, 60);
    }

    /**
     * Returns the operacion's destino' (max 60 characters)
     */
    public function getDestino(): string
    {
        return $this->destino;
    }

    /**
     * Sets the operacion's destino' (max 60 characters)
     *
     * @param string $destino Use only the first 60 characters
     * @return void
     */
    public function setDestino(string $destino): void
    {
        $this->destino = substr($destino, 0, 60);
    }

    /**
     * Returns the operacion's horaProgramada
     */
    public function getHoraProgramada(): ?DateTime
    {
        return $this->horaProgramada;
    }

    /**
     * Sets the operacion's horaProgramada
     *
     * @param DateTime|null $horaProgramada
     * @return void
     */
    public function setHoraProgramada(?DateTime $horaProgramada): void
    {
        $this->horaProgramada = $horaProgramada;
    }

    /**
     * Returns the operacion's horaEstimada
     */
    public function getHoraEstimada(): ?DateTime
    {
        return $this->horaEstimada;
    }

    /**
     * Sets the operacion's horaEstimada
     *
     * @param DateTime|null $horaEstimada
     * @return void
     */
    public function setHoraEstimada(?DateTime $horaEstimada): void
    {
        $this->horaEstimada = $horaEstimada;
    }

    /**
     * Returns the operacion's estado
     */
    public function getEstado(): EstadoOperacion
    {
        return $this->estado;
    }

    /**
     * Assign the new `estado` to the Operacion
     *
     * @param EstadoOperacion|string $newEstado [ EstadoOperacion::PROGRAMADO | EstadoOperacion::EJECUTADO | ... ]
     * @return void
     * @throws InvalidArgumentException if the newEstado is invalid
     */
    public function setEstado(EstadoOperacion|string $newEstado): void
    {
        try {
            $this->estado = ($newEstado instanceof EstadoOperacion)
                ? $newEstado
                : EstadoOperacion::from(strtolower($newEstado));
        } catch (ValueError) {
            throw new InvalidArgumentException('Invalid EstadoOperacion');
        }
    }

    /**
     * Returns the operacion's operadorId
     */
    public function getOperador(): Operador
    {
        return $this->operadorId;
    }

    /**
     * Sets the operacion's operadorId
     *
     * @param Operador $operadorId
     * @return void
     */
    public function setOperador(Operador $operadorId): void
    {
        $this->operadorId->removeOperacion($this);
        $this->operadorId = $operadorId;
        $operadorId->addOperacion($this);
    }

    /**
     * Returns the operacion's puntoId
     */
    public function getPunto(): Punto
    {
        return $this->puntoId;
    }

    /**
     * Sets the operacion's puntoId
     *
     * @param Punto $puntoId
     * @return void
     */
    public function setPunto(Punto $puntoId): void
    {
        // eliminar punto anterior
        $this->puntoId->removeOperacion($this);
        $this->puntoId = $puntoId;
        $puntoId->addOperacion($this);
    }

    /** @see \Stringable */
    public function __toString(): string
    {
        return sprintf(
            'operacionId:%26s tipo:%s codigo:%10s sentido:%s origen:%s destino:%s, horaProgramada:%s, horaEstimada:%s, estado:%s, operador:%s, punto:%s',
            $this->getId(),
            $this->getTipo()->value,
            $this->getCodigo(),
            $this->getSentido()->value,
            $this->getOrigen(),
            $this->getDestino(),
            $this->horaProgramada?->format('Y-m-d H:i:s'),
            $this->horaEstimada?->format('Y-m-d H:i:s'),
            $this->getEstado()->value,
            $this->getOperador()->__toString(),
            $this->getPunto()->__toString(),
        );
    }

    /** @see \JsonSerializable */
    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionObject($this);
        return [
            strtolower($reflection->getShortName()) => [
            'operacionId' => $this->operacionId,
            'tipo' => $this->tipo->value,
            'codigo' => $this->codigo,
            'sentido' => $this->sentido->value,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'horaProgramada' => $this->horaProgramada?->format('Y-m-d H:i:s'),
            'horaEstimada' => $this->horaEstimada?->format('Y-m-d H:i:s'),
            'estado' => $this->estado->value,
            'operador' => $this->operadorId->jsonSerialize(),
            'punto' => $this->puntoId->jsonSerialize(),
        ]];
    }
}