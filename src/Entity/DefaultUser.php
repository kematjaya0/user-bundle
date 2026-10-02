<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kematjaya\UserBundle\Repo\UserRepository;

/**
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'kmj_default_user')]
class DefaultUser extends KmjUser
{
    private ?string $single_role = null;

    public function getSingleRole(): string
    {
        $roles = $this->getRoles();

        return end($roles);
    }

    public function setSingleRole(string $single_role): self
    {
        $this->single_role = $single_role;

        $this->setRoles([$single_role]);

        return $this;
    }


}
