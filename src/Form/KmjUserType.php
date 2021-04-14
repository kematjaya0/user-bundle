<?php

namespace Kematjaya\UserBundle\Form;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Subscriber\UserTypeSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * 
 * @author Nur Hidayatullah <kematjaya0@hmail.com>
 */
class KmjUserType extends AbstractType
{
    
    /**
     * 
     * @var UserTypeSubscriberInterface
     */
    private $userTypeSubscriber;
    
    public function __construct(UserTypeSubscriberInterface $userTypeSubscriber) 
    {
        $this->userTypeSubscriber = $userTypeSubscriber;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('name')
            ->add('roles')
            ->add('is_active');
        
        $builder->addEventSubscriber($this->userTypeSubscriber);
        
        if ($options['event_subcriber']) {
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

