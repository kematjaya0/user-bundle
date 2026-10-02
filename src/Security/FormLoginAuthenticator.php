<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Security;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Form\LoginType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * @package Kematjaya\UserBundle\Security
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class FormLoginAuthenticator extends AbstractAuthenticator
{
    /**
     * Same values as Security::AUTHENTICATION_ERROR / LAST_USERNAME (Symfony 5.4)
     * and SecurityRequestAttributes (Symfony 6.2+), without depending on either class.
     */
    public const AUTHENTICATION_ERROR = '_security.last_error';
    public const LAST_USERNAME = '_security.last_username';

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RoutingConfigurationFactoryInterface $routingConfigurationFactory,
        private readonly KmjUserRepoInterface $kmjUserRepository,
    ) {}

    /**
     * Unknown or inactive users and wrong passwords all end as the same
     * "Invalid credentials." error (security.hide_user_not_found), so the
     * login page does not reveal which usernames exist.
     */
    public function authenticate(Request $request): Passport
    {
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            throw new CustomUserMessageAuthenticationException(
                "please submit form"
            );
        }

        $credentials = $form->getData();
        if ($request->hasSession()) {
            $request->getSession()->set(self::LAST_USERNAME, $credentials['username'] ?? '');
        }

        if (!$form->isValid()) {
            throw new CustomUserMessageAuthenticationException(
                $this->getErrors($form)
            );
        }

        return new Passport(
            new UserBadge((string) $credentials['username'], fn(string $identifier): ?KmjUserInterface => $this->kmjUserRepository->findOneByUsernameAndActive($identifier)),
            new PasswordCredentials((string) $credentials['password'])
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(self::AUTHENTICATION_ERROR, $exception);
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $defaultUrl = $this->urlGenerator->generate(
            $this->routingConfigurationFactory->getLoginSuccessRedirectPath($token->getRoleNames())
        );
        $targetPath = $request->cookies->has("redirect_path") ? $request->cookies->get("redirect_path") : $defaultUrl;
        $response = new RedirectResponse(
            $targetPath
        );
        if ($request->cookies->has("redirect_path")) {
            $response->headers->clearCookie("redirect_path");
        }

        return $response;
    }

    public function supports(Request $request): ?bool
    {
        return "kmj_user_login" === $request->attributes->get("_route") && Request::METHOD_POST === $request->getMethod();
    }

    protected function createForm(string $className, mixed $data = null): FormInterface
    {
        return $this->formFactory->create($className, $data);
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
