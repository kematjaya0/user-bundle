<?php

namespace Kematjaya\UserBundle\Tests\Unit\Security;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Security\FormLoginAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class FormLoginAuthenticatorTest extends TestCase
{
    private function createAuthenticator(
        ?HttpUtils $httpUtils = null,
        ?UrlGeneratorInterface $urlGen = null,
        ?UserProviderInterface $provider = null,
        ?FormFactoryInterface $formFactory = null,
        ?RoutingConfigurationFactoryInterface $routing = null,
    ): FormLoginAuthenticator {
        $httpUtils ??= $this->createMock(HttpUtils::class);
        $urlGen ??= $this->createMock(UrlGeneratorInterface::class);
        $provider ??= $this->createMock(UserProviderInterface::class);
        $formFactory ??= $this->createMock(FormFactoryInterface::class);
        $routing ??= $this->createMock(RoutingConfigurationFactoryInterface::class);

        return new FormLoginAuthenticator($httpUtils, $urlGen, $provider, $formFactory, $routing);
    }

    public function testSupports(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('checkRequestPath')->willReturn(true);

        $auth = $this->createAuthenticator($httpUtils);

        $request = Request::create('/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded']);
        $this->assertTrue($auth->supports($request));

        $getReq = Request::create('/login', 'GET');
        $this->assertFalse($auth->supports($getReq));

        $otherPath = Request::create('/other', 'POST');
        $httpUtils2 = $this->createMock(HttpUtils::class);
        $httpUtils2->method('checkRequestPath')->willReturn(false);
        $auth2 = $this->createAuthenticator($httpUtils2);
        $this->assertFalse($auth2->supports($otherPath));

        $jsonRequest = Request::create('/login', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->assertFalse($auth->supports($jsonRequest));
    }

    public function testOnAuthenticationSuccess(): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $routing->method('getLoginSuccessRedirectPath')->with(['ROLE_USER'])->willReturn('admin_index');

        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->willReturnCallback(
            static fn (string $name): string => match ($name) {
                'admin_index' => '/admin/home.html',
                'sso_authorize' => 'http://localhost/sso',
            },
        );

        $auth = $this->createAuthenticator(null, $urlGen, null, null, $routing);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn(['ROLE_USER']);

        // login biasa: tujuan dari konfigurasi user.route.login_success, bukan route tetap
        $req1 = Request::create('/login', 'POST');
        $req1->setSession(new Session(new MockArraySessionStorage()));
        $resp1 = $auth->onAuthenticationSuccess($req1, $token, 'main');
        $this->assertSame('/admin/home.html', $resp1->getTargetUrl());

        // halaman yang tadinya diminta sebelum login (disimpan firewall di session)
        $req2 = Request::create('/login', 'POST');
        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/admin/bank.html');
        $req2->setSession($session);
        $resp2 = $auth->onAuthenticationSuccess($req2, $token, 'main');
        $this->assertSame('/admin/bank.html', $resp2->getTargetUrl());
        $this->assertFalse($session->has('_security.main.target_path'));

        // cookie redirect_path dari UnauthorizedEventListener
        $req3 = Request::create('/login', 'POST', [], ['redirect_path' => '/admin/item.html']);
        $req3->setSession(new Session(new MockArraySessionStorage()));
        $resp3 = $auth->onAuthenticationSuccess($req3, $token, 'main');
        $this->assertSame('/admin/item.html', $resp3->getTargetUrl());

        // SSO login with grant_type & client_id
        $req4 = Request::create('/login?grant_type=code&client_id=123', 'POST');
        $resp4 = $auth->onAuthenticationSuccess($req4, $token, 'main');
        $this->assertSame('http://localhost/sso', $resp4->getTargetUrl());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function redirectCookieProvider(): iterable
    {
        yield 'path di situs ini' => ['/admin/item.html', '/admin/item.html'];
        yield 'url lengkap host sama' => ['http://localhost/admin/item.html', 'http://localhost/admin/item.html'];
        yield 'situs lain' => ['https://evil.example/login', '/admin/home.html'];
        yield 'tanpa skema' => ['//evil.example/login', '/admin/home.html'];
        yield 'garis miring terbalik' => ['/\\evil.example/login', '/admin/home.html'];
        yield 'skema javascript' => ['javascript:alert(1)', '/admin/home.html'];
        yield 'host mirip' => ['http://localhost.evil.example/', '/admin/home.html'];
    }

    #[DataProvider('redirectCookieProvider')]
    public function testRedirectCookieOnlyFollowedWhenItStaysOnThisSite(string $cookie, string $expected): void
    {
        $routing = $this->createMock(RoutingConfigurationFactoryInterface::class);
        $routing->method('getLoginSuccessRedirectPath')->willReturn('admin_home');
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->with('admin_home')->willReturn('/admin/home.html');
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn(['ROLE_USER']);

        $request = Request::create('http://localhost/login', 'POST', [], ['redirect_path' => $cookie]);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $this->createAuthenticator(urlGen: $urlGen, routing: $routing)->onAuthenticationSuccess($request, $token, 'main');

        $this->assertSame($expected, $response->getTargetUrl());
    }

