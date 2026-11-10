<?php

namespace Kematjaya\UserBundle\Tests\Unit\Form;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Form\KmjUserType;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class KmjUserTypeTest extends TestCase
{
    public function testBuildFormAddsSubscriberAndBaseFields(): void
    {
        $subscriber = $this->createMock(UserTypeSubscriberInterface::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('getData')->willReturn(new DefaultUser());
        $builder->expects($this->exactly(3))->method('add')->willReturnSelf();
        $builder->expects($this->once())->method('addEventSubscriber')->with($subscriber);
        $registry = $this->createMock(UserTypeSubscriberBuilderInterface::class);
        $registry->method('getSubscriber')->willReturn($subscriber);

        $type = new KmjUserType($registry);
        $type->buildForm($builder, []);
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);
        self::assertSame('Kematjaya\\UserBundle\\Entity\\KmjUserInterface', $resolver->resolve()['data_class']);
    }
}
