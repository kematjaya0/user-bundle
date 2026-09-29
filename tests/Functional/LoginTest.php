<?php

namespace Kematjaya\UserBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

class LoginTest extends FunctionalTestCase
{
    private function submitLogin(string $username, string $password): Crawler
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            'kmj_login[username]' => $username,
            'kmj_login[password]' => $password,
        ]);

        return $this->client->submit($form);
    }

    public function testLoginPageRenders(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('input[name="kmj_login[username]"]'));
        $this->assertCount(1, $crawler->filter('input[name="kmj_login[password]"]'));
    }

    public function testSuccessfulLoginRedirectsToDefaultRoute(): void
    {
        $this->createUser('budi', 'rahasia');

        $this->submitLogin('budi', 'rahasia');

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        $this->assertSame('home', $this->client->getResponse()->getContent());
    }

    public function testSuccessfulLoginRedirectsByRole(): void
    {
        $this->createUser('root', 'rahasia', ['ROLE_SUPER_USER']);

        $this->submitLogin('root', 'rahasia');

        $this->assertResponseRedirects('/admin');
    }

    public function testUserStaysActiveAfterLogin(): void
    {
        $this->createUser('budi', 'rahasia');

        $this->submitLogin('budi', 'rahasia');

        $this->assertTrue($this->findUser('budi')->getIsActive());
    }

    /**
     * Wrong password and unknown user must show the same error so usernames
     * cannot be enumerated.
     */
    public function testWrongPasswordAndUnknownUserShowSameError(): void
    {
        $this->createUser('budi', 'rahasia');

        $wrongPassword = $this->submitLogin('budi', 'salah')->filter('.alert-danger')->text();
        $unknownUser = $this->submitLogin('tidak-ada', 'salah')->filter('.alert-danger')->text();

        $this->assertSame('Invalid credentials.', trim($wrongPassword));
        $this->assertSame(trim($wrongPassword), trim($unknownUser));
    }

    public function testInactiveUserCannotLogin(): void
    {
        $this->createUser('budi', 'rahasia', ['ROLE_USER'], false);

        $crawler = $this->submitLogin('budi', 'rahasia');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Invalid credentials.', trim($crawler->filter('.alert-danger')->text()));
    }

    public function testLastUsernameIsKeptAfterFailedLogin(): void
    {
        $crawler = $this->submitLogin('budi', 'salah');

        $this->assertSame('budi', $crawler->filter('input[name="kmj_login[username]"]')->attr('value'));
    }

    public function testAuthenticatedUserVisitingLoginIsRedirected(): void
    {
        $this->client->loginUser($this->createUser('budi', 'rahasia'));

        $this->client->request('GET', '/login');

        $this->assertResponseRedirects('/');
    }

    public function testProtectedPageRedirectsToLoginThenBackAfterLogin(): void
    {
        $this->createUser('budi', 'rahasia');

        $this->client->request('GET', '/user/profile');
        $this->assertResponseRedirects('/login');
        $this->assertBrowserHasCookie('redirect_path');

        $this->submitLogin('budi', 'rahasia');

        $this->assertResponseRedirects('http://localhost/user/profile');
        $this->assertBrowserNotHasCookie('redirect_path');
    }

    public function testLogoutRouteIsHandledByFirewall(): void
    {
        $this->client->loginUser($this->createUser('budi', 'rahasia'));

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }
}
