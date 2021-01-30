<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Tests\Util;

use Symfony\Component\Security\Core\Role\RoleHierarchy as Hierarchy;

/**
 * @package Kematjaya\UserBundle\Tests\Util
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class RoleHierarchy extends Hierarchy
{
    public function __construct() {
        
        $hierarchy = [
            'ROLE_USER'
        ];
        
        parent::__construct($hierarchy);
    }
}
