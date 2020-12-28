<?php

/**
 * This file is part of the user-bundle.
 */
namespace Kematjaya\UserBundle\Exception;

use Exception;
/**
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserNotFoundException extends Exception
{
    public function __construct(string $identityNumber) 
    {
        $message = sprintf("user with identity %s not found", $identityNumber);
        parent::__construct($message);
    }
}
