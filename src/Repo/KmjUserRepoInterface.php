<?php

namespace Kematjaya\UserBundle\Repo;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Doctrine\Common\Persistence\ObjectRepository;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface KmjUserRepoInterface extends ObjectRepository
{
    public function findOneByUsernameAndActive(string $username):?KmjUserInterface;
    
    public function createUser():KmjUserInterface;
}
