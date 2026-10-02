<?php

/**
 * This file is part of the user-bundle.
 */

namespace Kematjaya\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package Kematjaya\UserBundle\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
class AbstractKmjController extends AbstractController
{
    public function __construct(private readonly RoutingConfigurationFactoryInterface $routingConfigurationFactory) {}

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine' => '?' . ManagerRegistry::class,
        ]);
    }

    /**
     * Get array configurations
     */
    protected function getRoutingConfiguration(): RoutingConfigurationFactoryInterface
    {
        return $this->routingConfigurationFactory;
    }

    /**
     * Process form from request
     */
    protected function processForm(Request $request, FormInterface $form): ?object
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

        $manager = $this->container->get('doctrine')->getManager();

        try {

            $user = $this->saveObject($form->getData(), $manager);
            if ($user) {
                $this->addFlash("info", "change password successfully.");
            }

            return $user;
        } catch (Exception $ex) {
            $this->addFlash("error", $ex->getMessage());
        }

        return null;
    }

    /**
     * Saving object into database
     */
    protected function saveObject(object $object, EntityManagerInterface $manager): object
    {
        $manager->transactional(
            function (EntityManagerInterface $em) use ($object): void {
                $em->persist($object);
            }
        );

        return $object;
    }

    /**
     * Get error form
     */
    protected function getErrorsFromForm(FormInterface $form): array
    {
        $errors = [];
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
