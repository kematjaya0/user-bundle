<?php

namespace Kematjaya\User\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class Configuration implements ConfigurationInterface 
{
    //put your code here
    public function getConfigTreeBuilder(): TreeBuilder 
    {
        $treeBuilder = new TreeBuilder('kmj_user');
        $treeBuilder->getRootNode()
                ->children()
                    ->arrayNode('route')
                        ->children()
                            ->scalarNode('login')->end()
                            ->scalarNode('auth_success')->end()
                        ->end()
                    ->end()
                ->end();
        
        return $treeBuilder;
    }

}
