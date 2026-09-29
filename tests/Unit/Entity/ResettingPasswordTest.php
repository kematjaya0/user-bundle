<?php

namespace Kematjaya\UserBundle\Tests\Unit\Entity;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\ResettingPassword;
use PHPUnit\Framework\TestCase;

class ResettingPasswordTest extends TestCase
{
    /**
     * The typed password is plain text; the user may only change once the
     * form is valid and the password is hashed.
     */
    public function testSetPasswordDoesNotTouchUserPassword(): void
    {
        $user = (new DefaultUser())->setUsername('budi')->setPassword('hash-lama');
        $resetting = new ResettingPassword($user);

        $resetting->setPassword('rahasia-baru');

        $this->assertSame('rahasia-baru', $resetting->getPassword());
        $this->assertSame('hash-lama', $user->getPassword());
    }

    public function testUsernameComesFromUser(): void
    {
        $resetting = new ResettingPassword((new DefaultUser())->setUsername('budi'));

        $this->assertSame('budi', $resetting->getUsername());
    }
}
