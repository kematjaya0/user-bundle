<?php

namespace Kematjaya\User\DataFixtures;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Kematjaya\User\Entity\KmjUserInterface;
use Kematjaya\User\Repo\KmjUserRepoInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserFixtures extends Fixture implements FixtureGroupInterface
{
    private $kmjUserRepo;
    
    private $encoderFactory;
    
    public function __construct(KmjUserRepoInterface $kmjUserRepo, EncoderFactoryInterface $encoderFactory) 
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
        foreach($arr as $role => $users)
        {
            foreach($users as $username)
            {
                $user = $this->kmjUserRepo->createUser();
                $user->setUsername($username);
                $user->setName(strtoupper($username));
                $user->setIsActive(true);
                $encoder = $this->encoderFactory->getEncoder($user);
                $password = $encoder->encodePassword( 'admin123', $user->getUsername());
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
