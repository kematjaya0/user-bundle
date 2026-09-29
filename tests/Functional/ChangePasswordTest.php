<?php

namespace Kematjaya\UserBundle\Tests\Functional;

class ChangePasswordTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->client->loginUser($this->createUser('budi', 'lama123'));
    }

    private function submitChangePassword(string $old, string $new, string $retype): void
    {
        $crawler = $this->client->request('GET', '/user/change-password');
        $form = $crawler->filter('form')->form([
            'change_password[password_old]' => $old,
            'change_password[password_new]' => $new,
            'change_password[password_re_new]' => $retype,
        ]);
        $this->client->submit($form);
    }

    public function testPageRenders(): void
    {
        $this->client->request('GET', '/user/change-password');

        $this->assertResponseIsSuccessful();
    }

    public function testChangePasswordSuccess(): void
    {
        $this->submitChangePassword('lama123', 'baru456', 'baru456');

        $this->assertResponseRedirects('/');
        $this->assertTrue($this->isPasswordValid('budi', 'baru456'));
    }

    public function testWrongOldPasswordKeepsPassword(): void
    {
        $this->submitChangePassword('salah', 'baru456', 'baru456');

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->isPasswordValid('budi', 'lama123'));
    }

    public function testMismatchedNewPasswordKeepsPassword(): void
    {
        $this->submitChangePassword('lama123', 'baru456', 'beda789');

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->isPasswordValid('budi', 'lama123'));
    }

    public function testEmptyOldPasswordDoesNotCrash(): void
    {
        $this->submitChangePassword('', 'baru456', 'baru456');

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->isPasswordValid('budi', 'lama123'));
    }
}
