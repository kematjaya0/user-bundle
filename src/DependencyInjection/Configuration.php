<?php

namespace Kematjaya\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder 
    {
        $treeBuilder = new TreeBuilder('user');
        $rootNode = $treeBuilder->getRootNode();
        
        $rootNode
                //->fixXmlConfig('path')
            ->children()
            ->arrayNode('route')->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('login')->defaultValue('kmj_user_login')->end()
            ->scalarNode('auth_success')->defaultValue('homepage')->end()
            ->scalarNode('reset_password_redirect_path')->defaultValue('homepage')->end()
            ->end()
            ->end()
            ->end();
        
        return $treeBuilder;
    }

}
