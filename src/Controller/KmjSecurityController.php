<?php

namespace Kematjaya\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Kematjaya\UserBundle\Config\RoutingConfigurationFactoryInterface;
use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Exception\UserNotFoundException;
use Kematjaya\UserBundle\Form\ChangePasswordType;
use Kematjaya\UserBundle\Form\LoginType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class KmjSecurityController extends AbstractKmjController
{
    public function __construct(
        RoutingConfigurationFactoryInterface $routingConfigurationFactory,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly KmjUserRepoInterface $userRepo,
    ) {
        parent::__construct($routingConfigurationFactory);
    }

    /**
     * Login page
     */
    public function login(): Response
    {
        if ($this->getUser()) {
            $this->addFlash("info", 'wellcome back : ' . $this->getUser()->getUserIdentifier());

            return $this->redirectToRoute($this->getRoutingConfiguration()->getLoginSuccessRedirectPath($this->getUser()->getRoles()));
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginType::class, [
            'username' => $lastUsername,
        ]);

        return $this->render('@User/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'form' => $form->createView(),
        ]);
    }

    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Change password page
     *
     * @throws UserNotFoundException
     */
    public function changePassword(Request $request): Response
    {
        if (!$this->getUser()) {

            return $this->redirectToRoute('kmj_user_logout');
        }

        $user = $this->userRepo->find($this->getUser()->getId());
        if (!$user) {
            throw new UserNotFoundException($this->getUser()->getId());
        }

        $redirect = $this->getRoutingConfiguration()->getLoginSuccessRedirectPath($this->getUser()->getRoles());
        $form = $this->createForm(ChangePasswordType::class, new ClientChangePassword($user), ["action" => $this->generateUrl("kmj_user_change_password")]);
        $object = parent::processForm($request, $form);
        if ($object) {

            return $this->redirectToRoute($redirect);
        }

        return $this->render(
            '@User/security/change_password.html.twig',
            [
                'title' => 'change_password',
                'form' => $form->createView(),
                'back_path' => $redirect,
            ]
        );
    }

    /**
     * Saving object into database
     */
    protected function saveObject(object $object, EntityManagerInterface $manager): object
    {
        if (!$object instanceof ClientChangePassword) {
            throw new \Exception(sprintf("object type not allowed: %s", ClientChangePassword::class));
        }

        return parent::saveObject($object->getUser(), $manager);
    }
}
