<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Entity;

use Kematjaya\UserBundle\Entity\KmjUserInterface;

/**
 * @package Kematjaya\UserBundle\Entity
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class DefaultUser implements KmjUserInterface
{
    protected $username;
    
    protected $is_active;
    
    protected $name;
    
    protected $password;
    
    protected $roles;
    
    public function __construct() 
    {
        $this->roles = [
            self::ROLE_USER
        ];
    }
    
    public function eraseCredentials() 
    {
        
    }

    public function getIsActive(): ?bool 
    {
        return $this->is_active;
    }

    public function getName(): ?string 
    {
        return $this->name;
    }

    public function getPassword() 
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getSalt() 
    {
        return null;
    }

    public function getUsername(): string 
    {
        return $this->username;
    }

    public function setIsActive(bool $is_active): KmjUserInterface 
    {
        $this->is_active = $is_active;
        
        return $this;
    }

    public function setName(string $name): KmjUserInterface 
    {
        $this->name = $name;
        
        return $this;
    }

    public function setPassword(string $password): KmjUserInterface 
    {
        $this->password = $password;
        
        return $this;
    }

    public function setRoles(array $roles): KmjUserInterface 
    {
        $this->roles = $roles;
        
        return $this;
    }

    public function setUsername(string $username): KmjUserInterface 
    {
        $this->username = $username;
        
        return $this;
    }

}
