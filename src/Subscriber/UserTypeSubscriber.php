<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Subscriber;

use Kematjaya\UserBundle\Entity\DefaultUser;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
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
     * @var PasswordHasherFactoryInterface
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
    
    /**
     * 
     * @var KmjUserRepoInterface
     */
    private $repo;
    
    public function __construct(KmjUserRepoInterface $repo, TokenStorageInterface $token, PasswordHasherFactoryInterface $encoderFactory, RoleHierarchyInterface $roleHierarchy)
    { 
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $token;
        $this->roleHierarchy = $roleHierarchy;
        $this->repo = $repo;
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
                'label' => 'username',
                'attr' => ['readonly' => (bool) $data->getId()]
            ])
            ->add('is_active', CheckboxType::class, [
                'label' => 'is_active'
            ])
            ->add('roles', ChoiceType::class, [
                'label' =>'roles',
                'multiple'  => true,
                'choices' => $this->getRoles()
            ]);
        
        if ($data instanceof DefaultUser) {
            $form
                ->remove('roles')
                ->add('single_role', ChoiceType::class, [
                'label' =>'roles',
                'choices' => $this->getRoles()
            ]);
        }
        
        if (null === $data->getId()) {
            $form->add('password', PasswordType::class, [
                'label' => 'password'
            ]);
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
        
        $data->setUsername(trim($data->getUsername()));
        $other = $this->repo->findOneByUsernameAndActive($data->getUsername());
        if ($other) {
            $event->getForm()
                    ->get('username')
                    ->addError(new FormError(sprintf('username "%s" already used', $data->getUsername())));
            return;
        }
        
        $encoder = $this->encoderFactory->getPasswordHasher($data);
        $password = $encoder->hash($data->getPassword());
        
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
        
        unset($roles[DefaultUser::ROLE_USER]);
        
        return $roles;
    }

    public function isSupported(string $className): bool 
    {
        $refflection = new \ReflectionClass($className);
        
        return $refflection->implementsInterface(KmjUserInterface::class);
    }

}
