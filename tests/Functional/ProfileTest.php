<?php

namespace Kematjaya\UserBundle\Tests\Functional;

class ProfileTest extends FunctionalTestCase
{
    public function testProfileShowsCurrentUser(): void
    {
        $this->client->loginUser($this->createUser('budi', 'rahasia'));

        $crawler = $this->client->request('GET', '/user/profile');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('budi', $crawler->filter('table')->text());
    }
}
