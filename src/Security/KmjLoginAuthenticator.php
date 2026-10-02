<?php

namespace Kematjaya\UserBundle\Security;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Kematjaya\UserBundle\Form\LoginType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * @deprecated since version 4.2.0, use Kematjaya\UserBundle\Security\FormLoginAuthenticator instead
 */
class KmjLoginAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'kmj_user_login';

    private readonly array $config;

    private readonly string $loginRoute;

    private ?string $error = null;

    public function __construct(
        ContainerBagInterface $containerBag,
        private readonly ContainerInterface $container,
        private readonly KmjUserRepoInterface $kmjUserRepo,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly RoutingConfigurationFactoryInterface $routingConfigurationFactory
    ) {
        $this->config = $containerBag->get('user');
        $this->loginRoute = $this->config['route']['login'];
    }

    public function supports(Request $request): bool
    {
        return $this->loginRoute === $request->attributes->get('_route')
            && $request->isMethod(Request::METHOD_POST);
    }

    public function getCredentials(Request $request): mixed
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

    public function getUser(mixed $credentials, UserProviderInterface $userProvider): ?UserInterface
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

    public function checkCredentials(mixed $credentials, UserInterface $user): bool
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword(mixed $credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        $redirectPath = $this->routingConfigurationFactory->getLoginSuccessRedirectPath($token->getUser()->getRoles());
        $this->saveTargetPath($request->getSession(), $providerKey, $this->urlGenerator->generate($redirectPath));
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        if ($targetPath) {

            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate($this->loginRoute);
    }

    protected function createForm(string $className, mixed $data = null): FormInterface
    {
        return $this->container->get('form.factory')->create($className, $data);
    }

    protected function getErrors(FormInterface $form): ?string
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
