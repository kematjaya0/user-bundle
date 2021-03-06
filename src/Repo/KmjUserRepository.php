<?php

namespace Kematjaya\UserBundle\Repo;

use Kematjaya\UserBundle\Entity\KmjUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BaseUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseUser[]    findAll()
 * @method BaseUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KmjUserRepository extends ServiceEntityRepository implements KmjUserRepoInterface
{
    public function __construct(ManagerRegistry $registry, string $entityClass = null)
    {
        $entityClass = null === $entityClass ? KmjUser::class : $entityClass;
        parent::__construct($registry, $entityClass);
    }
    
    public function createUser(): KmjUserInterface 
    {
        throw new \Exception(sprintf("please implement method '%s' for create object", 'createUser()'));
    }

    public function findOneByIdentityNumber(string $identityNumber): ?KmjUserInterface 
    {
        return $this->find($identityNumber);
    }

    public function findOneByUsernameAndActive(string $username): ?KmjUserInterface 
    {
        return $this->findOneBy(['username' => $username, 'is_active' => true]);
    }

}
