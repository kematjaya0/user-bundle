<?php

namespace Kematjaya\UserBundle\Tests\Unit\Repo;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Repo\KmjUserRepository;
use Kematjaya\UserBundle\Repo\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserRepositoryTest extends TestCase
{
    private function createRegistry(EntityManagerInterface $em): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return $registry;
    }

    public function testKmjUserRepository(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(DefaultUser::class);
        $em->method('getClassMetadata')->willReturn($meta);

        $repo = new KmjUserRepository($this->createRegistry($em));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("please implement method 'createUser()'");
        $repo->createUser();
    }

    public function testUserRepositoryCreate(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(DefaultUser::class);
        $em->method('getClassMetadata')->willReturn($meta);

        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $repo = new UserRepository($roleHierarchy, $tokenStorage, $this->createRegistry($em));
        $user = $repo->createUser();
        $this->assertInstanceOf(DefaultUser::class, $user);
    }

    public function testCreateQueryBuilderForSuperUser(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(DefaultUser::class);
        $em->method('getClassMetadata')->willReturn($meta);

        $qb = $this->createMock(QueryBuilder::class);
        $em->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();

        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);

        $user = new DefaultUser();
        $user->setSingleRole(KmjUserInterface::ROLE_SUPER_USER);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $repo = new UserRepository($roleHierarchy, $tokenStorage, $this->createRegistry($em));
        $resultQb = $repo->createQueryBuilder('u');

        $this->assertSame($qb, $resultQb);
    }

    public function testCreateQueryBuilderWithMultipleRoles(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(DefaultUser::class);
        $em->method('getClassMetadata')->willReturn($meta);

        $expr = new Expr();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('expr')->willReturn($expr);
        $em->method('createQueryBuilder')->willReturn($qb);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->willReturnSelf();
        $qb->expects($this->once())->method('orWhere')->willReturnSelf();

        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $roleHierarchy->method('getReachableRoleNames')->willReturn(['ROLE_ADMIN', 'ROLE_STAFF']);

        $user = new DefaultUser();
        $user->setSingleRole(KmjUserInterface::ROLE_ADMINISTRATOR);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $repo = new UserRepository($roleHierarchy, $tokenStorage, $this->createRegistry($em));
        $resultQb = $repo->createQueryBuilder('u');

        $this->assertSame($qb, $resultQb);
    }

    public function testKmjUserRepositoryFinders(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $meta = new ClassMetadata(DefaultUser::class);
        $em->method('getClassMetadata')->willReturn($meta);

        $repo = new class ($this->createRegistry($em)) extends KmjUserRepository {
            public function find($id, $lockMode = null, $lockVersion = null): KmjUserInterface
            {
                $user = new DefaultUser();
                $user->setUsername((string) $id);
                return $user;
            }

            public function findOneBy(array $criteria, ?array $orderBy = null): KmjUserInterface
            {
                $user = new DefaultUser();
                $user->setUsername($criteria['username'] ?? 'found');
                return $user;
            }
        };

        $this->assertNotNull($repo->findOneByIdentityNumber('ID-100'));
        $this->assertSame('active_user', $repo->findOneByUsernameAndActive('active_user')->getUsername());
    }
}
