<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import(__DIR__ . '/user.php')->prefix('/user');
    $routes->add('kmj_user_login', '/login')
        ->controller('Kematjaya\\UserBundle\\Controller\\KmjSecurityController::login')
        ->methods(['GET', 'POST']);
    $routes->add('kmj_user_logout', '/logout')
        ->controller('Kematjaya\\UserBundle\\Controller\\KmjSecurityController::logout')
        ->methods(['GET']);
};
