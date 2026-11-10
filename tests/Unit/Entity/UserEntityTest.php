<?php

namespace Kematjaya\UserBundle\Tests\Unit\Entity;

use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\ResettingPassword;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    public function testDefaultUser(): void
    {
        $user = new DefaultUser();
        $user->setName('John Doe');
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('John Doe', (string) $user);

        $user->setUsername('johndoe');
        $this->assertSame('johndoe', $user->getUsername());
        $this->assertSame('johndoe', $user->getUserIdentifier());

        $user->setPassword('secret123');
        $this->assertSame('secret123', $user->getPassword());

        $user->setIsActive(true);
        $this->assertTrue($user->getIsActive());

        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());

        $user->setSingleRole('ROLE_SUPER_USER');
        $this->assertSame(['ROLE_SUPER_USER'], $user->getRoles());
        $this->assertSame('ROLE_SUPER_USER', $user->getSingleRole());
        $this->assertSame('johndoe', $user->getSalt());

        $user->eraseCredentials();
        $this->assertFalse($user->getIsActive());
    }

    public function testClientChangePassword(): void
    {
        $user = new DefaultUser();
        $change = new ClientChangePassword($user);

        $this->assertSame($user, $change->getUser());

        $otherUser = new DefaultUser();
        $change->setUser($otherUser);
        $this->assertSame($otherUser, $change->getUser());

        $change->setPasswordOld('oldpass');
        $this->assertSame('oldpass', $change->getPasswordOld());

        $change->setPasswordNew('newpass');
        $this->assertSame('newpass', $change->getPasswordNew());

        $change->setPasswordReNew('newpass');
        $this->assertSame('newpass', $change->getPasswordReNew());
    }

    public function testResettingPassword(): void
    {
        $user = new DefaultUser();
        $user->setUsername('target_user');
        $reset = new ResettingPassword($user);

        $other = new DefaultUser();
        $other->setUsername('new_target');
        $reset->setUser($other);
        $this->assertSame('new_target', $reset->getUsername());
        $reset->setPassword('p1');
        $this->assertSame('p1', $reset->getPassword());

        $reset->setRetypePassword('p1');
        $this->assertSame('p1', $reset->getRetypePassword());

        $this->assertNotNull($reset->getId());
    }

    public function testResettingPasswordUsernameSetterIsNoOpForFormCompatibility(): void
    {
        $user = new DefaultUser();
        $reset = new ResettingPassword($user);

        $this->assertNull($reset->setUsername('ignored'));
        $this->assertNull($reset->getUsername());
    }
}