    public function testStartRedirectsToBundleLoginRouteAsRelativePath(): void
    {
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->with('kmj_user_login')->willReturn('/login');

        $resp = $this->createAuthenticator(null, $urlGen)->start(Request::create('/admin/bank.html'));

        $this->assertSame('/login', $resp->getTargetUrl());
    }

    public function testAuthenticateRejectsInactiveUser(): void
    {
        $user = new DefaultUser();
        $user->setUsername('john');
        $user->setIsActive(false);

        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('loadUserByIdentifier')->with('john')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 'john', 'password' => 'secret123']);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $request = Request::create('/login', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->expectException(BadCredentialsException::class);
        $this->createAuthenticator(null, null, $provider, $formFactory)->authenticate($request);
    }

    public function testOnAuthenticationFailure(): void
    {
        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->with('kmj_user_login')->willReturn('/login');

        $auth = $this->createAuthenticator(null, $urlGen);

        $req = Request::create('/login', 'POST');
        $session = new Session(new MockArraySessionStorage());
        $req->setSession($session);

        $ex = new AuthenticationException('Bad password');
        $resp = $auth->onAuthenticationFailure($req, $ex);

        $this->assertSame('/login', $resp->getTargetUrl());
        $this->assertSame($ex, $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR));
    }

    public function testAuthenticateSuccess(): void
    {
        $user = new DefaultUser();
        $user->setUsername('john');
        $user->setIsActive(true);

        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('loadUserByIdentifier')->with('john')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 'john', 'password' => 'secret123']);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $auth = $this->createAuthenticator(null, null, $provider, $formFactory);

        $request = Request::create('/login', 'POST');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $passport = $auth->authenticate($request);
        $this->assertSame('john', $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateAddsPasswordUpgradeBadge(): void
    {
        $user = new DefaultUser();
        $user->setUsername('john');
        $user->setIsActive(true);
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('loadUserByIdentifier')->willReturn($user);
        $provider = $this->createMockForIntersectionOfInterfaces([UserProviderInterface::class, PasswordUpgraderInterface::class]);
        $provider->method('loadUserByIdentifier')->willReturn($user);
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 'john', 'password' => 'secret']);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->method('create')->willReturn($form);

        $request = Request::create('/login', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $passport = $this->createAuthenticator(null, null, $provider, $factory)->authenticate($request);

        self::assertTrue($passport->hasBadge(\Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge::class));
    }

    public function testAuthenticateUnsubmittedForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $auth = $this->createAuthenticator(null, null, null, $formFactory);

        $request = Request::create('/login', 'POST');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('please submit form');
        $auth->authenticate($request);
    }

    public function testAuthenticateInvalidForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $error = new FormError('Bad captcha');
        $form->method('getErrors')->willReturn(new FormErrorIterator($form, [$error]));

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $auth = $this->createAuthenticator(null, null, null, $formFactory);

        $request = Request::create('/login', 'POST');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $auth->authenticate($request);
    }

    public function testAuthenticateUserNotFound(): void
    {
        $provider = $this->createMock(UserProviderInterface::class);
        $provider->method('loadUserByIdentifier')->with('unknown')->willThrowException(new UserNotFoundException());

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 'unknown', 'password' => 'secret']);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $auth = $this->createAuthenticator(null, null, $provider, $formFactory);

        $request = Request::create('/login', 'POST');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $this->expectException(UserNotFoundException::class);
        $auth->authenticate($request);
    }


    public function testAuthenticateEmptyUsername(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => '   ', 'password' => 'secret']);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $auth = $this->createAuthenticator(null, null, null, $formFactory);
        $request = Request::create('/login', 'POST');

        $this->expectException(BadCredentialsException::class);
        $auth->authenticate($request);
    }

    public function testAuthenticateEmptyPassword(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 'john', 'password' => '']);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $auth = $this->createAuthenticator(null, null, null, $formFactory);
        $request = Request::create('/login', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->expectException(BadCredentialsException::class);
        $auth->authenticate($request);
    }

    public function testAuthenticateRejectsNonStringUsername(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 123, 'password' => 'secret']);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->method('create')->willReturn($form);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $request = Request::create('/login', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->createAuthenticator(null, null, null, $factory)->authenticate($request);
    }

    public function testAuthenticateRejectsNonStringPassword(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn(['username' => 'john', 'password' => 123]);
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->method('create')->willReturn($form);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $request = Request::create('/login', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->createAuthenticator(null, null, null, $factory)->authenticate($request);
    }

}
