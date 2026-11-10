<?php

declare(strict_types=1);

namespace Kematjaya\UserBundle\Tests\Unit\Form;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Entity\ResettingPassword;
use Kematjaya\UserBundle\Form\ResetPasswordType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Submit form yang sesungguhnya, bukan penangan yang dipanggil langsung, supaya
 * pemetaan field ke objek data ikut teruji.
 */
final class ResetPasswordFormSubmitTest extends TestCase
{
    private const OLD_PLAIN_PASSWORD = 'oldPassword123';

    private const NEW_PLAIN_PASSWORD = 'newPassword456';

    private NativePasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new NativePasswordHasher(null, null, 4);
    }

    public function testSubmittedPasswordIsTheOneThatGetsHashed(): void
    {
        $user = $this->createUser();
        $form = $this->createForm($user);

        $form->submit([
            'username' => 'cashier',
            'password' => self::NEW_PLAIN_PASSWORD,
            'retype_password' => self::NEW_PLAIN_PASSWORD,
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertCount(0, $form->getErrors(true), (string) $form->getErrors(true));
        self::assertTrue(
            $this->hasher->verify((string) $user->getPassword(), self::NEW_PLAIN_PASSWORD),
            'the password typed by the user cannot be used to log in',
        );
        self::assertFalse(
            $this->hasher->verify((string) $user->getPassword(), self::OLD_PLAIN_PASSWORD),
            'the old password still works',
        );
    }

    public function testEmptyPasswordIsRejectedAndLeavesTheOldPasswordAlone(): void
    {
        $user = $this->createUser();
        $oldHash = $user->getPassword();
        $form = $this->createForm($user);

        $form->submit([
            'username' => 'cashier',
            'password' => '',
            'retype_password' => '',
        ]);

        self::assertCount(1, $form->getErrors(true));
        self::assertStringContainsString('password cannot be empty.', (string) $form->getErrors(true));
        self::assertSame($oldHash, $user->getPassword(), 'the stored password was replaced anyway');
        self::assertTrue(
            $this->hasher->verify((string) $user->getPassword(), self::OLD_PLAIN_PASSWORD),
            'the user can no longer log in with the old password',
        );
    }

    public function testMismatchedRetypeLeavesNoPlainPasswordOnTheUser(): void
    {
        $user = $this->createUser();
        $oldHash = $user->getPassword();
        $form = $this->createForm($user);

        $form->submit([
            'username' => 'cashier',
            'password' => self::NEW_PLAIN_PASSWORD,
            'retype_password' => 'typoPassword999',
        ]);

        self::assertCount(1, $form->getErrors(true));
        self::assertStringContainsString('password not match.', (string) $form->getErrors(true));
        self::assertSame($oldHash, $user->getPassword(), 'the plain password leaked onto the user entity');
        self::assertNotSame(self::NEW_PLAIN_PASSWORD, $user->getPassword());
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
        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->method('getPasswordHasher')->willReturn($this->hasher);

        return Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new ResetPasswordType($factory)], []))
            ->getFormFactory()
            ->create(ResetPasswordType::class, new ResettingPassword($user));
    }
}
