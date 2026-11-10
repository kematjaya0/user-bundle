<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kematjaya\UserBundle\Repo\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'kmj_default_user')]
class DefaultUser extends KmjUser
{
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    public function getSingleRole(): string
    {
        $roles = $this->getRoles();

        return end($roles);
    }

    public function setSingleRole(string $single_role): self
    {
        $this->setRoles([$single_role]);

        return $this;
    }


}
