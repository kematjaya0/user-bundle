<?php

namespace Kematjaya\UserBundle\Tests\Unit\DependencyInjection;

use Kematjaya\UserBundle\DependencyInjection\Compiler\UserCompilerPass;
use Kematjaya\UserBundle\DependencyInjection\Configuration;
use Kematjaya\UserBundle\DependencyInjection\UserExtension;
use Kematjaya\UserBundle\UserBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class UserDiTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new UserBundle();
        $this->assertInstanceOf(Bundle::class, $bundle);

        $container = new ContainerBuilder();
        $bundle->build($container);
        $this->assertNotEmpty($container->getCompilerPassConfig()->getPasses());
    }

    public function testExtension(): void
    {
        $extension = new UserExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('user'));
        $config = $container->getParameter('user');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('route', $config);
    }

    public function testConfiguration(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testCompilerPass(): void
    {
        $pass = new UserCompilerPass();
        $container = new ContainerBuilder();
        $pass->process($container);
        $this->assertFalse($container->hasDefinition('event_dispatcher'));
    }
}
