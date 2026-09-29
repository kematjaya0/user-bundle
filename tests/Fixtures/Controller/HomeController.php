<?php

namespace Kematjaya\UserBundle\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect target after login in the test kernel.
 */
class HomeController
{
    public function __invoke(): Response
    {
        return new Response('home');
    }
}
