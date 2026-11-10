<?php

/**
 * Description of ChangePasswordType
 *
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */

namespace Kematjaya\UserBundle\Form;

use Kematjaya\UserBundle\Entity\ClientChangePasswordInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class ChangePasswordType extends AbstractType
{
    public function __construct(private readonly PasswordHasherFactoryInterface $passwordHasherFactory, private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'password_old',
                PasswordType::class,
                [
                "required" => true, "label" => "old_password",
                ],
            )
            ->add(
                'password_new',
                PasswordType::class,
                [
                "required" => true,
                "label" => "password_new",
                ],
            )
            ->add(
                'password_re_new',
                PasswordType::class,
                [
                "required" => true,
                "label" => "password_re_new",
                ],
            );


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $data = $event->getData();
                if (!$data instanceof ClientChangePasswordInterface) {
                    return;
                }

                $user = $data->getUser();
                if (!$user instanceof PasswordAuthenticatedUserInterface) {
                    return;
                }

                $form = $event->getForm();
                $passwordOld = (string) $data->getPasswordOld();
                if ('' === trim($passwordOld)) {
                    $form->get("password_old")->addError(new FormError("old password cannot be empty."));

                    return;
                }

                if (!$this->userPasswordHasher->isPasswordValid($user, $passwordOld)) {
                    $form->get("password_old")->addError(new FormError("old password is wrong! "));

                    return;
                }

                $passwordNew = (string) $data->getPasswordNew();
                if ('' === trim($passwordNew)) {
                    $form->get("password_new")->addError(new FormError("new password cannot be empty."));

                    return;
                }

                if ($passwordNew !== (string) $data->getPasswordReNew()) {
                    $form->get("password_re_new")->addError(new FormError("new password is not match!"));

                    return;
                }

                $encoder = $this->passwordHasherFactory->getPasswordHasher($user);
                $user->setPassword($encoder->hash($passwordNew));
            },
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
            'data_class' => ClientChangePasswordInterface::class,
            ],
        );
    }
}
