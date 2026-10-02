<?php

namespace Kematjaya\UserBundle\Tests\Unit\EventListener;

use Kematjaya\UserBundle\EventListener\UnauthorizedEventListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class UnauthorizedEventListenerTest extends TestCase
{
    private function createListener(): UnauthorizedEventListener
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(fn(string $name, array $params = [], int $type = UrlGeneratorInterface::ABSOLUTE_PATH): string => (UrlGeneratorInterface::ABSOLUTE_URL === $type ? 'http://localhost' : '') . '/' . $name);

        return new UnauthorizedEventListener(
            $urlGenerator,
            new ParameterBag(['user' => ['route' => ['unauthorize_redirect_path' => 'kmj_user_login']]])
        );
    }

    private function createEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testRedirectsToLoginAndRemembersTarget(): void
    {
        $request = Request::create('/user/profile');
        $request->attributes->set('_route', 'kmj_user_profile');
        $request->attributes->set('_route_params', []);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $event = $this->createEvent($request, new InsufficientAuthenticationException('login dulu'));

        $this->createListener()->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/kmj_user_login', $response->getTargetUrl());
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('redirect_path', $cookies[0]->getName());
        $this->assertSame('http://localhost/kmj_user_profile', $cookies[0]->getValue());
        $this->assertSame(['login dulu'], $request->getSession()->getFlashBag()->get('error'));
    }

    public function testHandlesWrappedException(): void
    {
        $request = Request::create('/user/profile');
        $request->attributes->set('_route', 'kmj_user_profile');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $event = $this->createEvent($request, new \RuntimeException('x', 0, new InsufficientAuthenticationException()));

        $this->createListener()->onKernelException($event);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testIgnoresOtherExceptions(): void
    {
        $event = $this->createEvent(Request::create('/'), new \RuntimeException('x'));

        $this->createListener()->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Stateless firewalls (API) have no session and a request may lack _route.
     */
    public function testWorksWithoutSessionAndRoute(): void
    {
        $event = $this->createEvent(Request::create('/api/x'), new InsufficientAuthenticationException());

        $this->createListener()->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame([], $response->headers->getCookies());
    }
}
