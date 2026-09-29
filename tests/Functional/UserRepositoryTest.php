<?php

namespace Kematjaya\UserBundle\Tests\Functional;

use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The role filter for non super users relies on the TEXT() DQL function that the
 * host application registers, so it is not covered here.
 */
class UserRepositoryTest extends FunctionalTestCase
{
    private function countWithQueryBuilder(): int
    {
        return (int) static::getContainer()->get(KmjUserRepoInterface::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function testQueryBuilderWithoutTokenIsNotFiltered(): void
    {
        $this->createUser('satu', 'x');
        $this->createUser('dua', 'x', ['ROLE_ADMINISTRATOR']);

        $this->assertSame(2, $this->countWithQueryBuilder());
    }

    public function testQueryBuilderForSuperUserIsNotFiltered(): void
    {
        $root = $this->createUser('root', 'x', ['ROLE_ADMINISTRATOR', 'ROLE_SUPER_USER']);
        $this->createUser('dua', 'x');
        static::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($root, 'main', $root->getRoles())
        );

        $this->assertSame(2, $this->countWithQueryBuilder());
    }
}
