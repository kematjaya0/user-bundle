<?php

namespace Kematjaya\UserBundle\Tests\Unit\Exception;

use Kematjaya\UserBundle\Exception\UserNotFoundException;
use PHPUnit\Framework\TestCase;

class UserNotFoundExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $ex = new UserNotFoundException('ID-123');
        $this->assertSame('user with identity ID-123 not found', $ex->getMessage());
    }
}
