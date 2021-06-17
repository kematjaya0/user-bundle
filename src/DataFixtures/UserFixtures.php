<?php

namespace Kematjaya\UserBundle\DataFixtures;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserFixtures extends Fixture implements FixtureGroupInterface
{
    
    /**
     * 
     * @var KmjUserRepoInterface
     */
    private $kmjUserRepo;
    
    /**
     * 
     * @var PasswordHasherFactoryInterface
     */
    private $encoderFactory;
    
    public function __construct(KmjUserRepoInterface $kmjUserRepo, PasswordHasherFactoryInterface $encoderFactory) 
    {
        $this->kmjUserRepo = $kmjUserRepo;
        $this->encoderFactory = $encoderFactory;
    }
    
    public function load(ObjectManager $manager)
    {
        $arr = [
            KmjUserInterface::ROLE_SUPER_USER => ['root'], 
            KmjUserInterface::ROLE_ADMINISTRATOR => ['admin']
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
