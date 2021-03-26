<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @package Kematjaya\UserBundle\Form
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class LoginType extends AbstractType
{
    const NAME = 'kmj_login';
    
    /**
     * 
     * @var array
     */
    private $configs;
    
    public function __construct(ParameterBagInterface $bag) 
    {
        $this->configs = $bag->get('user');
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('username', TextType::class, [
                    'label' => 'username'
                ])
                ->add('password', PasswordType::class, [
                    'label' => 'password'
                ]);
                
        if ($this->configs['use_captcha']) {
            $builder->add('captcha', CaptchaType::class, [
                'label' => 'captcha'
            ]);
        }
    }
    
    public function getBlockPrefix() 
    {
        return self::NAME;
    }
}
