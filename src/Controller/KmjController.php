<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Controller;

use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Entity\ResettingPassword;
use Kematjaya\UserBundle\Exception\UserNotFoundException;
use Kematjaya\UserBundle\Form\ResetPasswordType;
use Kematjaya\UserBundle\Controller\AbstractKmjController;

/**
 * @package Kematjaya\UserBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class KmjController extends AbstractKmjController
{
    
    public function profile()
    {
        $config = $this->getConfigs();
        
        return $this->render(
            '@User/security/profile.html.twig', [
            'kmj_user' => $this->getUser(),
            'title' => 'profile', 'back_path' => $config['auth_success']
            ]
        );
    }
    
    public function resetPassword(Request $request, KmjUserRepoInterface $repo, string $identityNumber)
    {
        $user = $repo->findOneByIdentityNumber($identityNumber);
        if(!$user) {
            throw new UserNotFoundException($identityNumber);
        }
        
        $userReset = new ResettingPassword($user);
        $form = $this->createForm(
            ResetPasswordType::class, $userReset
        );
        
        $object = parent::processForm($request, $form);
        if($object) {
            $config = $this->getConfigs();
            return $this->redirectToRoute($config['auth_success']);
        }
        
        return $this->render(
            'pages/pti-user/reset-password.html.twig', [
            'data' => $user, 'form' => $form->createView()
            ]
        );
    }
    
    /**
     * Saving object into database
     * 
     * @param  ResettingPassword   $object
     * @param  EntityManagerInterface $manager
     * @return type
     */
    protected function saveObject($object, \Doctrine\ORM\EntityManagerInterface $manager) 
    {
        if(!$object instanceof ResettingPassword) {
            throw new \Exception(sprintf("object type not allowed: %s", ResettingPassword::class));
        }
        
        parent::saveObject($object->getUser(), $manager);
    }
}
