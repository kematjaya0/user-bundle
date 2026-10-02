<?php

/**
 * This file is part of the e-reporting.
 */

namespace Kematjaya\UserBundle\Subscriber\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;

/**
 * @package App\Subscriber\Builder
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserTypeSubscriberBuilder implements UserTypeSubscriberBuilderInterface
{
    private readonly ArrayCollection $subscribers;

    public function __construct()
    {
        $this->subscribers = new ArrayCollection();
    }

    public function addSubscriber(UserTypeSubscriberInterface $subscriber): UserTypeSubscriberBuilderInterface
    {
        $this->subscribers->add($subscriber);

        return $this;
    }

    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function getSubscriber(string $className): ?UserTypeSubscriberInterface
    {
        $subscribers = $this->subscribers->filter(fn(UserTypeSubscriberInterface $subscriber): bool => $subscriber->isSupported($className));

        $subscriber = $subscribers->first();

        return $subscriber instanceof UserTypeSubscriberInterface ? $subscriber : null;
    }
}
