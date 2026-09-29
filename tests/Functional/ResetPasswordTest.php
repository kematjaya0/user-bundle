<?php

namespace Kematjaya\UserBundle\Tests\Functional;

use Kematjaya\UserBundle\Entity\DefaultUser;

class ResetPasswordTest extends FunctionalTestCase
{
    private function submitReset(DefaultUser $target, string $password, string $retype): void
    {
        $crawler = $this->client->request('GET', sprintf('/user/%s/reset-password', $target->getId()));
        $form = $crawler->filter('form')->form([
            'reset_password[password]' => $password,
            'reset_password[retype_password]' => $retype,
        ]);
        $this->client->submit($form);
    }

    /**
     * A regular user must not be able to reset another user's password.
     */
    public function testRegularUserIsForbidden(): void
    {
        $target = $this->createUser('target', 'lama123');
        $this->client->loginUser($this->createUser('budi', 'rahasia'));

        $this->client->request('GET', sprintf('/user/%s/reset-password', $target->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdministratorCanResetPassword(): void
    {
        $target = $this->createUser('target', 'lama123');
        $this->client->loginUser($this->createUser('admin', 'rahasia', ['ROLE_ADMINISTRATOR']));

        $this->submitReset($target, 'baru456', 'baru456');

        $this->assertResponseRedirects('/');
        $this->assertTrue($this->isPasswordValid('target', 'baru456'));
    }

    public function testMismatchedPasswordKeepsPassword(): void
    {
        $target = $this->createUser('target', 'lama123');
        $this->client->loginUser($this->createUser('admin', 'rahasia', ['ROLE_ADMINISTRATOR']));

        $this->submitReset($target, 'baru456', 'beda789');

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->isPasswordValid('target', 'lama123'));
    }

    public function testUnknownUserReturns404(): void
    {
        $this->client->loginUser($this->createUser('admin', 'rahasia', ['ROLE_ADMINISTRATOR']));

        $this->client->request('GET', '/user/1ed0c1a2-0000-6000-8000-000000000000/reset-password');

        $this->assertResponseStatusCodeSame(404);
    }
}
