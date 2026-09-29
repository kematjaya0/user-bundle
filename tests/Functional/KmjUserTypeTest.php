<?php

namespace Kematjaya\UserBundle\Tests\Functional;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Form\KmjUserType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class KmjUserTypeTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // the subscriber reads the available roles from the current token
        $admin = $this->createUser('admin', 'rahasia', ['ROLE_SUPER_USER']);
        static::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($admin, 'main', $admin->getRoles())
        );
    }

    private function createForm(DefaultUser $user): FormInterface
    {
        return static::getContainer()->get(FormFactoryInterface::class)
            ->create(KmjUserType::class, $user, ['csrf_protection' => false]);
    }

    public function testNewUserFormOffersReachableRoles(): void
    {
        $form = $this->createForm(new DefaultUser());

        $this->assertTrue($form->has('single_role'));
        $this->assertTrue($form->has('password'));
        $choices = $form->get('single_role')->getConfig()->getOption('choices');
        $this->assertArrayHasKey('ROLE_ADMINISTRATOR', $choices);
        $this->assertArrayNotHasKey('ROLE_USER', $choices);
    }

    public function testCreateUserHashesPasswordAndTrimsUsername(): void
    {
        $user = new DefaultUser();
        $form = $this->createForm($user);

        $form->submit([
            'username' => '  kasir01 ',
            'name' => 'Kasir',
            'is_active' => true,
            'single_role' => 'ROLE_ADMINISTRATOR',
            'password' => 'rahasia',
        ]);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
        $this->assertSame('kasir01', $user->getUsername());
        $this->assertSame(['ROLE_ADMINISTRATOR'], $user->getRoles());
        $this->assertTrue(static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($user, 'rahasia'));
    }

    /**
     * Usernames of inactive users are taken too; otherwise reactivating the old
     * user leaves two active users with the same username.
     */
    public function testUsernameOfInactiveUserIsAlsoRejected(): void
    {
        $this->createUser('kasir01', 'lama', ['ROLE_USER'], false);
        $form = $this->createForm(new DefaultUser());

        $form->submit([
            'username' => 'kasir01',
            'name' => 'Kasir',
            'is_active' => true,
            'single_role' => 'ROLE_ADMINISTRATOR',
            'password' => 'rahasia',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('username')->getErrors());
    }
}
