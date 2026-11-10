<?php

declare(strict_types=1);

namespace Kematjaya\UserBundle\Tests\Unit\DependencyInjection;

use Kematjaya\UserBundle\Controller\KmjController;
use Kematjaya\UserBundle\Controller\KmjSecurityController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Membaca services.yaml bundle apa adanya. Kesalahan wiring di sini tidak tertangkap
 * test unit controller, karena controller dibuat dengan "new" di test itu.
 */
final class ServicesFileTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $loader = new YamlFileLoader(
            $this->container,
            new FileLocator(\dirname(__DIR__, 3) . '/src/Resources/config'),
        );
        $loader->load('services.yaml');
    }

    public function testControllersReceiveTheServiceLocatorNotTheWholeContainer(): void
    {
        foreach ([KmjSecurityController::class, KmjController::class] as $id) {
            $definition = $this->container->getDefinition($id);

            self::assertFalse(
                $definition->hasMethodCall('setContainer'),
                $id . ': setContainer(@service_container) membuat getUser() gagal',
            );
            self::assertTrue($definition->hasTag('controller.service_arguments'), $id);
            self::assertTrue($definition->isPublic(), $id);
        }
    }

    public function testEveryServiceClassExists(): void
    {
        foreach ($this->container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? $id;
            if (!str_starts_with($class, 'Kematjaya\\UserBundle\\DataFixtures\\') && str_starts_with($class, 'Kematjaya\\')) {
                self::assertTrue(
                    class_exists($class) || interface_exists($class),
                    'service ' . $id . ' memakai class yang tidak ada: ' . $class,
                );
            }
        }
    }
}
