<?php

namespace Kematjaya\UserBundle\Tests\Unit\Security;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Security\FormLoginAuthenticator;
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
    ): FormLoginAuthenticator {
        $httpUtils ??= $this->createMock(HttpUtils::class);
        $urlGen ??= $this->createMock(UrlGeneratorInterface::class);
        $provider ??= $this->createMock(UserProviderInterface::class);
        $formFactory ??= $this->createMock(FormFactoryInterface::class);

        return new FormLoginAuthenticator($httpUtils, $urlGen, $provider, $formFactory);
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
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('generateUri')->with($this->anything(), 'dashboard_index')->willReturn('http://localhost/dashboard');

        $urlGen = $this->createMock(UrlGeneratorInterface::class);
        $urlGen->method('generate')->with('sso_authorize', $this->anything())->willReturn('http://localhost/sso');

        $auth = $this->createAuthenticator($httpUtils, $urlGen);

        $token = $this->createMock(TokenInterface::class);

        // normal login
        $req1 = Request::create('/login', 'POST');
        $resp1 = $auth->onAuthenticationSuccess($req1, $token, 'main');
        $this->assertSame('http://localhost/dashboard', $resp1->getTargetUrl());

        // SSO login with grant_type & client_id
        $req2 = Request::create('/login?grant_type=code&client_id=123', 'POST');
        $resp2 = $auth->onAuthenticationSuccess($req2, $token, 'main');
        $this->assertSame('http://localhost/sso', $resp2->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils->method('generateUri')->with($this->anything(), 'app_login')->willReturn('http://localhost/login');

        $auth = $this->createAuthenticator($httpUtils);

        $req = Request::create('/login', 'POST');
        $session = new Session(new MockArraySessionStorage());
        $req->setSession($session);

        $ex = new AuthenticationException('Bad password');
        $resp = $auth->onAuthenticationFailure($req, $ex);

        $this->assertSame('http://localhost/login', $resp->getTargetUrl());
        $this->assertSame($ex, $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR));
    }

    public function testAuthenticateSuccess(): void
    {
        $user = new DefaultUser();
        $user->setUsername('john');

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
