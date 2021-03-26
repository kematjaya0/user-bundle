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
class UserRepository implements KmjUserRepoInterface
{
    
    public function createUser(): KmjUserInterface 
    {
        return new DefaultUser();
    }

    public function findOneByIdentityNumber(string $identityNumber): ?KmjUserInterface 
    {
        return (new DefaultUser())
                ->setIsActive(true)
                ->setUsername($identityNumber)
                ->setName($identityNumber)
                ;
    }

    public function findOneByUsernameAndActive(string $username): ?KmjUserInterface 
    {
        return (new DefaultUser())
                ->setIsActive(true)
                ->setUsername($username)
                ->setName($username)
                ;
    }

}
