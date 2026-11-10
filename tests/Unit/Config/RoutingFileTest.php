<?php

declare(strict_types=1);

namespace Kematjaya\UserBundle\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\PhpFileLoader;

/**
 * Memuat berkas routing bundle yang dipakai aplikasi (all.php), supaya salah
 * argumen import() langsung kelihatan, bukan baru saat aplikasi di-boot.
 */
final class RoutingFileTest extends TestCase
{
    public function testAllRoutesAreLoadedWithUserPrefix(): void
    {
        $dir = \dirname(__DIR__, 3) . '/src/Resources/config/routing';
        $routes = (new PhpFileLoader(new FileLocator($dir)))->load('all.php');

        self::assertSame('/login', $routes->get('kmj_user_login')?->getPath());
        self::assertSame('/logout', $routes->get('kmj_user_logout')?->getPath());
        self::assertSame('/user/change-password', $routes->get('kmj_user_change_password')?->getPath());
        self::assertSame('/user/profile', $routes->get('kmj_user_profile')?->getPath());
        self::assertSame('/user/{identityNumber}/reset-password', $routes->get('kmj_user_reset_password')?->getPath());
    }
}
