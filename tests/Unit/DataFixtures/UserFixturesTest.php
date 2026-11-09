<?php

namespace Kematjaya\UserBundle\Tests\Unit\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Kematjaya\UserBundle\DataFixtures\UserFixtures;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserFixturesTest extends TestCase
{
    public function testLoadFixtures(): void
    {
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $repo->method('createUser')->willReturnCallback(fn (): DefaultUser => new DefaultUser());

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('hashed_admin123');

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->method('getPasswordHasher')->willReturn($hasher);

        $fixtures = new UserFixtures($repo, $factory);
        $this->assertContains('kmj-user', UserFixtures::getGroups());

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->exactly(2))->method('persist');
        $manager->expects($this->once())->method('flush');

        $fixtures->load($manager);
    }
}
