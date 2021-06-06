<?php

namespace Kematjaya\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder 
    {
        $treeBuilder = new TreeBuilder('user');
        $rootNode = $treeBuilder->getRootNode();
        
        $this->addRouteConfig($rootNode->children());
        
        return $treeBuilder;
    }
    
    public function addRouteConfig(NodeBuilder $node)
    {
        $node
            ->booleanNode('use_captcha')->defaultTrue()->end()
            ->arrayNode('route')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('login')->defaultValue('kmj_user_login')->end()
                    ->scalarNode('auth_success')->setDeprecated()->end()
                    ->scalarNode('reset_password_redirect_path')->setDeprecated()->end()
                    ->arrayNode('login_success')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('default')->defaultValue('homepage')->end()
                            ->arrayNode('roles')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('role')->end()
                                        ->scalarNode('path')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('reset_password_success')->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('default')->defaultValue('homepage')->end()
                            ->arrayNode('roles')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('role')->end()
                                        ->scalarNode('path')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

}
