<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Config;

/**
 * @package Kematjaya\UserBundle\Config
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface RoutingConfigurationFactoryInterface 
{
    public function getLoginSuccessRedirectPath(array $roles):string;
    
    public function getResetPasswordRedirectPath(array $roles):string;
}
