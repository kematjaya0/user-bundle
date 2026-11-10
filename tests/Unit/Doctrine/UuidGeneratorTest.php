<?php

namespace Kematjaya\UserBundle\Tests\Unit\Doctrine;

use Kematjaya\UserBundle\Doctrine\UuidGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV7;

final class UuidGeneratorTest extends TestCase
{
    public function testGeneratesUuidV7(): void
    {
        $uuid = (new UuidGenerator())->generateId($this->createMock(\Doctrine\ORM\EntityManagerInterface::class), null);

        self::assertInstanceOf(UuidV7::class, $uuid);
    }
}
