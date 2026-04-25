<?php

declare(strict_types=1);

/**
 * src/Model/Operador.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use PhpStaticAnalysis\Attributes\Type;
use ReflectionObject;

/**
 * Class Operador
 */
#[ORM\Entity, ORM\Table(name: 'operadores')]
#[ORM\UniqueConstraint(name: 'Operador_nombre_uindex', columns: [ 'nombre' ])]
#[ORM\UniqueConstraint(name: 'Operador_siglas_uindex', columns: [ 'siglas' ])]
class Operador implements \JsonSerializable, \Stringable
{
    #[ORM\Id,
    ORM\Column(
        name: 'id',
        type: 'integer',
        nullable: false
    ),
    ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected int $id;

    #[ORM\Column(
        type: 'string',
        length: 80,
        unique: true,
        nullable: false
    )]
    /** @phpstan-type non-empty-string */
    public string $nombre {
        get => $this->nombre;
        set {
            if (strlen($value) <= 80) { // 80 caracteres max
                $this->nombre = $value;
            }
        }
    }

    #[ORM\Column(
        type: 'string',
        length: 6,
        unique: true,
        nullable: false
    )]
    /** @phpstan-type non-empty-string */
    public string $siglas {
        get => $this->siglas;
        set {
            if (strlen($value) <= 6) { // 6 caracteres max
                $this->siglas = strtoupper($value);
            }
        }
    }

    #[ORM\Column(
        type: 'string',
        length: 20,
        unique: false,
        nullable: true
    )]
    public string | null $color {
        get => $this->color;
        set { // 20 caracteres max
            if (is_null($value) || strlen($value) <= 20) {
                $value = is_null($value) ? null : strtolower($value);
                $this->color = $value;
            }
        }
    }

    #[ORM\Column(
        name: 'url_icono',
        type: 'string',
        length: 2047,
        nullable: true
    )]
    public string | null $urlIcono = null {
        get => $this->urlIcono;
        set { // 2047 caracteres max
            if (is_null($value) || strlen($value) <= 2047) {
                $this->urlIcono = $value;
            }
        }
    }

    #[Type('Collection<Operacion>')]
    #[ORM\OneToMany(targetEntity: Operacion::class, mappedBy: 'operadorId')]
    public Collection $operaciones;

    /**
     * Operador's Constructor
     *
     * @param non-empty-string $name 80 caracteres max
     * @param string $siglas 6 caracteres max
     * @param string|null $color 20 caracteres max
     * @param string|null $urlIcono 2047 caracteres max
     */
    public function __construct(
        string  $name,
        string  $siglas,
        ?string $color = null,
        ?string $urlIcono = null,
    ) {
        assert($name !== '');
        $this->id = 0;
        $this->nombre = $name;
        $this->siglas = $siglas;
        $this->color = $color;
        $this->urlIcono = $urlIcono;
        $this->operaciones = new ArrayCollection();
    }

    /**
     * Gets the operator's ID
     *
     * @return int<0, max>
     */
    public function getId(): int
    {
        return $this->id;
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
        return $this->getOperaciones()->contains($operacion);
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
            '[%s: (id=%04d, nombre="%s", siglas="%6s", color="%20s", imageUrl="%s", operaciones=%s)]',
            $reflection->getShortName(),
            $this->getId(),
            $this->nombre,
            $this->siglas,
            $this->color,
            $this->urlIcono,
            $this->operaciones->__toString() ?? '[]',
        );
    }

    /** @see \JsonSerializable */
    #[ArrayShape([
        'operador' => [
            'id' => 'int',
            'nombre' => 'string',
            'siglas' => 'string',
            'color' => 'string|null',
            'urlIcono' => 'string|null',
            'operaciones' => 'array',
        ]
    ])]
    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionObject($this);
        return [
            strtolower($reflection->getShortName()) => [
                'id'        => $this->getId(),
                'nombre'    => $this->nombre,
                'siglas'    => $this->siglas,
                'color'     => $this->color,
                'urlIcono'  => $this->urlIcono,
                'operaciones' => $this->getCodes() ?? [],
            ]
        ];
    }
}
