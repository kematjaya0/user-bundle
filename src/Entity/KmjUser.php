<?php

namespace Kematjaya\UserBundle\Entity;

use Kematjaya\UserBundle\Repo\KmjUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Table(name="kmj_user")
 * @ORM\Entity(repositoryClass=KmjUserRepository::class)
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class KmjUser implements KmjUserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_active;

    public function getId(): ?\Symfony\Component\Uid\Uuid
    {
        return $this->id;
    }

    public function __toString() 
    {
        return $this->getName();
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

    public function getRoles(): ?array
    {
        return $this->roles;
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
    public function getSalt()
    {
        return $this->getUsername();
    }

     /**
      * @see UserInterface
      */
    public function eraseCredentials()
    {
        $this->is_active = false;
    }
}
