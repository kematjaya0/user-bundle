<?php

namespace Kematjaya\UserBundle\Entity;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface ClientChangePasswordInterface
{
    public function setUser(KmjUserInterface $user):self;
    
    public function getUser():?KmjUserInterface;
    
    public function setPasswordOld(string $password): self;
    
    public function getPasswordOld():?string;
    
    public function setPasswordNew(string $password): self;
    
    public function getPasswordNew():?string;
    
    public function setPasswordReNew(string $password): self;
    
    public function getPasswordReNew():?string;
}
