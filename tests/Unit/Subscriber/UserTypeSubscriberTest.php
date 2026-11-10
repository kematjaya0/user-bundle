<?php

namespace Kematjaya\UserBundle\Tests\Unit\Subscriber;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilder;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use Kematjaya\UserBundle\Subscriber\CompilerPass\UserTypeCompilerPass;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class UserTypeSubscriberTest extends TestCase
{
    public function testIsSupportedAndSubscribedEvents(): void
    {
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);

        $subscriber = new UserTypeSubscriber($repo, $tokenStorage, $hasherFactory, $roleHierarchy);

        $this->assertTrue($subscriber->isSupported(DefaultUser::class));
        $this->assertFalse($subscriber->isSupported(\stdClass::class));

        $events = UserTypeSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertArrayHasKey(FormEvents::POST_SUBMIT, $events);
    }

    public function testPostSetDataWithDefaultUserWithoutId(): void
    {
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);

        $subscriber = new UserTypeSubscriber($repo, $tokenStorage, $hasherFactory, $roleHierarchy);

        $user = new DefaultUser();
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->atLeastOnce())->method('add')->willReturnSelf();
        $form->expects($this->once())->method('remove')->with('roles')->willReturnSelf();

        $event = new PostSetDataEvent($form, $user);
        $subscriber->postSetData($event);
    }

    public function testPostSubmitNewUserSuccess(): void
    {
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $repo->method('findOneByUsernameAndActive')->with('newuser')->willReturn(null);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->method('hash')->with('plain_password')->willReturn('hashed_password');

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory->method('getPasswordHasher')->willReturn($hasher);

        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);

        $subscriber = new UserTypeSubscriber($repo, $tokenStorage, $hasherFactory, $roleHierarchy);

        $user = new DefaultUser();
        $user->setUsername('  newuser  ');
        $user->setPassword('plain_password');

        $form = $this->createMock(FormInterface::class);
        $event = new PostSubmitEvent($form, $user);

        $subscriber->postSubmit($event);

        $this->assertSame('newuser', $user->getUsername());
        $this->assertSame('hashed_password', $user->getPassword());
    }

    public function testPostSubmitUsernameTaken(): void
    {
        $existing = new DefaultUser();
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $repo->method('findOneByUsernameAndActive')->with('existing_user')->willReturn($existing);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);

        $subscriber = new UserTypeSubscriber($repo, $tokenStorage, $hasherFactory, $roleHierarchy);

        $user = new DefaultUser();
        $user->setUsername('existing_user');

        $field = $this->createMock(FormInterface::class);
        $field->expects($this->once())->method('addError')->with($this->isInstanceOf(FormError::class));

        $form = $this->createMock(FormInterface::class);
        $form->method('get')->with('username')->willReturn($field);

        $event = new PostSubmitEvent($form, $user);
        $subscriber->postSubmit($event);
    }

    public function testGetRoles(): void
    {
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn(['ROLE_ADMIN']);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $roleHierarchy->method('getReachableRoleNames')->with(['ROLE_ADMIN'])->willReturn(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_STAFF']);

        $subscriber = new UserTypeSubscriber($repo, $tokenStorage, $hasherFactory, $roleHierarchy);
        $roles = $subscriber->getRoles();

        $this->assertArrayHasKey('ROLE_ADMIN', $roles);
        $this->assertArrayHasKey('ROLE_STAFF', $roles);
        $this->assertArrayNotHasKey('ROLE_USER', $roles);
    }

    public function testBuilderAndCompilerPass(): void
    {
        $builder = new UserTypeSubscriberBuilder();
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $roleHierarchy = $this->createMock(RoleHierarchyInterface::class);
        $subscriber = new UserTypeSubscriber($repo, $tokenStorage, $hasherFactory, $roleHierarchy);

        $builder->addSubscriber($subscriber);
        $found = $builder->getSubscriber(DefaultUser::class);
        $this->assertSame($subscriber, $found);

        $pass = new UserTypeCompilerPass();
        $container = new ContainerBuilder();
        $pass->process($container); // early return when builder not registered
        $this->assertFalse($container->has(UserTypeSubscriberBuilderInterface::class));
    }
}
