<?php

namespace Kematjaya\UserBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base functional test: every test starts with a fresh SQLite schema.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $manager = $this->getEntityManager();
        $metadata = $manager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($manager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    protected function createUser(string $username, string $password, array $roles = ['ROLE_USER'], bool $isActive = true): DefaultUser
    {
        $user = new DefaultUser();
        $user->setUsername($username)
            ->setName(strtoupper($username))
            ->setRoles($roles)
            ->setIsActive($isActive);
        $user->setPassword($this->getPasswordHasher()->hashPassword($user, $password));

        $manager = $this->getEntityManager();
        $manager->persist($user);
        $manager->flush();

        return $user;
    }

    protected function findUser(string $username): ?DefaultUser
    {
        $manager = $this->getEntityManager();
        $manager->clear();

        return $manager->getRepository(DefaultUser::class)->findOneBy(['username' => $username]);
    }

    protected function isPasswordValid(string $username, string $plainPassword): bool
    {
        $user = $this->findUser($username);

        return null !== $user && $this->getPasswordHasher()->isPasswordValid($user, $plainPassword);
    }

    private function getPasswordHasher(): UserPasswordHasherInterface
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class);
    }
}
