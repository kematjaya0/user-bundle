<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kematjaya\UserBundle\Repo\UserRepository;

/**
 * @ORM\Table(name="kmj_default_user")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
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
