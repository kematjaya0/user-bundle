<?php

namespace Kematjaya\UserBundle\Form;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Kematjaya\UserBundle\Subscriber\Builder\UserTypeSubscriberBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Nur Hidayatullah <kematjaya0@hmail.com>
 */
class KmjUserType extends AbstractType
{
    public function __construct(private readonly UserTypeSubscriberBuilderInterface $userTypeSubscriberBuilder) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, [
                'label' => 'username',
            ])
            ->add('name', null, [
                'label' => 'name',
            ])
            ->add('is_active', null, [
                'label' => 'is_active',
            ]);

        $className = $builder->getData()::class;
        $subscriber = $this->userTypeSubscriberBuilder->getSubscriber($className);
        if ($subscriber) {
            $builder->addEventSubscriber($subscriber);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KmjUserInterface::class,
        ]);
    }
}
