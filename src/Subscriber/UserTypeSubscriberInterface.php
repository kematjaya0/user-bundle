<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package Kematjaya\UserBundle\Subscriber
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface UserTypeSubscriberInterface extends EventSubscriberInterface
{
    
    public function getRoles():array;
}