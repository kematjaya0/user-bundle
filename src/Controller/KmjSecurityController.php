<?php

namespace Kematjaya\UserBundle\Controller;

use Kematjaya\UserBundle\Entity\ClientChangePassword;
use Kematjaya\UserBundle\Form\ChangePasswordType;
use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class KmjSecurityController extends AbstractController
{
    protected $authenticationUtils;
    
    protected $userRepo;
    
    public function __construct(AuthenticationUtils $authenticationUtils, KmjUserRepoInterface $userRepo) 
    {
        $this->authenticationUtils = $authenticationUtils;
        $this->userRepo = $userRepo;
    }
    
    private function getConfigs()
    {
        $config = $this->container->getParameter('user');
        if(!isset($config['route']['auth_success']))
        {
            throw new \Exception("please set router.auth_succes key under kmj_user config");
        }
        
        return $config['route'];
    }
    
    public function login(): Response
    {
        if ($this->getUser()) 
        {
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
    
    
    public function profile()
    {
        $config = $this->getConfigs();
        
        return $this->render('@User/security/profile.html.twig', [
            'kmj_user' => $this->getUser(),
            'title' => 'profile', 'back_path' => $config['auth_success']
        ]);
    }
    
    public function changePassword(
        Request $request)
    {
        if(!$this->getUser()) 
        {
            return $this->redirectToRoute('kmj_user_logout');
        }
        
        $config = $this->getConfigs();
        
        $user = $this->userRepo->find($this->getUser()->getId());
        if(!$user)
        {
            throw new \Exception('cannot find user');
        }
        
        $form = $this->createForm(ChangePasswordType::class, new ClientChangePassword($user), ["action" => $this->generateUrl("kmj_user_change_password")]);
        $form->handleRequest($request);
        if ($form->isSubmitted())
        {
            if($form->isValid()) {
                
                $em = $this->getDoctrine()->getManager();
                $con = $em->getConnection();
                $con->beginTransaction();
                    
                try{
                    
                    $user = $form->getData()->getUser();
                    $em->persist($user);
                    $em->flush();
                    $con->commit();
                    
                    $this->addFlash("info", "change password successfully.");
                    return $this->redirectToRoute($config['auth_success']);
                } catch (\Exception $ex) {
                    $con->rollBack();
                    
                    $this->addFlash("error", $ex->getMessages());
                }
            }else{
                $errors = $this->getErrorsFromForm($form);
                
                $this->addFlash("error", implode(", ", $errors));
            }
        }
        
        return $this->render('@User/security/change_password.html.twig', array(
            'title' => 'change_password',
            'form' => $form->createView(),
            'back_path' => $config['auth_success']
        ));
    }
    
    protected function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) 
        {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) 
                {
                    $errors[$childForm->getName()] = implode(", ", $childErrors);
                }
            }
        }
        return $errors;
    }
}
