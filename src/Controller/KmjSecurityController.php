<?php

namespace Kematjaya\UserBundle\Controller;

use Kematjaya\UserBundle\Exception\UserNotFoundException;
use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Form\ChangePasswordType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Kematjaya\UserBundle\Controller\AbstractKmjController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class KmjSecurityController extends AbstractKmjController
{
    /**
     * 
     * @var AuthenticationUtils
     */
    protected $authenticationUtils;
    
    /**
     * 
     * @var KmjUserRepoInterface
     */
    protected $userRepo;
    
    public function __construct(AuthenticationUtils $authenticationUtils, KmjUserRepoInterface $userRepo) 
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->userRepo = $userRepo;
    }
    
    /**
     * Login page
     * 
     * @return Response
     */
    public function login(): Response
    {
        if ($this->getUser()) {
            $config = $this->getConfigs();
            
            $this->addFlash("info", 'wellcome back : '. $this->getUser()->getUsername());
            return $this->redirectToRoute($config['auth_success']);
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('@User/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
    /**
     * Change password page
     * 
     * @param  Request $request
     * @return Response
     * @throws UserNotFoundException
     */
    public function changePassword(Request $request) : Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('kmj_user_logout');
        }
        
        $config = $this->getConfigs();
        
        $user = $this->userRepo->find($this->getUser()->getId());
        if (!$user) {
            throw new UserNotFoundException($this->getUser()->getId());
        }
        
        $form = $this->createForm(ChangePasswordType::class, new ClientChangePassword($user), ["action" => $this->generateUrl("kmj_user_change_password")]);
        $object = parent::processForm($request, $form);
        if ($object) {
            return $this->redirectToRoute($config['auth_success']);
        }
        
        return $this->render(
            '@User/security/change_password.html.twig', array(
            'title' => 'change_password',
            'form' => $form->createView(),
            'back_path' => $config['auth_success']
            )
        );
    }
    
    /**
     * Saving object into database
     * 
     * @param  ClientChangePassword   $object
     * @param  EntityManagerInterface $manager
     * @return type
     */
    protected function saveObject($object, \Doctrine\ORM\EntityManagerInterface $manager) 
    {
        if (!$object instanceof ClientChangePassword) {
            throw new \Exception(sprintf("object type not allowed: %s", ClientChangePassword::class));
        }
        
        return parent::saveObject($object->getUser(), $manager);
    }
}
