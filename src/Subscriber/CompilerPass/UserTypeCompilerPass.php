<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Subscriber\CompilerPass;

use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @package Kematjaya\UserBundle\Subscriber\CompilerPass
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition(UserTypeSubscriberBuilderInterface::class);
        $taggedServices = $container->findTaggedServiceIds(UserTypeSubscriberInterface::TAG_NAME);
        foreach($taggedServices as $id => $tags) {
            $definition->addMethodCall('addSubscriber', [new Reference($id)]);
        }
    }
}
