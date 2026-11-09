<?php

namespace Kematjaya\UserBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private readonly KmjUserRepoInterface $kmjUserRepo, private readonly PasswordHasherFactoryInterface $encoderFactory)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $arr = [
            KmjUserInterface::ROLE_SUPER_USER => ['root'],
            KmjUserInterface::ROLE_ADMINISTRATOR => ['admin'],
        ];

        $i = 1;
        foreach ($arr as $role => $users) {
            foreach ($users as $username) {
                $user = $this->kmjUserRepo->createUser();
                $user->setUsername($username);
                $user->setName(strtoupper($username));
                $user->setIsActive(true);
                $encoder = $this->encoderFactory->getPasswordHasher($user);
                $password = $encoder->hash('admin123');
                $user->setPassword($password);
                $user->setRoles([$role]);
                $manager->persist($user);
                $i++;
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['kmj-user'];
    }

}
