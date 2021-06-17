<?php

namespace Kematjaya\UserBundle\Security;

use Kematjaya\UserBundle\Form\LoginType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;

class KmjLoginAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'kmj_user_login';

    /**
     * 
     * @var array
     */
    private $config;
    
    /**
     * 
     * @var KmjUserRepoInterface
     */
    private $kmjUserRepo;
    
    /**
     * 
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    
    /**
     * 
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;
    
    /**
     * 
     * @var UserPasswordHasherInterface
     */
    private $passwordEncoder;
    
    /**
     * 
     * @var ContainerInterface
     */
    private $container;
    
    private $loginRoute;
    
    /**
     * 
     * @var string
     */
    private $error;

    /**
     * 
     * @var RoutingConfigurationFactoryInterface
     */
    private $routingConfigurationFactory;
    
    public function __construct(
        ContainerBagInterface $containerBag,
        ContainerInterface $container,
        KmjUserRepoInterface $kmjUserRepo, 
        UrlGeneratorInterface $urlGenerator, 
        CsrfTokenManagerInterface $csrfTokenManager, 
        UserPasswordHasherInterface $passwordEncoder,
            RoutingConfigurationFactoryInterface $routingConfigurationFactory
    ) {
        $this->container = $container;
        $this->config = $containerBag->get('user');
        $this->kmjUserRepo = $kmjUserRepo;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->loginRoute = $this->config['route']['login'];
        $this->routingConfigurationFactory = $routingConfigurationFactory;
    }

    public function supports(Request $request)
    {
        return $this->loginRoute === $request->attributes->get('_route')
            && $request->isMethod(Request::METHOD_POST);
    }

    public function getCredentials(Request $request)
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            
            $this->error = $this->getErrors($form);
        }
        
        $credentials = $form->getData();
        
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null !== $this->error) {
            throw new CustomUserMessageAuthenticationException($this->error);
        }
        
        $user = $this->kmjUserRepo->findOneByUsernameAndActive($credentials['username']);
        if (!$user) {
            
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $redirectPath = $this->routingConfigurationFactory->getLoginSuccessRedirectPath($token->getUser()->getRoles());
        $this->saveTargetPath($request->getSession(), $providerKey, $this->urlGenerator->generate($redirectPath));
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        if ($targetPath) {
            
            return new RedirectResponse($targetPath);
        }

        throw new RedirectResponse('homepage');
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate($this->loginRoute);
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
