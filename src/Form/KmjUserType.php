<?php

namespace Kematjaya\UserBundle\Form;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class KmjUserType extends AbstractType
{
    protected $encoderFactory;
    
    protected $roleHierarchy;
    
    protected $tokenStorage;
    
    protected $roleList = [];
    
    public function __construct(
            TokenStorageInterface $token, 
            EncoderFactoryInterface $encoderFactory, 
            RoleHierarchyInterface $roleHierarchy) 
    {
        $this->encoderFactory = $encoderFactory;
        $this->roleHierarchy = $roleHierarchy;
        $this->tokenStorage = $token;
        
        if($token->getToken())
        {
            foreach($roleHierarchy->getReachableRoleNames($token->getToken()->getRoleNames()) as $role) 
            {
                $this->roleList[$role] = $role;
            }
        }
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => ['readonly' => (bool) $builder->getForm()->getData()->getId()]
            ])
            ->add('name')
            ->add('roles')
            ->add('is_active')
        ;
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event){
            $data = $event->getData();
            $form = $event->getForm();
            
            $form
                ->add('roles', ChoiceType::class, [
                    'label' =>'roles',
                    'choices' => $this->roleList,
                    'multiple'  => true
                ])
            ;
            if (!$data || null === $data->getId()) 
            {
                $form->add('password', PasswordType::class);
            }
        });
        
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event){
            $data = $event->getData();
            $form = $event->getForm();
            if(!$data->getId()) 
            {
                $encoder = $this->encoderFactory->getEncoder($data);
                $password = $encoder->encodePassword( $data->getPassword(), $data->getUsername());
                $data->setPassword($password);
                $event->setData($data);
            }
        });
        
        if($options['event_subcriber'])
        {
            $builder->addEventSubscriber($options['event_subcriber']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('event_subcriber');
        $resolver->setAllowedTypes('event_subcriber', ["null", EventSubscriberInterface::class]);
        $resolver->setDefaults([
            'data_class' => KmjUserInterface::class,
            'event_subcriber' => null
        ]);
    }
}

