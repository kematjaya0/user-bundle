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
interface ResettingPasswordInterface 
{
    public function getUser():?KmjUserInterface;
    
    public function setUser(KmjUserInterface $user):self;
}
