<?php

namespace Kematjaya\UserBundle;

use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use Kematjaya\UserBundle\DependencyInjection\Compiler\UserCompilerPass;
use Kematjaya\UserBundle\Subscriber\CompilerPass\UserTypeCompilerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        $container->registerForAutoconfiguration(UserTypeSubscriberInterface::class)
                ->addTag(UserTypeSubscriberInterface::TAG_NAME);
        
        $container->addCompilerPass(new UserTypeCompilerPass());
        $container->addCompilerPass(new UserCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
    
}
