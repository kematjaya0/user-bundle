<?php

namespace Kematjaya\UserBundle\Form;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * 
 * @author Nur Hidayatullah <kematjaya0@hmail.com>
 */
class KmjUserType extends AbstractType
{
    
    /**
     * 
     * @var UserTypeSubscriberBuilderInterface
     */
    private $userTypeSubscriberBuilder;
    
    public function __construct(UserTypeSubscriberBuilderInterface $userTypeSubscriberBuilder) 
    {
        $this->userTypeSubscriberBuilder = $userTypeSubscriberBuilder;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('name')
            ->add('is_active');
        
        $className = get_class($builder->getData());
        $subscriber = $this->userTypeSubscriberBuilder->getSubscriber($className);
        if ($subscriber) {
            $builder->addEventSubscriber($subscriber);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KmjUserInterface::class,
        ]);
    }
}

