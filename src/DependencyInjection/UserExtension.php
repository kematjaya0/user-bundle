<?php

namespace Kematjaya\UserBundle\DependencyInjection;

use Kematjaya\UserBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container) 
    {
        
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter($this->getAlias(), $config);
        
    }
}
