<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Kematjaya\UserBundle\EventListener;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Description of UnauthorizedEventListener
 *
 * @author guest
 */
class UnauthorizedEventListener 
{
    /**
     * 
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    
    public function __construct(UrlGeneratorInterface $urlGenerator) 
    {
        $this->urlGenerator = $urlGenerator;
    }
    
    public function onKernelException(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof InsufficientAuthenticationException) {
            $this->createResponse($event);
        }
        
        if ($event->getThrowable()->getPrevious() instanceof InsufficientAuthenticationException) {
            $this->createResponse($event);
        }
    }
    
    protected function createResponse(ExceptionEvent $event)
    {
        $event->getRequest()->getSession()->getFlashBag()->add("error", $event->getThrowable()->getMessage());
        $response = new RedirectResponse(
            $this->urlGenerator->generate("kmj_user_login")
        );
        $response->headers->setCookie(
            Cookie::create(
                "redirect_path", 
                $event->getRequest()->attributes->get("_route")
            )
        );
        $event->setResponse($response);
    }
}
