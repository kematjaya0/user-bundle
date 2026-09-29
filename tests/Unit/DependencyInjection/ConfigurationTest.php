<?php

namespace Kematjaya\UserBundle\Tests\Unit\DependencyInjection;

use Kematjaya\UserBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        $this->assertTrue($config['use_captcha']);
        $this->assertSame('ROLE_ADMINISTRATOR', $config['reset_password_role']);
        $this->assertSame('kmj_user_login', $config['route']['login']);
        $this->assertSame('kmj_user_login', $config['route']['unauthorize_redirect_path']);
        $this->assertSame('homepage', $config['route']['login_success']['default']);
        $this->assertFalse($config['route']['login_success']['back_to_previous']);
        $this->assertSame([], $config['route']['login_success']['roles']);
        $this->assertSame('homepage', $config['route']['reset_password_success']['default']);
    }
}
