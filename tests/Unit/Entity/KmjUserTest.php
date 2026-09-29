<?php

namespace Kematjaya\UserBundle\Tests\Unit\Entity;

use Kematjaya\UserBundle\Entity\DefaultUser;
use PHPUnit\Framework\TestCase;

class KmjUserTest extends TestCase
{
    public function testGetRolesReturnsArrayForNewUser(): void
    {
        $this->assertSame([], (new DefaultUser())->getRoles());
    }

    public function testGetUserIdentifierReturnsUsername(): void
    {
        $user = (new DefaultUser())->setUsername('budi');

        $this->assertSame('budi', $user->getUserIdentifier());
    }

    /**
     * Symfony calls eraseCredentials() after every successful login; it must
     * not change the active flag.
     */
    public function testEraseCredentialsKeepsUserActive(): void
    {
        $user = (new DefaultUser())->setIsActive(true);

        $user->eraseCredentials();

        $this->assertTrue($user->getIsActive());
    }

    public function testToStringWithoutNameReturnsEmptyString(): void
    {
        $this->assertSame('', (string) new DefaultUser());
    }

    public function testGetSingleRoleWithoutRolesReturnsEmptyString(): void
    {
        $this->assertSame('', (new DefaultUser())->getSingleRole());
    }

    public function testSetSingleRoleReplacesRoles(): void
    {
        $user = (new DefaultUser())->setRoles(['ROLE_A', 'ROLE_B']);

        $user->setSingleRole('ROLE_C');

        $this->assertSame(['ROLE_C'], $user->getRoles());
        $this->assertSame('ROLE_C', $user->getSingleRole());
    }
}
