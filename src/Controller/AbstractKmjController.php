<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * @package Kematjaya\UserBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class AbstractKmjController extends AbstractController
{
    /**
     * Get array configurations
     * 
     * @return type
     * @throws Exception
     */
    protected function getConfigs()
    {
        $config = $this->container->getParameter('user');
        if(!isset($config['route']['auth_success'])) {
            throw new \Exception("please set router.auth_succes key under kmj_user config");
        }
        
        return $config['route'];
    }
    
    /**
     * Process form from request
     * 
     * @param  Request       $request
     * @param  FormInterface $form
     * @return type
     */
    protected function processForm(Request $request, FormInterface $form)
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            
            return null;
        }
        
        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $this->addFlash("error", implode(", ", $errors));
            
            return null;
        }
        
        $manager = $this->getDoctrine()->getManager();

        try{
            
            $user = $this->saveObject($form->getData(), $manager);
            if($user) {
                $this->addFlash("info", "change password successfully.");
            }
            
            return $user;
        } catch (Exception $ex) {
            $this->addFlash("error", $ex->getMessages());
        }
        
        return null;
    }
    
    /**
     * Saving object into database
     * 
     * @param  mixed                  $object
     * @param  EntityManagerInterface $manager
     * @return type
     */
    protected function saveObject($object, EntityManagerInterface $manager) 
    {
        $manager->transactional(
            function (EntityManagerInterface $em) use ($object) {
                $em->persist($object);
            }
        );
        
        return $object;
    }
    
    /**
     * Get error form
     * 
     * @param  FormInterface $form
     * @return type
     */
    protected function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) {
            if (!$childForm instanceof FormInterface) {
                continue;
            }
            
            $childErrors = $this->getErrorsFromForm($childForm);
            if (!$childErrors) {
                continue;
            }
            
            $errors[$childForm->getName()] = implode(", ", $childErrors);
        }
        
        return $errors;
    }
}
