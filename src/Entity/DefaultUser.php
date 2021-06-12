<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Kematjaya\UserBundle\Repo\UserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="kmj_default_user")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class DefaultUser extends KmjUser
{
    public function getUserIdentifier()
    {
        return $this->getUsername();
    }
}
