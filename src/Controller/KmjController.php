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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package Kematjaya\UserBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class KmjController extends AbstractKmjController
{
    
    /**
     * Get profile page
     * 
     * @return Response
     */
    public function profile(): Response
    {
        $redirectPath = $this->getRoutingConfiguration()->getLoginSuccessRedirectPath($this->getUser()->getRoles());
        
        return $this->render('@User/security/profile.html.twig', [
            'kmj_user' => $this->getUser(),
            'title' => 'profile', 'back_path' => $redirectPath
        ]);
    }
    
    /**
     * Reset password page
     * 
     * @param  Request              $request
     * @param  KmjUserRepoInterface $repo
     * @param  string               $identityNumber
     * @return Response
     * @throws UserNotFoundException
     */
    public function resetPassword(Request $request, KmjUserRepoInterface $repo, string $identityNumber): Response
    {
        $user = $repo->findOneByIdentityNumber($identityNumber);
        if (!$user) {
            throw new UserNotFoundException($identityNumber);
        }
        
        $userReset = new ResettingPassword($user);
        $form = $this->createForm(
            ResetPasswordType::class, $userReset
        );
        
        $redirectPath = $this->getRoutingConfiguration()->getResetPasswordRedirectPath($this->getUser()->getRoles());
        $object = parent::processForm($request, $form);
        if ($object) {
            
            return $this->redirectToRoute($redirectPath);
        }
        
        return $this->render(
            '@User/security/reset-password.html.twig', [
            'data' => $user, 'form' => $form->createView(),
            'title' => 'reset_password',  'back_path' => $redirectPath
            ]
        );
    }
    
    /**
     * Saving object into database
     * 
     * @param  ResettingPassword      $object
     * @param  EntityManagerInterface $manager
     * @return mixed object persisted in database
     */
    protected function saveObject($object, \Doctrine\ORM\EntityManagerInterface $manager) 
    {
        if (!$object instanceof ResettingPassword) {
            throw new \Exception(sprintf("object type not allowed: %s", ResettingPassword::class));
        }
        
        return parent::saveObject($object->getUser(), $manager);
    }
}
