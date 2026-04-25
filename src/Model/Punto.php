<?php

declare(strict_types=1);

/**
 * src/Model/Operador.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Model;

use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use PhpStaticAnalysis\Attributes\Type;
use ReflectionObject;
use TDW\IPanel\Enum\TipoPunto;
use ValueError;

/**
 * Class Punto
 */
#[ORM\Entity, ORM\Table(name: 'puntos')]
#[ORM\UniqueConstraint(name: 'Punto_codigo_uindex', columns: [ 'codigo' ])]
class Punto implements \JsonSerializable, \Stringable
{
    #[ORM\Id,
    ORM\Column(
        name: 'id',
        type: 'integer',
        nullable: false
    ),
    ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $puntoId;

    #[ORM\Column(
        type: 'enum',
        length: 6,
        enumType: TipoPunto::class,
    )]
    protected TipoPunto $tipo;

    #[ORM\Column(
        type: 'string',
        length: 10,
        unique: true,
        nullable: false
    )]
    /** @phpstan-type non-empty-string */
    protected string $codigo;

    #[Type('Collection<Operacion>')]
    #[ORM\OneToMany(targetEntity: Operacion::class, mappedBy: 'puntoId')]
    public Collection $operaciones;

    /**
     * Punto's Constructor
     *
     * @param TipoPunto|string $tipoPunto
     * @param non-empty-string $codigo 10 caracteres max
     */
    public function __construct(
        TipoPunto|string $tipoPunto,
        string $codigo
    ) {
        assert($codigo !== '');
        $this->puntoId = 0;
        $this->setTipo($tipoPunto);
        $this->setCodigo($codigo);
        $this->operaciones = new ArrayCollection();
    }

    /**
     * Gets the punto's ID
     * @return int<0, max>
     */
    public function getId(): int
    {
        return $this->puntoId;
    }

    /**
     * Gets the punto's tipo
     * @return TipoPunto
     */
    public function getTipo(): TipoPunto
    {
        return $this->tipo;
    }

    /**
     * Sets the punto's tipo
     * @param TipoPunto|string $tipo
     * @return void
     * @throws InvalidArgumentException if the tipo is invalid
     */
    public function setTipo(TipoPunto|string $tipo): void
    {
        try {
            $this->tipo = ($tipo instanceof TipoPunto)
                ? $tipo
                : TipoPunto::from(strtoupper($tipo));
        } catch (ValueError) {
            throw new InvalidArgumentException('Invalid TipoPunto');
        }
    }

    /**
     * Gets the punto's codigo
     * @return non-empty-string
     */
    public function getCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Sets the punto's codigo
     * @param non-empty-string $codigo (10 characters max)
     * @return void
     */
    public function setCodigo(string $codigo): void
    {
        assert($codigo !== '');
        if (strlen($codigo) <= 10) {
            $this->codigo = $codigo;
        }
    }

    /**
     * Gets the Operaciones who are part of the entity
     *
     * @return Collection<Operacion>
     */
    public function getOperaciones(): Collection
    {
        return $this->operaciones;
    }

    /**
     * Determines if a Operacion is part of the entity
     *
     * @param Operacion $operacion
     * @return bool
     */
    public function containsOperacion(Operacion $operacion): bool
    {
        return $this->operaciones->contains($operacion);
    }

    /**
     * Add a Operacion to the entity
     *
     * @param Operacion $operacion
     * @return void
     */
    public function addOperacion(Operacion $operacion): void
    {
        if ($this->containsOperacion($operacion)) {
            return;
        }

        $this->operaciones->add($operacion);
    }

    /**
     * Remove a Operacion from the entity
     *
     * @param Operacion $operacion
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOperacion(Operacion $operacion): bool
    {
        return $this->operaciones->removeElement($operacion);
    }

    /**
     * Obtains an sorted array with Operacion Ulids
     * @return string[] sorted Ulids in collection
     */
    #[ArrayShape([ "int" => "string" ])]
    final public function getCodes(): array
    {
        $arrayIds = array_map(
            fn(Operacion $element) => $element->getId(),
            $this->operaciones->getValues()
        );
        sort($arrayIds);
        return $arrayIds;
    }

    /** @see \Stringable */
    public function __toString(): string
    {
        $reflection = new \ReflectionObject($this);
        return sprintf(
            '[%s: (puntoId=%04d, tipo="%6s", codigo="%10s", operaciones=%s)',
            $reflection->getShortName(),
            $this->getId(),
            $this->getTipo()->name,
            $this->getCodigo(),
            $this->getOperaciones() ?? [],
        );
    }

    /** @see \JsonSerializable */
    #[ArrayShape([
        'operador' => [
            'puntoId' => 'int',
            'tipo' => 'string',
            'codigo' => 'string',
            'operaciones' => 'array',
        ]
    ])]
    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionObject($this);
        return [
            strtolower($reflection->getShortName()) => [
                'puntoId'   => $this->getId(),
                'tipo'      => $this->getTipo()->name,
                'codigo'    => $this->getCodigo(),
                'operaciones' => $this->getCodes() ?? [],
            ]
        ];
    }
}
