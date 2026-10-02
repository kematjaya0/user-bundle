<?php

namespace Kematjaya\UserBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Gregwar\CaptchaBundle\GregwarCaptchaBundle;
use Kematjaya\UserBundle\Entity\KmjUser;
use Kematjaya\UserBundle\Security\FormLoginAuthenticator;
use Kematjaya\UserBundle\Tests\Fixtures\Controller\HomeController;
use Kematjaya\UserBundle\UserBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Minimal kernel for functional tests; configured in PHP so Symfony 5.4 and
 * 6.4 differences can be handled with Kernel::VERSION_ID.
 *
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new DoctrineBundle(),
            new GregwarCaptchaBundle(),
            new UserBundle(),
        ];
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/kmj-user-bundle/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/kmj-user-bundle/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $framework = [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'router' => ['utf8' => true],
            'session' => ['handler_id' => null, 'storage_factory_id' => 'session.storage.factory.mock_file'],
            'assets' => ['enabled' => true],
            'php_errors' => ['log' => true],
        ];
        if (Kernel::VERSION_ID >= 60400) {
            $framework['handle_all_throwables'] = true;
            $framework['session'] += ['cookie_secure' => 'auto', 'cookie_samesite' => 'lax'];
            $framework['uid'] = ['default_uuid_version' => 6, 'time_based_uuid_version' => 6];
        }
        $container->extension('framework', $framework);

        $security = [
            'password_hashers' => [
                KmjUser::class => ['algorithm' => 'bcrypt', 'cost' => 4],
            ],
            'role_hierarchy' => [
                'ROLE_ADMINISTRATOR' => ['ROLE_USER'],
                'ROLE_SUPER_USER' => ['ROLE_ADMINISTRATOR'],
            ],
            'providers' => [
                'kmj_user' => ['entity' => ['class' => KmjUser::class, 'property' => 'username']],
            ],
            'firewalls' => [
                'main' => [
                    'lazy' => true,
                    'provider' => 'kmj_user',
                    'custom_authenticators' => [FormLoginAuthenticator::class],
                    'logout' => ['path' => 'kmj_user_logout'],
                ],
            ],
            'access_control' => [
                ['path' => '^/user', 'roles' => 'IS_AUTHENTICATED_FULLY'],
            ],
        ];
        if (Kernel::VERSION_ID < 60000) {
            $security['enable_authenticator_manager'] = true;
        }
        $container->extension('security', $security);

        $container->extension('twig', ['strict_variables' => true]);

        $container->extension('doctrine', [
            'dbal' => ['url' => 'sqlite:///%kernel.cache_dir%/test.db'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'mappings' => [
                    'UserBundle' => [
                        'is_bundle' => true,
                        'type' => 'attribute',
                        'dir' => 'Entity',
                        'prefix' => 'Kematjaya\UserBundle\Entity',
                    ],
                ],
            ],
        ]);

        $container->extension('user', [
            'use_captcha' => false,
            'route' => [
                'login_success' => [
                    'default' => 'homepage',
                    'roles' => [
                        ['role' => 'ROLE_SUPER_USER', 'path' => 'admin_home'],
                    ],
                ],
            ],
        ]);

        $container->services()
            ->set(HomeController::class)
                ->public()
                ->autowire()
                ->tag('controller.service_arguments');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@UserBundle/Resources/config/routing/all.xml');
        $routes->add('homepage', '/')->controller(HomeController::class);
        $routes->add('admin_home', '/admin')->controller(HomeController::class);
    }
}
