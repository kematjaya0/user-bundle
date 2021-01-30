<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Subscriber;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @package Kematjaya\UserBundle\Subscriber
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class UserTypeSubscriber implements UserTypeSubscriberInterface
{
    
    /**
     * 
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;
    
    /**
     * 
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    
    /**
     * 
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;
    
    public function __construct(TokenStorageInterface $token, EncoderFactoryInterface $encoderFactory, RoleHierarchyInterface $roleHierarchy)
    { 
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $token;
        $this->roleHierarchy = $roleHierarchy;
    }
    
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    public function postSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        $form
            ->add('username', TextType::class, [
                'attr' => ['readonly' => (bool) $data->getId()]
            ])
            ->add('roles', ChoiceType::class, [
                'label' =>'roles',
                'choices' => $this->getRoles(),
                'multiple'  => true
            ]);
        
        if (null === $data->getId()) {
            $form->add('password', PasswordType::class);
        }
    }
    
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data instanceof KmjUserInterface) {
            
            return;
        }
        
        if ($data->getId()) {
            
            return;
        }
        
        $encoder = $this->encoderFactory->getEncoder($data);
        $password = $encoder->encodePassword($data->getPassword(), $data->getSalt());
        
        $data->setPassword($password);
        $event->setData($data);
    }
    
    public function getRoles():array
    {
        $roles = [];
        if (!$this->tokenStorage->getToken()) {
            
            return $roles;
        }
        
        
        foreach ($this->roleHierarchy->getReachableRoleNames($this->tokenStorage->getToken()->getRoleNames()) as $role) {
            $roles[$role] = $role;
        }
        return $roles;
    }
}
