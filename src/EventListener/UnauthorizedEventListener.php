<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Kematjaya\UserBundle\EventListener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

/**
 * Description of UnauthorizedEventListener
 *
 * @author guest
 */
class UnauthorizedEventListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->getThrowable() instanceof InsufficientAuthenticationException) {
            $this->createResponse($event);
        }

        if ($event->getThrowable()->getPrevious() instanceof InsufficientAuthenticationException) {
            $this->createResponse($event);
        }
    }

    protected function createResponse(ExceptionEvent $event): void
    {
        $configs = $this->parameterBag->get("user");
        $request = $event->getRequest();
        // stateless firewalls (e.g. an API) have no session
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add("error", $event->getThrowable()->getMessage());
        }

        $redirectPath = $configs['route']['unauthorize_redirect_path'];
        $response = new RedirectResponse(
            $this->urlGenerator->generate($redirectPath)
        );

        $route = $request->attributes->get("_route");
        if (null !== $route) {
            $url = $this->urlGenerator->generate(
                $route,
                $request->attributes->get("_route_params") ?? [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $response->headers->setCookie(
                Cookie::create(
                    "redirect_path",
                    $url
                )
            );
        }

        $event->setResponse($response);
    }
}
