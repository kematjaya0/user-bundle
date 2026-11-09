<?php

namespace Kematjaya\UserBundle\Tests\Unit\EventListener;

use Kematjaya\UserBundle\EventListener\UnauthorizedEventListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class UnauthorizedEventListenerTest extends TestCase
{
    public function testOnKernelExceptionWithInsufficientAuth(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(fn (string $route, array $params = []): string => '/' . $route);

        $bag = new ParameterBag([
            'user' => [
                'route' => ['unauthorize_redirect_path' => 'login_route'],
            ],
        ]);

        $listener = new UnauthorizedEventListener($urlGenerator, $bag);

        $request = new Request([], [], ['_route' => 'secret_page', '_route_params' => ['id' => 1]]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new InsufficientAuthenticationException('Not authenticated'),
        );

        $listener->onKernelException($event);

        $this->assertNotNull($event->getResponse());
        $this->assertTrue($event->getResponse()->isRedirect('/login_route'));
        $this->assertTrue($session->getFlashBag()->has('error'));
    }

    public function testOnKernelExceptionWithPreviousInsufficientAuth(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(fn (string $route): string => '/' . $route);

        $bag = new ParameterBag([
            'user' => [
                'route' => ['unauthorize_redirect_path' => 'login_route'],
            ],
        ]);

        $listener = new UnauthorizedEventListener($urlGenerator, $bag);

        $request = new Request([], [], ['_route' => 'secret_page', '_route_params' => []]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $prev = new InsufficientAuthenticationException('Inner auth error');
        $wrapper = new \RuntimeException('Wrapper error', 0, $prev);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $wrapper,
        );

        $listener->onKernelException($event);

        $this->assertNotNull($event->getResponse());
        $this->assertTrue($event->getResponse()->isRedirect('/login_route'));
    }

    public function testOnKernelExceptionIgnoredForOtherExceptions(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $bag = new ParameterBag(['user' => ['route' => ['unauthorize_redirect_path' => 'login_route']]]);
        $listener = new UnauthorizedEventListener($urlGenerator, $bag);

        $request = new Request();
        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \RuntimeException('Something else'),
        );

        $listener->onKernelException($event);
        $this->assertNull($event->getResponse());
    }
}
