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
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;


class ChangePasswordType extends AbstractType
{
    /**
     * 
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;
    
    /**
     * 
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;
    
    public function __construct(EncoderFactoryInterface $encoderFactory, UserPasswordEncoderInterface $passwordEncoder)
    { 
        $this->encoderFactory = $encoderFactory;
        $this->passwordEncoder = $passwordEncoder;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'password_old', PasswordType::class, [
                "required" => true, "label" => "old_password",
                ]
            )
            ->add(
                'password_new', PasswordType::class, [
                "required" => true, 
                "label" => "password_new"
                ]
            )
            ->add(
                'password_re_new', PasswordType::class, [
                "required" => true, 
                "label" => "password_re_new"
                ]
            );
        
        
        $builder->addEventListener(
            FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
            
                if (!$this->passwordEncoder->isPasswordValid($data->getUser(), $data->getPasswordOld())) {
                    $form->get("password_old")->addError(new FormError("old password is wrong! "));
                
                    return;
                }
            
                if ($data->getPasswordNew() !== $data->getPasswordReNew()) {
                    $form->get("password_re_new")->addError(new FormError("new password is not match!"));
                    
                    return;
                }
                
                $encoder = $this->encoderFactory->getEncoder($data->getUser());
                $password = $encoder->encodePassword($data->getPasswordNew(), $data->getUser()->getSalt());
                $data->getUser()->setPassword($password);
                $event->setData($data);
            }
        );
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
            'data_class' => ClientChangePasswordInterface::class,
            ]
        );
    }
}
