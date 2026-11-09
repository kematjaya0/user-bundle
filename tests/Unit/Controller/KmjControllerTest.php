<?php

namespace Kematjaya\UserBundle\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Kematjaya\UserBundle\Controller\AbstractKmjController;
use Kematjaya\UserBundle\Controller\KmjController;
use Kematjaya\UserBundle\Controller\KmjSecurityController;
use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\ResettingPassword;
use Kematjaya\UserBundle\Exception\UserNotFoundException;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Repo\KmjUserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Twig\Environment;

class KmjControllerTest extends TestCase
{
    private function createContainer(
        ?UrlGeneratorInterface $router = null,
        ?TokenStorageInterface $tokenStorage = null,
        ?Environment $twig = null,
        ?FormFactoryInterface $formFactory = null,
        ?SessionInterface $session = null,
    ): Container {
        $container = new Container();

        $router ??= $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturnCallback(fn ($route): string => '/' . $route);
        $container->set('router', $router);

        $tokenStorage ??= $this->createMock(TokenStorageInterface::class);
        $container->set('security.token_storage', $tokenStorage);

        $twig ??= $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<html>content</html>');
        $container->set('twig', $twig);

        $formFactory ??= $this->createMock(FormFactoryInterface::class);
        $container->set('form.factory', $formFactory);

        $session ??= new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack([$request]);
        $container->set('request_stack', $requestStack);

        return $container;
    }

    public function testLogoutThrows(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $controller = new KmjSecurityController($routing, $authUtils, $repo);

        $this->expectException(\LogicException::class);
        $controller->logout();
    }

    public function testGetErrorsFromForm(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $controller = new class ($routing) extends AbstractKmjController {
            public function exposeErrors(FormInterface $form): array
            {
                return $this->getErrorsFromForm($form);
            }
        };

        $form = $this->createMock(FormInterface::class);
        $error = new FormError('Invalid field');
        $form->method('getErrors')->willReturn(new FormErrorIterator($form, [$error]));

        $errors = $controller->exposeErrors($form);
        $this->assertSame(['Invalid field'], $errors);
    }

    public function testResetPasswordUserNotFound(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $controller = new KmjController($routing);

        $repo = $this->createMock(KmjUserRepoInterface::class);
        $repo->method('findOneByIdentityNumber')->with('unknown_id')->willReturn(null);

        $request = new Request();
        $this->expectException(UserNotFoundException::class);
        $controller->resetPassword($request, $repo, 'unknown_id');
    }

    public function testSecurityControllerSaveObjectValidAndInvalid(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $repo = $this->createMock(KmjUserRepoInterface::class);
        $controller = new class ($routing, $authUtils, $repo) extends KmjSecurityController {
            public function exposeSave($obj, $em)
            {
                return $this->saveObject($obj, $em);
            }
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn ($cb) => $cb($em));
        $em->expects($this->once())->method('persist');

        $user = new DefaultUser();
        $change = new ClientChangePassword($user);

        $saved = $controller->exposeSave($change, $em);
        $this->assertSame($user, $saved);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('object type not allowed');
        $controller->exposeSave(new \stdClass(), $em);
    }

    public function testKmjControllerSaveObjectValidAndInvalid(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $controller = new class ($routing) extends KmjController {
            public function exposeSave($obj, $em)
            {
                return $this->saveObject($obj, $em);
            }
        };

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn ($cb) => $cb($em));
        $em->expects($this->once())->method('persist');

        $user = new DefaultUser();
        $reset = new ResettingPassword($user);

