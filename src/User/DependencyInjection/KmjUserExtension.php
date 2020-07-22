<?php

namespace Kematjaya\User\DependencyInjection;

use Kematjaya\User\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class KmjUserExtension extends Extension 
{
    public function load(array $configs, ContainerBuilder $container) 
    {
        
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.xml');
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter($this->getAlias(), $config);
        
    }
}
