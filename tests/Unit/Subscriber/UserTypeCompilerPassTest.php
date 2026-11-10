<?php

namespace Kematjaya\UserBundle\Tests\Unit\Subscriber;

use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use Kematjaya\UserBundle\Subscriber\CompilerPass\UserTypeCompilerPass;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class UserTypeCompilerPassTest extends TestCase
{
    public function testAddsTaggedSubscribersToBuilder(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(UserTypeSubscriberBuilderInterface::class, new Definition());
        $container->setDefinition('custom_subscriber', new Definition());
        $container->getDefinition('custom_subscriber')->addTag(UserTypeSubscriberInterface::TAG_NAME);

        (new UserTypeCompilerPass())->process($container);

        $calls = $container->getDefinition(UserTypeSubscriberBuilderInterface::class)->getMethodCalls();
        self::assertCount(1, $calls);
        self::assertSame('addSubscriber', $calls[0][0]);
        self::assertSame('custom_subscriber', (string) $calls[0][1][0]);
    }
}
