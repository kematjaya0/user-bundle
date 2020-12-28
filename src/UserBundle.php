<?php

namespace Kematjaya\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Kematjaya\UserBundle\DependencyInjection\Compiler\UserCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        $container->addCompilerPass(new UserCompilerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
    
}
