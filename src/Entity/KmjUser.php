<?php

namespace Kematjaya\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kematjaya\UserBundle\Repo\KmjUserRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KmjUserRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\Table(name: 'kmj_user')]
abstract class KmjUser implements KmjUserInterface, LegacyPasswordAuthenticatedUserInterface, \Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $username = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'boolean')]
    private ?bool $is_active = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): KmjUserInterface
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): KmjUserInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): KmjUserInterface
    {
        $this->password = $password;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles ?? [];
    }

    public function setRoles(array $roles): KmjUserInterface
    {
        $this->roles = $roles;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): KmjUserInterface
    {
        $this->is_active = $is_active;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return $this->getUsername();
    }

    /**
     * Called by Symfony after every successful authentication to clear temporary
     * sensitive data (e.g. a plain password). This entity keeps none, and it must
     * not change the active flag here: a later flush would deactivate the user.
     *
     * @see UserInterface
     */
    public function eraseCredentials(): void {}
}
