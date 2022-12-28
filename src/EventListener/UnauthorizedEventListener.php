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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
    
    /**
     * 
     * @var ParameterBagInterface
     */
    private $parameterBag;
    
    public function __construct(UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag) 
    {
        $this->urlGenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
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
        $configs = $this->parameterBag->get("user");
        $event->getRequest()->getSession()->getFlashBag()->add("error", $event->getThrowable()->getMessage());
        $redirectPath = $configs['route']['unauthorize_redirect_path'];
        $response = new RedirectResponse(
            $this->urlGenerator->generate($redirectPath)
        );
        
        $url = $this->urlGenerator->generate(
            $event->getRequest()->attributes->get("_route"),
            $event->getRequest()->attributes->get("_route_params"),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $response->headers->setCookie(
            Cookie::create(
                "redirect_path", 
                $url
            )
        );
        $event->setResponse($response);
    }
}
