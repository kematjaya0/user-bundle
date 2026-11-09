<?php

namespace Kematjaya\UserBundle\Form;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @package Kematjaya\UserBundle\Form
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class LoginType extends AbstractType
{
    public const NAME = 'kmj_login';

    private bool|string|int|float|\UnitEnum|array|null $configs;

    public function __construct(ParameterBagInterface $bag)
    {
        $this->configs = $bag->get('user');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('username', TextType::class, [
                    'label' => 'username',
                ])
                ->add('password', PasswordType::class, [
                    'label' => 'password',
                ]);

        if ($this->configs['use_captcha']) {
            $builder->add('captcha', CaptchaType::class, [
                'label' => 'captcha',
            ]);
        }
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
