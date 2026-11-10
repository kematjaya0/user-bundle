<?php

namespace Kematjaya\UserBundle\Security;

use Kematjaya\UserBundle\Form\LoginType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * @package Kematjaya\UserBundle\Security
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class FormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    private array $options;
    public function __construct(private readonly HttpUtils $httpUtils, private readonly UrlGeneratorInterface $urlGenerator, private readonly UserProviderInterface $userProvider, private readonly FormFactoryInterface $formFactory)
    {
        $this->options = array_merge([
            'username_parameter' => '_username',
            'password_parameter' => '_password',
            'check_path' => '/login',
            "login_path" => "app_login",
            'post_only' => true,
            'form_only' => true,
            'enable_csrf' => true,
            'csrf_parameter' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
        ], []);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->httpUtils->generateUri($request, $this->options['login_path']);
    }

    public function supports(Request $request): bool
    {
        if (Request::METHOD_POST !== $request->getMethod()) {
            return false;
        }

        if (!$this->httpUtils->checkRequestPath($request, $this->options['check_path'])) {
            return false;
        }

        return $this->options['form_only'] ? 'form' === $request->getContentTypeFormat() : true;
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        $user = $this->userProvider->loadUserByIdentifier($credentials["username"]);

        $userBadge = new UserBadge($credentials['username'], fn (): UserInterface => $this->userProvider->loadUserByIdentifier($credentials["username"]));
        $passport = new Passport($userBadge, new PasswordCredentials($credentials['password']), [new RememberMeBadge()]);

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userProvider));
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $params = $request->query->all();
        if (isset($params["grant_type"]) && isset($params["client_id"])) {
            return new RedirectResponse(
                $this->urlGenerator->generate("sso_authorize", $params),
            );
        }

        return new RedirectResponse(
            $this->httpUtils->generateUri($request, "dashboard_index"),
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse(
            $this->getLoginUrl($request),
        );
    }

    private function getCredentials(Request $request): array
    {
        $credentials = [];
        $form = $this->formFactory->create(LoginType::class);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            throw new CustomUserMessageAuthenticationException(
                "please submit form",
            );
        }

        if (!$form->isValid()) {
            throw new CustomUserMessageAuthenticationException(
                $this->getErrors($form),
            );
        }

        $credentials = $form->getData();
        if (!\is_string($credentials['username']) && !$credentials['username'] instanceof \Stringable) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        if ('' === $credentials['username']) {
            throw new BadCredentialsException(sprintf('The key "%s" must be a non-empty string.', $this->options['username_parameter']));
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials['username']);

        if (!\is_string($credentials['password']) && (!\is_object($credentials['password']) || !method_exists($credentials['password'], '__toString'))) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.', $this->options['password_parameter'], \gettype($credentials['password'])));
        }

        if ('' === (string) $credentials['password']) {
            throw new BadCredentialsException(sprintf('The key "%s" must be a non-empty string.', $this->options['password_parameter']));
        }

        return $credentials;
    }

    protected function getErrors(FormInterface $form): ?string
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = sprintf("%s %s", $error->getOrigin() ? $error->getOrigin()->getName() . ': ' : '', $error->getMessage());
        }

        return implode(", ", $errors);
    }
}
