<?php

namespace Kematjaya\UserBundle\Repo;

use Kematjaya\UserBundle\Entity\KmjUserInterface;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
interface KmjUserRepoInterface
{
    /**
     * Get KmjUser object by username
     *
     * @param  string $identityNumber identity number of entity
     */
    public function findOneByIdentityNumber(string $identityNumber): ?KmjUserInterface;

    /**
     * Get KmjUser object by username and is active
     */
    public function findOneByUsernameAndActive(string $username): ?KmjUserInterface;

    /**
     * Create KmjUser object
     */
    public function createUser(): KmjUserInterface;
}
