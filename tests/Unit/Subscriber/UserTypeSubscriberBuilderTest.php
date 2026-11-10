<?php

namespace Kematjaya\UserBundle\Tests\Unit\Subscriber;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilder;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use PHPUnit\Framework\TestCase;

final class UserTypeSubscriberBuilderTest extends TestCase
{
    public function testFindsSubscriberForSupportedClass(): void
    {
        $subscriber = $this->createMock(UserTypeSubscriberInterface::class);
        $subscriber->method('isSupported')->with(DefaultUser::class)->willReturn(true);
        $builder = new UserTypeSubscriberBuilder();

        self::assertSame($builder, $builder->addSubscriber($subscriber));
        self::assertCount(1, $builder->getSubscribers());
        self::assertSame($subscriber, $builder->getSubscriber(DefaultUser::class));
    }

    public function testThrowsWhenNoSubscriberSupportsClass(): void
    {
        $subscriber = $this->createMock(UserTypeSubscriberInterface::class);
        $subscriber->method('isSupported')->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('subcsriber for class');
        (new UserTypeSubscriberBuilder())->addSubscriber($subscriber)->getSubscriber(DefaultUser::class);
    }
}
