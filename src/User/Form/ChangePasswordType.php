<?php

/**
 * Description of ChangePasswordType
 *
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */

namespace Kematjaya\User\Form;

use Kematjaya\User\Entity\KmjUserInterface;
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
    
    private $encoderFactory;
    private $passwordEncoder;
    
    public function __construct(
        EncoderFactoryInterface $encoderFactory, 
        UserPasswordEncoderInterface $passwordEncoder) 
    {
        $this->encoderFactory = $encoderFactory;
        $this->passwordEncoder = $passwordEncoder;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, [
                "attr" => ["readonly" => true, "class" => "form-control"]
            ])
            ->add('password_old', PasswordType::class, [
                "required" => true, "label" => "Password Lama", 
                "attr" => ["class" => "form-control"]
            ])
            ->add('password_new', PasswordType::class, [
                "required" => true, 
                "label" => "Password Baru",
                "attr" => ["class" => "form-control"]
            ])
            ->add('password_re_new', PasswordType::class, [
                "required" => true, 
                "label" => "Ulangi Password Baru",
                "attr" => ["class" => "form-control"]
            ]);
        
        
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();
            
            $error = false;
            // jika password lama tidak cocok
            if(!$this->passwordEncoder->isPasswordValid($data, $data->getPasswordOld())) 
            {
                $form->get("password_old")->addError(new FormError("old password is wrong! "));
                $error = true;
            }
            
            if($data->getPasswordNew() !== $data->getPasswordReNew()) 
            {
                $form->get("password_re_new")->addError(new FormError("new password is not match!"));
                $error = true;
            }
                
            $encoder = $this->encoderFactory->getEncoder($data);
            $password = $encoder->encodePassword( $data->getPasswordNew(), $data->getUsername());
            if(!$error) {
                $data->setPassword($password);
                $event->setData($data);
            }
        });
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KmjUserInterface::class,
        ]);
    }
}
