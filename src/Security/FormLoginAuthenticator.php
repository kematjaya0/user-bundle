<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Security;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Kematjaya\UserBundle\Form\LoginType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @package Kematjaya\UserBundle\Security
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class FormLoginAuthenticator extends AbstractAuthenticator 
{
    /**
     * 
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * 
     * @var KmjUserRepoInterface
     */
    private $kmjUserRepository;
    
    /**
     * 
     * @var PasswordHasherFactoryInterface
     */
    private $passwordHasherFactory;
    
    /**
     * 
     * @var RoutingConfigurationFactoryInterface
     */
    private $routingConfigurationFactory;
    
    /**
     * 
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    
    public function __construct(ContainerInterface $container, UrlGeneratorInterface $urlGenerator, RoutingConfigurationFactoryInterface $routingConfigurationFactory, PasswordHasherFactoryInterface $passwordHasherFactory, KmjUserRepoInterface $kmjUserRepository) 
    {
        $this->container = $container;
        $this->kmjUserRepository = $kmjUserRepository;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->routingConfigurationFactory = $routingConfigurationFactory;
        $this->urlGenerator = $urlGenerator;
    }
    
    public function authenticate(Request $request): PassportInterface 
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            throw new CustomUserMessageAuthenticationException(
                "please submit form"
            );
        }
        
        if (!$form->isValid()) {
            throw new CustomUserMessageAuthenticationException(
                $this->getErrors($form)
            );
        }
        
        $credentials = $form->getData();
        $user = $this->kmjUserRepository->findOneByUsernameAndActive($credentials['username']);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException(
                sprintf("user not found: '%s'", $credentials['username'])
            );
        }
        
        $hasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if (!$hasher->verify($user->getPassword(), $credentials['password'])) {
            throw new CustomUserMessageAuthenticationException(
                sprintf("wrong password")
            );
        }
        
        return new SelfValidatingPassport(
            new UserBadge($user->getUsername(), function (string $identifier) {
                return $this->kmjUserRepository->findOneByUsernameAndActive($identifier);
            })
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response 
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response 
    {
        $targetPath = $this->routingConfigurationFactory->getLoginSuccessRedirectPath($token->getRoleNames());
        
        return new RedirectResponse(
            $this->urlGenerator->generate($targetPath)
        );
    }

    public function supports(Request $request): ?bool 
    {
        return Request::METHOD_POST === $request->getMethod();
    }
    
    protected function createForm(string $className, $data = null): FormInterface
    {
        return $this->container->get('form.factory')->create($className, $data);
    }
    
    protected function getErrors(FormInterface $form):?string 
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            if (!$error instanceof FormError) {
                
                continue;
            }
            $errors[] = sprintf("%s %s", $error->getOrigin() ? $error->getOrigin()->getName() . ': ' : '', $error->getMessage());
        }
        
        return implode(", ", $errors);
    }
}
