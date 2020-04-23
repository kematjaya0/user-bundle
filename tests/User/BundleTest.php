<?php

namespace Kematjaya\Tests\ItemPack;

use Kematjaya\Tests\User\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class BundleTest extends WebTestCase 
{
    public function testInitBundle()
    {
        $client = static::createClient();
        $container = $client->getContainer();
        dump($container->has('Kematjaya\User\DataFixtures\UserFixtures'));exit;
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
