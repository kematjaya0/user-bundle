<?php

/**
 * This file is part of the e-reporting.
 */

namespace Kematjaya\UserBundle\Subscriber\Builder;

use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @package App\Subscriber\Builder
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserTypeSubscriberBuilder implements UserTypeSubscriberBuilderInterface
{
    
    /**
     * 
     * @var Collection
     */
    private $subscribers;
    
    public function __construct() 
    {
        $this->subscribers = new ArrayCollection();
    }
    
    public function addSubscriber(UserTypeSubscriberInterface $subscriber): UserTypeSubscriberBuilderInterface
    {
        $this->subscribers->add($subscriber);
        
        return $this;
    }
    
    public function getSubscribers():Collection
    {
        return $this->subscribers;
    }
    
    public function getSubscriber(string $className): ?UserTypeSubscriberInterface
    {
        $subscribers = $this->subscribers->filter(function(UserTypeSubscriberInterface $subscriber) use ($className) {
            return $subscriber->isSupported($className);
        });
        
        $subscriber = $subscribers->first();
        if(!$subscriber instanceof UserTypeSubscriberInterface) {
            
            throw new \Exception(sprintf("subcsriber for class %s not exist", $className));
        }
        
        return $subscriber;
    }
}
