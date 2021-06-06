<?php

namespace Kematjaya\UserBundle\Controller;

use Kematjaya\UserBundle\Form\LoginType;
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
            $this->addFlash("info", 'wellcome back : '. $this->getUser()->getUsername());
            
            return $this->redirectToRoute($this->getRedirectPath());
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginType::class, [
            'username' => $lastUsername
        ]);
        
        return $this->render('@User/security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'form' => $form->createView()
        ]);
    }

    protected function getRedirectPath():string
    {
        $config = $this->getConfigs();
        $redirect = isset($config['auth_success']) ? $config['auth_success'] : null;
        if (null !== $redirect) {
            
            return $redirect;
        }  
        
        $redirects = $config['login_success'];
        if (empty($redirects['roles'])) {
            
            return $redirects['default'];
        }
        
        dump($this->getUser()->getRoles());exit; 
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
