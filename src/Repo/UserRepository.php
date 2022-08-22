<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Repo;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @package Kematjaya\UserBundle\Repo
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserRepository extends KmjUserRepository
{
    /**
     * 
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    
    /**
     * 
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;
    
    public function __construct(RoleHierarchyInterface $roleHierarchy, TokenStorageInterface $tokenStorage, ManagerRegistry $registry)
    {
        $this->tokenStorage = $tokenStorage;
        $this->roleHierarchy = $roleHierarchy;
        parent::__construct($registry, DefaultUser::class);
    }
    
    public function createUser(): KmjUserInterface 
    {
        return new DefaultUser();
    }
    
    public function createQueryBuilder($alias, $indexBy = null):QueryBuilder 
    {
        $qb = parent::createQueryBuilder($alias, $indexBy);
        $user = $this->getUser();
        if (KmjUserInterface::ROLE_SUPER_USER === $user->getSingleRole()) {
            
            return $qb;
        }
        
        $roles = $this->roleHierarchy->getReachableRoleNames($user->getRoles());
        foreach ($roles as $k => $role) {
            if (0 == $k) {
                $qb->andWhere(
                    $qb->expr()->like(sprintf("TEXT(%s.roles)", $alias), $qb->expr()->literal('%' . $role . '%'))
                );
                continue;
            }
            $qb->orWhere(
                $qb->expr()->like(sprintf("TEXT(%s.roles)", $alias), $qb->expr()->literal('%' . $role . '%'))
            );
        }
            
        return $qb;
    }
    
    protected function getUser():?DefaultUser
    {
        $token = $this->getToken();
        if (null === $token) {
            
            return null;
        }
        
        return $token->getUser();
    }
    
    protected function getToken():?TokenInterface
    {
        return $this->tokenStorage->getToken();
    }
}
