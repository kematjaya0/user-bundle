<?php

namespace Kematjaya\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
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

        $this->addRouteConfig($rootNode->children());

        return $treeBuilder;
    }

    public function addRouteConfig(NodeBuilder $node): void
    {
        $node
            ->booleanNode('use_captcha')->defaultTrue()->end()
            ->scalarNode('reset_password_role')
                ->info('role required to reset the password of another user (route kmj_user_reset_password)')
                ->defaultValue('ROLE_ADMINISTRATOR')
            ->end()
            ->arrayNode('route')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('unauthorize_redirect_path')->defaultValue('kmj_user_login')->end()
                    ->scalarNode('login')->defaultValue('kmj_user_login')->end()
                    ->arrayNode('login_success')->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('back_to_previous')->defaultFalse()->end()
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
