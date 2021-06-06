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
    /**
     * 
     * @var array
     */
    private $configs;
    
    public function __construct(ParameterBagInterface $parameterBag) 
    {
        $this->configs = $parameterBag->get('user');
    }
    
    public function getLoginSuccessRedirectPath(array $roles):string
    {
        $config = $this->configs;
        $redirect = isset($config['auth_success']) ? $config['auth_success'] : null;
        if (null !== $redirect) {
            
            return $redirect;
        }  
        
        return $this->getPath($roles, 'login_success');
    }
    
    public function getResetPasswordRedirectPath(array $roles):string
    {
        $config = $this->configs;
        $redirect = isset($config['reset_password_redirect_path']) ? $config['reset_password_redirect_path'] : null;
        if (null !== $redirect) {
            
            return $redirect;
        }
        
        return $this->getPath($roles, 'reset_password_success');
    }
    
    protected function getPath(array $roles, string $name)
    {
        $redirects = $this->configs[$name];
        if (empty($redirects['roles'])) {
            
            return $redirects['default'];
        }
        
        $redirectPath = array_filter($redirects['roles'], function ($value) use ($roles) {
            return $value['role'] === end($roles);
        });
        
        if (empty($redirectPath)) {
            return $redirects['default'];
        }
        
        $paths = end($redirectPath);
        
        return $paths['path'];
    }
}
