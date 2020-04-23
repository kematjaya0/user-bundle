<?php

namespace App\Library\KmjUser\Repo;

use App\Library\KmjUser\Entity\KmjUserInterface;
use Doctrine\Common\Persistence\ObjectRepository;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface KmjUserRepoInterface extends ObjectRepository
{
    public function findOneByUsernameAndActive(string $username):?KmjUserInterface;
    
    public function createUser():KmjUserInterface;
}
