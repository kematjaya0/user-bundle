<?php

declare(strict_types=1);

namespace Kematjaya\UserBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class UuidGenerator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, object|null $entity): UuidV7
    {
        return Uuid::v7();
    }
}
