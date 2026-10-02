<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Repo;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @package Kematjaya\UserBundle\Repo
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserRepository extends KmjUserRepository
{
    public function __construct(
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly TokenStorageInterface $tokenStorage,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, DefaultUser::class);
    }

    public function createUser(): KmjUserInterface
    {
        return new DefaultUser();
    }

    public function createQueryBuilder(mixed $alias, mixed $indexBy = null): QueryBuilder
    {
        $qb = parent::createQueryBuilder($alias, $indexBy);
        $user = $this->getUser();
        // no authenticated user (console command, fixtures): no role filter
        if (null === $user || in_array(KmjUserInterface::ROLE_SUPER_USER, $user->getRoles(), true)) {

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

    protected function getUser(): ?KmjUserInterface
    {
        $token = $this->getToken();
        $user = null !== $token ? $token->getUser() : null;

        return $user instanceof KmjUserInterface ? $user : null;
    }

    protected function getToken(): ?TokenInterface
    {
        return $this->tokenStorage->getToken();
    }
}
