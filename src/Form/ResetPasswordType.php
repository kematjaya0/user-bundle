<?php

/**
 * This file is part of the Kematjaya\UserBundle.
 */

namespace Kematjaya\UserBundle\Form;

use Kematjaya\UserBundle\Entity\ResettingPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @package Kematjaya\UserBundle\Form
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class ResetPasswordType extends AbstractType
{
    /**
     * 
     * @var PasswordHasherFactoryInterface
     */
    private $encoderFactory;
    
    public function __construct(PasswordHasherFactoryInterface $encoderFactory) 
    {
        $this->encoderFactory = $encoderFactory;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username', TextType::class, [
                    'label' => 'username',
                    'attr' => ['readonly' => true]
                    ]
            )
            ->add(
                'password', PasswordType::class, [
                    'label' => 'password',
                    'required' => true
                    ]
            )
            ->add(
                'retype_password', PasswordType::class, [
                    'label' => 'retype_password',
                    'required' => true
                    ]
            );
        
        $builder->addEventListener(
            FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if(!$data instanceof ResettingPassword) {
                    return;
                }
            
                $form = $event->getForm();
                if(trim($data->getPassword()) != trim($data->getRetypePassword())) {
                    $form->get('retype_password')->addError(
                        new FormError(sprintf("password not match."))
                    );
                    return;
                }
            
                $user = $data->getUser();
                $encoder = $this->encoderFactory->getPasswordHasher($user);
                $data->setPassword($encoder->hash($user->getPassword()));
                $data->setRetypePassword($password);
            
                $event->setData($data);
            }
        );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
            'data_class' => ResettingPassword::class
            ]
        );
    }
}
