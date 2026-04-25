<?php

declare(strict_types=1);

/**
 * src/Model/User.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\IPanel\Model;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use ReflectionObject;
use Stringable;
use TDW\IPanel\Enum\Role;
use ValueError;

#[ORM\Entity, ORM\Table(name: 'users')]
class User implements JsonSerializable, Stringable
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
        name: 'email',
        type: 'string',
        length: 60,
        unique: true,
        nullable: false
    )]
    protected string $email;

    #[ORM\Column(
        name: 'password',
        type: 'string',
        length: 255,
        nullable: false
    )]
    protected string $password_hash;

    #[ORM\Column(
        name: 'role',
        type: 'enum',
        length: 10,
        nullable: false,
        enumType: Role::class,
        options: [ 'default' => Role::PUBLICO ]
    )]
    protected Role $role;

    /**
     * User constructor.
     *
     * @param non-empty-string $email user email
     * @param string $password user password
     * @param Role|string $role Role::*
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $email = '<empty>',
        string $password = '',
        Role|string $role = Role::PUBLICO
    ) {
        assert($email !== '', InvalidArgumentException::class);
        $this->id       = 0;
        $this->email    = $email;
        $this->setPassword($password);
        $this->setRole($role);
    }

    /**
     * Gets the user ID
     *
     * @return int User id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get user e-mail
     *
     * @return string User email
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set user e-mail
     *
     * @param string $email email
     * @return void
     * @throws InvalidArgumentException if the email is empty
     */
    public function setEmail(string $email): void
    {
        assert($email !== '', InvalidArgumentException::class);
        $this->email = $email;
    }

    /**
     * Get the hashed password
     *
     * @return string hashed password
     */
    public function getPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Set the user's password
     *
     * @param string $password password
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies that the given hash matches the user password.
     *
     * @param string $password user password
     * @return boolean Returns TRUE if the password and hash match, or FALSE otherwise.
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->getPassword());
    }

    /**
     * Determines whether the user has a certain role
     *
     * @param Role|string $role [ Role::PUBLICO | Role::GESTOR | Role::INACTIVO | 'publico' | 'gestor' | 'inactivo' ]
     * @return bool Returns TRUE if the user has the role, or FALSE otherwise.
     */
    public function hasRole(Role|string $role): bool
    {
        if (!$role instanceof Role) {
            $role = Role::tryFrom($role);
        }
        return match ($role) {
            Role::INACTIVO => $this->role->is(Role::INACTIVO),
            Role::PUBLICO => !$this->role->is(Role::INACTIVO),
            Role::GESTOR => $this->role->is(Role::GESTOR),
            default => false
        };
    }

    /**
     * Assign the role to the user
     *
     * @param Role|string $newRole [ Role::PUBLICO | Role::GESTOR | Role::INACTIVO | 'publico' | 'gestor' | 'inactivo' ]
     * @return void
     * @throws InvalidArgumentException if the role is invalid
     */
    public function setRole(Role|string $newRole): void
    {
        try {
            $this->role = ($newRole instanceof Role)
                ? $newRole
                : Role::from(strtolower($newRole));
        } catch (ValueError) {
            throw new InvalidArgumentException('Invalid Role');
        }
    }

    /**
     * Returns an array with the user's roles
     *
     * @return Role[] [ INACTIVE] | [ READER ] | [ READER , WRITER ]
     */
    public function getRoles(): array
    {
        $roles = array_filter(
            Role::cases(),
            fn($myRole) => $this->hasRole($myRole)
        );
        return $roles;
    }

    /** @see Stringable */
    public function __toString(): string
    {
        $reflection = new ReflectionObject($this);
        return
            sprintf(
                '[%s: (id=%04d, email="%s", role="%s")]',
                $reflection->getShortName(),
                $this->getId(),
                $this->getEmail(),
                $this->role->name,
            );
    }

    /**
     * @see JsonSerializable
     */
    #[ArrayShape(['user' => 'array'])]
    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionObject($this);
        return [
            strtolower($reflection->getShortName()) => [
                'id' => $this->getId(),
                'email' => $this->getEmail(),
                'role' => $this->role->name,
            ]
        ];
    }
}
