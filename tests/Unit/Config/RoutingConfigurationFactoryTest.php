<?php

namespace Kematjaya\UserBundle\Tests\Unit\Config;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactory;
use Kematjaya\UserBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RoutingConfigurationFactoryTest extends TestCase
{
    private function createFactory(array $config = []): RoutingConfigurationFactory
    {
        $processed = (new Processor())->processConfiguration(new Configuration(), [$config]);

        return new RoutingConfigurationFactory(new ParameterBag(['user' => $processed]));
    }

    public function testLoginRedirectUsesDefaultWithoutRoleMapping(): void
    {
        $this->assertSame('homepage', $this->createFactory()->getLoginSuccessRedirectPath(['ROLE_USER']));
    }

    public function testLoginRedirectUsesMappedRole(): void
    {
        $factory = $this->createFactory(['route' => ['login_success' => [
            'default' => 'homepage',
            'roles' => [['role' => 'ROLE_ADMINISTRATOR', 'path' => 'admin_home']],
        ]]]);

        $this->assertSame('admin_home', $factory->getLoginSuccessRedirectPath(['ROLE_ADMINISTRATOR']));
        $this->assertSame('homepage', $factory->getLoginSuccessRedirectPath(['ROLE_USER']));
    }

    public function testResetPasswordRedirectUsesMappedRole(): void
    {
        $factory = $this->createFactory(['route' => ['reset_password_success' => [
            'default' => 'homepage',
            'roles' => [['role' => 'ROLE_ADMINISTRATOR', 'path' => 'user_index']],
        ]]]);

        $this->assertSame('user_index', $factory->getResetPasswordRedirectPath(['ROLE_ADMINISTRATOR']));
        $this->assertSame('homepage', $factory->getResetPasswordRedirectPath([]));
    }
}
