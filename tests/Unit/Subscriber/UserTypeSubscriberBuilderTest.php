<?php

namespace Kematjaya\UserBundle\Tests\Unit\Subscriber;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilder;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use PHPUnit\Framework\TestCase;

class UserTypeSubscriberBuilderTest extends TestCase
{
    private function createSubscriber(bool $supported): UserTypeSubscriberInterface
    {
        $subscriber = $this->createMock(UserTypeSubscriberInterface::class);
        $subscriber->method('isSupported')->willReturn($supported);

        return $subscriber;
    }

    public function testReturnsFirstSupportedSubscriber(): void
    {
        $supported = $this->createSubscriber(true);
        $builder = (new UserTypeSubscriberBuilder())
            ->addSubscriber($this->createSubscriber(false))
            ->addSubscriber($supported);

        $this->assertSame($supported, $builder->getSubscriber(DefaultUser::class));
    }

    /**
     * The signature returns ?UserTypeSubscriberInterface and KmjUserType already
     * checks for null, so a class without subscriber needs no exception.
     */
    public function testReturnsNullWhenNoSubscriberSupportsClass(): void
    {
        $builder = (new UserTypeSubscriberBuilder())->addSubscriber($this->createSubscriber(false));

        $this->assertNull($builder->getSubscriber(\stdClass::class));
    }
}
