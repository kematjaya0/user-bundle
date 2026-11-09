<?php

namespace Kematjaya\UserBundle\Tests;

use Kematjaya\UserBundle\UserBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new UserBundle(),
            new SecurityBundle(),
            new FrameworkBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader): void {
            $loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'config/config.yml');
            $loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'config/services_test.yml');
            $loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'config/bundle.yml');

            $container->addObjectResource($this);
        });
    }
}
