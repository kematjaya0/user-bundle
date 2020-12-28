<?php

/**
 * This file is part of the user-bundle.
 */
namespace Kematjaya\UserBundle\Tests\Repository;

use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Entity\KmjUserInterface;

/**
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class KmjUserRepository implements KmjUserRepoInterface
{
    
    public function createUser(): KmjUserInterface 
    {
        
    }

    public function findOneByIdentityNumber(string $identityNumber): ?KmjUserInterface 
    {
        
    }

    public function findOneByUsernameAndActive(string $username): ?KmjUserInterface 
    {
        
    }

}
