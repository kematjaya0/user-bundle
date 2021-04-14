<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Repo;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;

/**
 * @package Kematjaya\UserBundle\Repo
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserRepository extends KmjUserRepository
{
    
    public function createUser(): KmjUserInterface 
    {
        return new DefaultUser();
    }
}
