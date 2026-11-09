<?php

namespace Kematjaya\UserBundle\Tests\Unit\Form;

use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Entity\ClientChangePasswordInterface;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Entity\ResettingPassword;
use Kematjaya\UserBundle\Form\ChangePasswordType;
use Kematjaya\UserBundle\Form\KmjUserType;
use Kematjaya\UserBundle\Form\LoginType;
use Kematjaya\UserBundle\Form\ResetPasswordType;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserFormTypeTest extends TestCase
{
    public function testLoginType(): void
    {
        $bag = new ParameterBag([
            'user' => ['use_captcha' => false],
        ]);
        $type = new LoginType($bag);
        $this->assertSame(LoginType::NAME, $type->getBlockPrefix());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))->method('add')->willReturnSelf();
        $type->buildForm($builder, []);
    }

    public function testLoginTypeWithCaptcha(): void
    {
        $bag = new ParameterBag([
            'user' => ['use_captcha' => true],
        ]);
        $type = new LoginType($bag);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(3))->method('add')->willReturnSelf();
        $type->buildForm($builder, []);
    }

    public function testResetPasswordType(): void
    {
        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $type = new ResetPasswordType($factory);

        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);
        $opts = $resolver->resolve([]);
        $this->assertSame(ResettingPassword::class, $opts['data_class']);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(3))->method('add')->willReturnSelf();
        $builder->expects($this->once())->method('addEventListener')->willReturnSelf();
        $type->buildForm($builder, []);
    }

    public function testChangePasswordType(): void
    {
        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $type = new ChangePasswordType($factory, $hasher);

        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);
        $opts = $resolver->resolve([]);
        $this->assertSame(ClientChangePasswordInterface::class, $opts['data_class']);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(3))->method('add')->willReturnSelf();
        $builder->expects($this->once())->method('addEventListener')->willReturnSelf();
        $type->buildForm($builder, []);
    }

    public function testKmjUserType(): void
    {
        $builderMock = $this->createMock(UserTypeSubscriberBuilderInterface::class);
        $type = new KmjUserType($builderMock);

        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);
        $opts = $resolver->resolve([]);
        $this->assertSame(KmjUserInterface::class, $opts['data_class']);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->method('getData')->willReturn(new DefaultUser());
        $formBuilder->expects($this->exactly(3))->method('add')->willReturnSelf();

        $type->buildForm($formBuilder, []);
    }

    public function testResetPasswordPostSubmitListener(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('hashed_pwd');

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->method('getPasswordHasher')->willReturn($hasher);

        $type = new ResetPasswordType($factory);

        $listener = null;
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnSelf();
        $builder->expects($this->once())->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, $this->callback(function ($cb) use (&$listener): bool {
                $listener = $cb;
                return is_callable($cb);
            }));

        $type->buildForm($builder, []);

        $form = $this->createMock(FormInterface::class);
        $event1 = new FormEvent($form, null);
        $listener($event1);

        $user = new DefaultUser();
        $reset = new ResettingPassword($user);
        $reset->setPassword('p1');
        $reset->setRetypePassword('p2');

        $field = $this->createMock(FormInterface::class);
        $field->expects($this->once())->method('addError');
        $form->method('get')->with('retype_password')->willReturn($field);

        $event2 = new FormEvent($form, $reset);
        $listener($event2);

        $reset->setRetypePassword('p1');
        $user->setPassword('p1');
        $event3 = new FormEvent($form, $reset);
        $listener($event3);
        $this->assertSame('hashed_pwd', $reset->getPassword());
    }

    public function testChangePasswordPostSubmitListener(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('hashed_new');

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->method('getPasswordHasher')->willReturn($hasher);

        $userHasher = $this->createMock(UserPasswordHasherInterface::class);
        $type = new ChangePasswordType($factory, $userHasher);

        $listener = null;
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('add')->willReturnSelf();
        $builder->expects($this->once())->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, $this->callback(function ($cb) use (&$listener): bool {
                $listener = $cb;
                return is_callable($cb);
            }));

        $type->buildForm($builder, []);

        $user = new DefaultUser();
        $change = new ClientChangePassword($user);
        $change->setPasswordOld('wrong');
        $change->setPasswordNew('newpass');
        $change->setPasswordReNew('newpass');

        $userHasher->method('isPasswordValid')->with($user, 'wrong')->willReturn(false);
        $fieldOld = $this->createMock(FormInterface::class);
        $fieldOld->expects($this->once())->method('addError');
        $form = $this->createMock(FormInterface::class);
        $form->method('get')->willReturnMap([['password_old', $fieldOld]]);

        $event1 = new FormEvent($form, $change);
        $listener($event1);

        $userHasher2 = $this->createMock(UserPasswordHasherInterface::class);
        $userHasher2->method('isPasswordValid')->willReturn(true);
        $type2 = new ChangePasswordType($factory, $userHasher2);
        $listener2 = null;
        $builder2 = $this->createMock(FormBuilderInterface::class);
        $builder2->method('add')->willReturnSelf();
        $builder2->method('addEventListener')->willReturnCallback(function ($evt, $cb) use (&$listener2, $builder2): FormBuilderInterface {
            $listener2 = $cb;
            return $builder2;
        });
        $type2->buildForm($builder2, []);

        $change->setPasswordOld('correct');
        $change->setPasswordNew('new1');
        $change->setPasswordReNew('new2');

        $fieldReNew = $this->createMock(FormInterface::class);
        $fieldReNew->expects($this->once())->method('addError');
        $form2 = $this->createMock(FormInterface::class);
        $form2->method('get')->with('password_re_new')->willReturn($fieldReNew);

        $event2 = new FormEvent($form2, $change);
        $listener2($event2);

        $change->setPasswordReNew('new1');
        $form3 = $this->createMock(FormInterface::class);
        $event3 = new FormEvent($form3, $change);
        $listener2($event3);
        $this->assertSame('hashed_new', $user->getPassword());
    }
}
