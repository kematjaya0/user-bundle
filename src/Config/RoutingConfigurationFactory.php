<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Config;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @package Kematjaya\UserBundle\Config
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class RoutingConfigurationFactory implements RoutingConfigurationFactoryInterface
{
    private readonly array $configs;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $configs = $parameterBag->get('user');
        $this->configs = $configs['route'];
    }

    public function getLoginSuccessRedirectPath(array $roles): string
    {
        $config = $this->configs;
        $redirect = $config['auth_success'] ?? null;
        if (null !== $redirect) {

            return $redirect;
        }

        return $this->getPath($roles, 'login_success');
    }

    public function getResetPasswordRedirectPath(array $roles): string
    {
        $config = $this->configs;
        $redirect = $config['reset_password_redirect_path'] ?? null;
        if (null !== $redirect) {

            return $redirect;
        }

        return $this->getPath($roles, 'reset_password_success');
    }

    protected function getPath(array $roles, string $name): string
    {
        $redirects = $this->configs[$name];
        if (empty($redirects['roles'])) {

            return $redirects['default'];
        }

        $redirectPath = array_filter($redirects['roles'], fn(array $value): bool => $value['role'] === end($roles));

        if (empty($redirectPath)) {
            return $redirects['default'];
        }

        $paths = end($redirectPath);

        return $paths['path'];
    }
}
