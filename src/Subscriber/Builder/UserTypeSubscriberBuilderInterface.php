<?php

/**
 * This file is part of the e-reporting.
 */

namespace Kematjaya\UserBundle\Subscriber\Builder;

use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use Doctrine\Common\Collections\Collection;

/**
 * @package App\Subscriber\Builder
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface UserTypeSubscriberBuilderInterface 
{
    
    public function addSubscriber(UserTypeSubscriberInterface $subscriber): self;
    
    public function getSubscribers():Collection;
    
    public function getSubscriber(string $className): ?UserTypeSubscriberInterface;
    
}
