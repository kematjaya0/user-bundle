<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Controller;

use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
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
     * 
     * @var RoutingConfigurationFactoryInterface
     */
    private $routingConfigurationFactory;
    
    public function __construct(RoutingConfigurationFactoryInterface $routingConfigurationFactory)
    {
        $this->routingConfigurationFactory = $routingConfigurationFactory;
    }
    
    /**
     * Get array configurations
     * 
     * @return RoutingConfigurationFactoryInterface
     */
    protected function getRoutingConfiguration()
    {
        return $this->routingConfigurationFactory;
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
