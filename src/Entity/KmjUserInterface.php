<?php

namespace Kematjaya\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface KmjUserInterface extends UserInterface
{
    public const ROLE_USER = "ROLE_USER";
    public const ROLE_ADMINISTRATOR = "ROLE_ADMINISTRATOR";
    public const ROLE_SUPER_USER = "ROLE_SUPER_USER";

    public function setName(string $name): self;

    public function getName(): ?string;

    public function setIsActive(bool $is_active): self;

    public function getIsActive(): ?bool;

    public function setPassword(string $password): self;

    public function setRoles(array $roles): self;

    public function setUsername(string $username): self;

}
