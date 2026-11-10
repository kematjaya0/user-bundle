<?php

/**
 * This file is part of the Kematjaya\UserBundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Ramsey\Uuid\Uuid;

/**
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class ResettingPassword implements ResettingPasswordInterface
{
    private ?string $password = null;

    private ?string $retype_password = null;

    public function __construct(private KmjUserInterface $user)
    {
    }

    public function getId(): mixed
    {
        if (class_exists(Uuid::class)) {
            return Uuid::fromDateTime(new \DateTime());
        }

        return \Symfony\Component\Uid\Uuid::v7();
    }

    public function getUser(): KmjUserInterface
    {
        return $this->user;
    }

    public function getUsername(): ?string
    {
        return $this->user->getUsername();
    }

    public function setUsername(string $usermane)
    {

    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRetypePassword(): ?string
    {
        return $this->retype_password;
    }

    public function setUser(KmjUserInterface $user): ResettingPasswordInterface
    {
        $this->user = $user;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setRetypePassword(string $retypePassword): self
    {
        $this->retype_password = $retypePassword;

        return $this;
    }


}
