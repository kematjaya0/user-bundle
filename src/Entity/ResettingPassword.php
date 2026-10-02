<?php

/**
 * This file is part of the Kematjaya\UserBundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class ResettingPassword implements ResettingPasswordInterface
{
    private ?string $password = null;

    private ?string $retype_password = null;

    public function __construct(private KmjUserInterface $user) {}

    public function getId(): UuidInterface
    {
        return Uuid::fromDateTime(new \DateTime());
    }

    public function getUser(): KmjUserInterface
    {
        return $this->user;
    }

    public function getUsername(): ?string
    {
        return $this->user->getUsername();
    }

    public function setUsername(string $usermane): void {}

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

    /**
     * Holds the typed plain password only. ResetPasswordType sets the hashed
     * password on the user once the form is valid.
     */
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
