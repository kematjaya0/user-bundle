<?php

namespace Kematjaya\UserBundle\Tests;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class AppKernel extends Kernel 
{
    public function registerBundles()
    {
        return [
            new \Kematjaya\UserBundle\UserBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle()
        ];
    }
    
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) 
        {
            $loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'config/config.yml');
            $loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'config/services_test.yml');
            $loader->load(__DIR__ . DIRECTORY_SEPARATOR . 'config/bundle.yml');
            
            $container->addObjectResource($this);
        });
    }
}
