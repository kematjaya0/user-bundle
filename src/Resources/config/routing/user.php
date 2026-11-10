<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('kmj_user_change_password', '/change-password')
        ->controller('Kematjaya\\UserBundle\\Controller\\KmjSecurityController::changePassword')
        ->methods(['GET', 'POST']);
    $routes->add('kmj_user_profile', '/profile')
        ->controller('Kematjaya\\UserBundle\\Controller\\KmjController::profile')
        ->methods(['GET']);
    $routes->add('kmj_user_reset_password', '/{identityNumber}/reset-password')
        ->controller('Kematjaya\\UserBundle\\Controller\\KmjController::resetPassword')
        ->methods(['GET', 'POST']);
};
