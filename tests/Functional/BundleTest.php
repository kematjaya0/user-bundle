<?php

namespace Kematjaya\UserBundle\Tests\Functional;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Kematjaya\UserBundle\DataFixtures\UserFixtures;
use Kematjaya\UserBundle\DependencyInjection\UserExtension;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Repo\UserRepository;
use Kematjaya\UserBundle\Security\FormLoginAuthenticator;
use Kematjaya\UserBundle\Security\KmjLoginAuthenticator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

class BundleTest extends KernelTestCase
{
    public function testContainerBootsWithBundleServices(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->assertInstanceOf(UserRepository::class, $container->get(KmjUserRepoInterface::class));
        $this->assertInstanceOf(FormLoginAuthenticator::class, $container->get(FormLoginAuthenticator::class));
        $this->assertIsArray($container->getParameter('user'));
    }

    /**
     * Guard was removed in Symfony 6.0: the legacy authenticator may only be
     * defined while the Guard classes exist (Symfony 5.4).
     */
    public function testLegacyGuardAuthenticatorOnlyDefinedWhenGuardExists(): void
    {
        $container = new ContainerBuilder();
        (new UserExtension())->load([], $container);

        $this->assertSame(
            class_exists(AbstractFormLoginAuthenticator::class),
            $container->hasDefinition(KmjLoginAuthenticator::class)
        );
    }

    public function testFixturesOnlyDefinedWhenFixturesBundleExists(): void
    {
        $container = new ContainerBuilder();
        (new UserExtension())->load([], $container);

        $this->assertSame(
            class_exists(Fixture::class),
            $container->hasDefinition(UserFixtures::class)
        );
    }
}
