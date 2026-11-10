<?php

declare(strict_types=1);

namespace Kematjaya\UserBundle\Tests\Unit\Form;

use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Form\ChangePasswordType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

/**
 * Submit form yang sesungguhnya, bukan penangan yang dipanggil langsung, supaya
 * pemetaan field ke objek data ikut teruji.
 */
final class ChangePasswordFormSubmitTest extends TestCase
{
    private const OLD_PLAIN_PASSWORD = 'oldPassword123';

    private const NEW_PLAIN_PASSWORD = 'newPassword456';

    private NativePasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new NativePasswordHasher(null, null, 4);
    }

    public function testSubmittedNewPasswordIsHashedOntoTheUser(): void
    {
        $user = $this->createUser();
        $form = $this->createForm($user);

        $form->submit([
            'password_old' => self::OLD_PLAIN_PASSWORD,
            'password_new' => self::NEW_PLAIN_PASSWORD,
            'password_re_new' => self::NEW_PLAIN_PASSWORD,
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertCount(0, $form->getErrors(true), (string) $form->getErrors(true));
        self::assertTrue(
            $this->hasher->verify((string) $user->getPassword(), self::NEW_PLAIN_PASSWORD),
            'the new password cannot be used to log in',
        );
        self::assertNotSame(self::NEW_PLAIN_PASSWORD, $user->getPassword(), 'the plain password was stored');
    }

    public function testEmptyOldPasswordIsRejectedWithoutCrashing(): void
    {
        $user = $this->createUser();
        $oldHash = $user->getPassword();
        $form = $this->createForm($user);

        $form->submit([
            'password_old' => '',
            'password_new' => self::NEW_PLAIN_PASSWORD,
            'password_re_new' => self::NEW_PLAIN_PASSWORD,
        ]);

        self::assertCount(1, $form->getErrors(true));
        self::assertStringContainsString('old password cannot be empty.', (string) $form->getErrors(true));
        self::assertSame($oldHash, $user->getPassword());
    }

    public function testEmptyNewPasswordIsRejectedWithoutCrashing(): void
    {
        $user = $this->createUser();
        $oldHash = $user->getPassword();
        $form = $this->createForm($user);

        $form->submit([
            'password_old' => self::OLD_PLAIN_PASSWORD,
            'password_new' => '',
            'password_re_new' => '',
        ]);

        self::assertCount(1, $form->getErrors(true));
        self::assertStringContainsString('new password cannot be empty.', (string) $form->getErrors(true));
        self::assertSame($oldHash, $user->getPassword());
        self::assertTrue(
            $this->hasher->verify((string) $user->getPassword(), self::OLD_PLAIN_PASSWORD),
            'the old password no longer works',
        );
    }

    public function testWrongOldPasswordIsRejected(): void
    {
        $user = $this->createUser();
        $oldHash = $user->getPassword();
        $form = $this->createForm($user);

        $form->submit([
            'password_old' => 'wrongPassword999',
            'password_new' => self::NEW_PLAIN_PASSWORD,
            'password_re_new' => self::NEW_PLAIN_PASSWORD,
        ]);

        self::assertCount(1, $form->getErrors(true));
        self::assertStringContainsString('old password is wrong!', (string) $form->getErrors(true));
        self::assertSame($oldHash, $user->getPassword());
    }

    public function testMismatchedNewPasswordIsRejected(): void
    {
        $user = $this->createUser();
        $oldHash = $user->getPassword();
        $form = $this->createForm($user);

        $form->submit([
            'password_old' => self::OLD_PLAIN_PASSWORD,
            'password_new' => self::NEW_PLAIN_PASSWORD,
            'password_re_new' => 'typoPassword999',
        ]);

        self::assertCount(1, $form->getErrors(true));
        self::assertStringContainsString('new password is not match!', (string) $form->getErrors(true));
        self::assertSame($oldHash, $user->getPassword());
    }

    public function testUserThatCannotHoldAPasswordIsIgnoredInsteadOfCrashing(): void
    {
        $user = $this->createMock(KmjUserInterface::class);
        $user->expects($this->never())->method('setPassword');

        $factory = new PasswordHasherFactory([KmjUserInterface::class => $this->hasher]);
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new ChangePasswordType($factory, new UserPasswordHasher($factory))], []))
            ->getFormFactory()
            ->create(ChangePasswordType::class, new ClientChangePassword($user));

        $form->submit([
            'password_old' => self::OLD_PLAIN_PASSWORD,
            'password_new' => self::NEW_PLAIN_PASSWORD,
            'password_re_new' => self::NEW_PLAIN_PASSWORD,
        ]);

        self::assertCount(0, $form->getErrors(true));
    }

    private function createUser(): DefaultUser
    {
        $user = new DefaultUser();
        $user->setUsername('cashier');
        $user->setPassword($this->hasher->hash(self::OLD_PLAIN_PASSWORD));

        return $user;
    }

    private function createForm(DefaultUser $user): FormInterface
    {
        $factory = new PasswordHasherFactory([DefaultUser::class => $this->hasher]);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new ChangePasswordType($factory, new UserPasswordHasher($factory))], []))
            ->getFormFactory()
            ->create(ChangePasswordType::class, new ClientChangePassword($user));
    }
}