        $saved = $controller->exposeSave($reset, $em);
        $this->assertSame($user, $saved);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('object type not allowed');
        $controller->exposeSave(new \stdClass(), $em);
    }

    public function testSecurityControllerLoginAuthenticated(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $routing->method('getLoginSuccessRedirectPath')->willReturn('dashboard');

        $authUtils = $this->createMock(AuthenticationUtils::class);
        $repo = $this->createMock(KmjUserRepoInterface::class);

        $user = new DefaultUser();
        $user->setUsername('admin');
        $user->setRoles(['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $controller = new KmjSecurityController($routing, $authUtils, $repo);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage));

        $response = $controller->login();
        $this->assertTrue($response->isRedirect('/dashboard'));
    }

    public function testSecurityControllerLoginUnauthenticated(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $authUtils->method('getLastUsername')->willReturn('last_user');
        $repo = $this->createMock(KmjUserRepoInterface::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn(new FormView());

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $controller = new KmjSecurityController($routing, $authUtils, $repo);
        $controller->setContainer($this->createContainer(formFactory: $formFactory));

        $response = $controller->login();
        $this->assertSame('<html>content</html>', $response->getContent());
    }

    public function testSecurityControllerChangePasswordUnauthenticated(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $repo = $this->createMock(KmjUserRepoInterface::class);

        $controller = new KmjSecurityController($routing, $authUtils, $repo);
        $controller->setContainer($this->createContainer());

        $response = $controller->changePassword(new Request());
        $this->assertTrue($response->isRedirect('/kmj_user_logout'));
    }
    public function testSecurityControllerChangePasswordUserNotFound(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $repo = $this->getMockBuilder(KmjUserRepository::class)->disableOriginalConstructor()->getMock();
        $repo->method('find')->willReturn(null);

        $user = new class () extends DefaultUser {
            public function getId(): UuidV4
            {
                return Uuid::v4();
            }
        };
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $controller = new KmjSecurityController($routing, $authUtils, $repo);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage));

        $this->expectException(UserNotFoundException::class);
        $controller->changePassword(new Request());
    }

    public function testSecurityControllerChangePasswordSuccess(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $routing->method('getLoginSuccessRedirectPath')->willReturn('dashboard');
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $uid = Uuid::v4();
        $user = new class ($uid) extends DefaultUser {
            public function __construct(private readonly Uuid $uid)
            {
            }
            public function getId(): Uuid
            {
                return $this->uid;
            }
        };
        $user->setRoles(['ROLE_USER']);

        $repo = $this->getMockBuilder(KmjUserRepository::class)->disableOriginalConstructor()->getMock();
        $repo->method('find')->with($uid)->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $change = new ClientChangePassword($user);
        $form->method('getData')->willReturn($change);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn ($cb) => $cb($em));
        $em->expects($this->once())->method('persist');

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($em);

        $controller = new KmjSecurityController($routing, $authUtils, $repo, $registry);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage, formFactory: $formFactory));

        $response = $controller->changePassword(new Request());
        $this->assertTrue($response->isRedirect('/dashboard'));
    }

    public function testKmjControllerProfile(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $routing->method('getLoginSuccessRedirectPath')->willReturn('dashboard');

        $user = new DefaultUser();
        $user->setRoles(['ROLE_USER']);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $controller = new KmjController($routing);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage));

        $response = $controller->profile();
        $this->assertSame('<html>content</html>', $response->getContent());
    }

    public function testKmjControllerResetPasswordSuccess(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $routing->method('getResetPasswordRedirectPath')->willReturn('login');

        $user = new DefaultUser();
        $user->setRoles(['ROLE_USER']);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $repo = $this->createMock(KmjUserRepoInterface::class);
        $repo->method('findOneByIdentityNumber')->with('id-10')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $reset = new ResettingPassword($user);
        $form->method('getData')->willReturn($reset);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn ($cb) => $cb($em));
        $em->expects($this->once())->method('persist');

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($em);

        $controller = new KmjController($routing, $registry);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage, formFactory: $formFactory));

        $response = $controller->resetPassword(new Request(), $repo, 'id-10');
        $this->assertTrue($response->isRedirect('/login'));
    }

    public function testSecurityControllerChangePasswordRenderOnUnsubmittedForm(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $authUtils = $this->createMock(AuthenticationUtils::class);

        $uid = Uuid::v4();
        $user = new class ($uid) extends DefaultUser {
            public function __construct(private readonly Uuid $uid)
            {
            }
            public function getId(): Uuid
            {
                return $this->uid;
            }
        };

        $repo = $this->getMockBuilder(KmjUserRepository::class)->disableOriginalConstructor()->getMock();
        $repo->method('find')->with($uid)->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn(new FormView());

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $controller = new KmjSecurityController($routing, $authUtils, $repo);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage, formFactory: $formFactory));

        $response = $controller->changePassword(new Request());
        $this->assertSame('<html>content</html>', $response->getContent());
    }

    public function testKmjControllerResetPasswordRenderOnUnsubmittedForm(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $user = new DefaultUser();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $repo = $this->createMock(KmjUserRepoInterface::class);
        $repo->method('findOneByIdentityNumber')->with('id-10')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn(new FormView());

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $controller = new KmjController($routing);
        $controller->setContainer($this->createContainer(tokenStorage: $tokenStorage, formFactory: $formFactory));

        $response = $controller->resetPassword(new Request(), $repo, 'id-10');
        $this->assertSame('<html>content</html>', $response->getContent());
    }

    public function testProcessFormValidationFailure(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $controller = new class ($routing) extends AbstractKmjController {
            public function exposeProcess(Request $req, FormInterface $form)
            {
                return $this->processForm($req, $form);
            }
        };

        $session = new Session(new MockArraySessionStorage());
        $controller->setContainer($this->createContainer(session: $session));

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $error = new FormError('Invalid field');
        $form->method('getErrors')->willReturn(new FormErrorIterator($form, [$error]));

        $res = $controller->exposeProcess(new Request(), $form);
        $this->assertNull($res);
        $this->assertTrue($session->getFlashBag()->has('error'));
    }
}
