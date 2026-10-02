<?php

namespace Kematjaya\UserBundle\DependencyInjection;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Kematjaya\UserBundle\DataFixtures\UserFixtures;
use Kematjaya\UserBundle\Security\KmjLoginAuthenticator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {

        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter($this->getAlias(), $config);

        // Guard was removed in Symfony 6.0
        if (class_exists(AbstractFormLoginAuthenticator::class)) {
            $container->autowire(KmjLoginAuthenticator::class)
                ->setAutoconfigured(true)
                ->setArgument('$container', new Reference('service_container'));
        }

        // doctrine-fixtures-bundle is usually a dev-only dependency
        if (class_exists(Fixture::class)) {
            $container->autowire(UserFixtures::class)
                ->setAutoconfigured(true)
                ->addTag('doctrine.fixture.orm');
        }
    }
}
