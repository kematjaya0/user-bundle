<?php

namespace Kematjaya\User\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class KmjSecurityController extends AbstractController
{
    protected $authenticationUtils;
    
    public function __construct(AuthenticationUtils $authenticationUtils) 
    {
        $this->authenticationUtils = $authenticationUtils;
    }
    
    public function login(): Response
    {
        if ($this->getUser()) 
        {
            $this->addFlash("info", 'wellcome back : '. $this->getUser()->getUsername());
            $config = $this->container->getParameter('kmj_user');
            if(!isset($config['route']['auth_success']))
            {
                throw new \Exception("please set router.auth_succes key under kmj_user config");
            }
            
            return $this->redirectToRoute($config['route']['auth_success']);
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('@KmjUser/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
