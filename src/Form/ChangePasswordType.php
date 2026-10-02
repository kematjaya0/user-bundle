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

class ChangePasswordType extends AbstractType
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'password_old',
                PasswordType::class,
                [
                    "required" => true, "label" => "old_password",
                ]
            )
            ->add(
                'password_new',
                PasswordType::class,
                [
                    "required" => true,
                    "label" => "password_new",
                ]
            )
            ->add(
                'password_re_new',
                PasswordType::class,
                [
                    "required" => true,
                    "label" => "password_re_new",
                ]
            );


        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $data = $event->getData();
                $form = $event->getForm();

                if (null === $data->getPasswordOld() || !$this->userPasswordHasher->isPasswordValid($data->getUser(), $data->getPasswordOld())) {
                    $form->get("password_old")->addError(new FormError("old password is wrong! "));

                    return;
                }

                if ($data->getPasswordNew() !== $data->getPasswordReNew()) {
                    $form->get("password_re_new")->addError(new FormError("new password is not match!"));

                    return;
                }

                $encoder = $this->passwordHasherFactory->getPasswordHasher($data->getUser());
                $data->getUser()->setPassword($encoder->hash($data->getPasswordNew()));
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ClientChangePasswordInterface::class,
            ]
        );
    }
}
