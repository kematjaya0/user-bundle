<?php

namespace Kematjaya\UserBundle\Tests;

use Kematjaya\UserBundle\Tests\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class BundleTest extends WebTestCase 
{
    public function testInitBundle(): ContainerInterface
    {
        $client = static::createClient();
        $container = $client->getContainer();
        
        $this->assertInstanceOf(ContainerInterface::class, $container);
        return $container;
        // Test if the service exists
        //$this->assertTrue($container->has('kematjaya.breadcrumbs_builder'));
        //$this->assertTrue($container->has('kematjaya.breadcrumbs_extension'));
        //$service = $container->get('kematjaya.breadcrumbs_builder');
        //$ext = $container->get('kematjaya.breadcrumbs_extension');
        //echo $ext->render();
        //$this->assertInstanceOf(Builder::class, $service);
    }
    
    public static function getKernelClass() 
    {
        return AppKernel::class;
    }
}
