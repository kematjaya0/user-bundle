<?php

/**
 * This file is part of the Kematjaya\UserBundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Symfony\Component\Uid\Uuid;

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
        return Uuid::v7();
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

        $this->user->setPassword($password);

        return $this;
    }

    public function setRetypePassword(string $retypePassword): self
    {
        $this->retype_password = $retypePassword;

        return $this;
    }


}
