<?php

namespace Kematjaya\UserBundle\Tests\Unit\Config;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RoutingConfigurationFactoryTest extends TestCase
{
    public function testGetLoginSuccessRedirectPathDirectConfig(): void
    {
        $bag = new ParameterBag([
            'user' => [
                'route' => [
                    'auth_success' => 'dashboard_direct',
                    'reset_password_redirect_path' => 'reset_direct',
                ],
            ],
        ]);

        $factory = new RoutingConfigurationFactory($bag);
        $this->assertSame('dashboard_direct', $factory->getLoginSuccessRedirectPath(['ROLE_USER']));
        $this->assertSame('reset_direct', $factory->getResetPasswordRedirectPath(['ROLE_USER']));
    }

    public function testGetRedirectPathFromRoles(): void
    {
        $bag = new ParameterBag([
            'user' => [
                'route' => [
                    'login_success' => [
                        'default' => 'homepage',
                        'roles' => [
                            ['role' => 'ROLE_ADMIN', 'path' => 'admin_dashboard'],
                            ['role' => 'ROLE_STAFF', 'path' => 'staff_dashboard'],
                        ],
                    ],
                    'reset_password_success' => [
                        'default' => 'login_page',
                        'roles' => [],
                    ],
                ],
            ],
        ]);

        $factory = new RoutingConfigurationFactory($bag);
        $this->assertSame('admin_dashboard', $factory->getLoginSuccessRedirectPath(['ROLE_USER', 'ROLE_ADMIN']));
        $this->assertSame('staff_dashboard', $factory->getLoginSuccessRedirectPath(['ROLE_STAFF']));
        $this->assertSame('homepage', $factory->getLoginSuccessRedirectPath(['ROLE_UNKNOWN']));
        $this->assertSame('login_page', $factory->getResetPasswordRedirectPath(['ROLE_ADMIN']));
    }
}
