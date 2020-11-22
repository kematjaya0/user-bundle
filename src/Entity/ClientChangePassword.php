<?php

namespace Kematjaya\UserBundle\Entity;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
class ClientChangePassword implements ClientChangePasswordInterface
{
    /**
     *
     * @var string
     */
    private $password_new;
    
    /**
     *
     * @var string
     */
    private $password_old;
    
    /**
     *
     * @var string
     */
    private $password_re_new;
    
    /**
     *
     * @var KmjUserInterface
     */
    private $user;
    
    public function __construct(KmjUserInterface $user) 
    {
        $this->user = $user;
    }
    
    public function setUser(KmjUserInterface $user):ClientChangePasswordInterface
    {
        $this->user = $user;
        
        return $this;
    }
    
    public function getUser():?KmjUserInterface
    {
        return $this->user;
    }
    
    public function getPasswordNew(): ?string 
    {
        return $this->password_new;
    }

    public function getPasswordOld(): ?string 
    {
        return $this->password_old;
    }

    public function getPasswordReNew(): ?string 
    {
        return $this->password_re_new;
    }

    public function setPasswordNew(string $password): ClientChangePasswordInterface 
    {
        $this->password_new = $password;
        
        return $this;
    }

    public function setPasswordOld(string $password): ClientChangePasswordInterface 
    {
        $this->password_old = $password; 
        
        return $this;
    }

    public function setPasswordReNew(string $password): ClientChangePasswordInterface 
    {
        $this->password_re_new = $password;
        
        return $this;
    }

}
