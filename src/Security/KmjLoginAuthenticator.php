<?php

namespace Kematjaya\UserBundle\Security;

use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class KmjLoginAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'kmj_user_login';

    private $config;
    private $kmjUserRepo;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $loginRoute;
    private $authSuccessRoute;

    public function __construct(
            ContainerBagInterface $containerBag,
            KmjUserRepoInterface $kmjUserRepo, 
            UrlGeneratorInterface $urlGenerator, 
            CsrfTokenManagerInterface $csrfTokenManager, 
            UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->config = $containerBag->get('user');
        $this->kmjUserRepo = $kmjUserRepo;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->loginRoute = $this->config['route']['login'];
        $this->authSuccessRoute = $this->config['route']['auth_success'];
    }

    public function supports(Request $request)
    {
        return $this->loginRoute === $request->attributes->get('_route')
            && $request->isMethod(Request::METHOD_POST);
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->kmjUserRepo->findOneByUsernameAndActive($credentials['username']);

        if (!$user) {
            // fail authentication with a custom error
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
        $this->saveTargetPath($request->getSession(), $providerKey, $this->urlGenerator->generate($this->authSuccessRoute));
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate($this->loginRoute);
    }
}
