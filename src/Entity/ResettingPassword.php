<?php

/**
 * This file is part of the Kematjaya\UserBundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Kematjaya\UserBundle\Entity\KmjUserInterface;

/**
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class ResettingPassword implements ResettingPasswordInterface
{
    /**
     * 
     * @var KmjUserInterface
     */
    private $user;
    
    /**
     * 
     * @var string
     */
    private $password;
    
    /**
     * 
     * @var string
     */
    private $retype_password;
    
    public function __construct(KmjUserInterface $user) 
    {
        $this->user = $user;
    }
    
    public function getId():\Ramsey\Uuid\UuidInterface
    {
        return \Ramsey\Uuid\Uuid::fromDateTime(new \DateTime());
    }
    
    public function getUser(): KmjUserInterface 
    {
        return $this->user;
    }

    public function getUsername():?string
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

    public function setUser(KmjUserInterface $user):ResettingPasswordInterface 
    {
        $this->user = $user;
        
        return $this;
    }

    public function setPassword(string $password):self 
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
