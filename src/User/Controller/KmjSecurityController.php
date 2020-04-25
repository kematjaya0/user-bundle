<?php

namespace Kematjaya\User\Controller;

use Kematjaya\User\Form\ChangePasswordType;
use Kematjaya\User\Repo\KmjUserRepoInterface;
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
    
    public function login(): Response
    {
        if ($this->getUser()) 
        {
            $config = $this->container->getParameter('kmj_user');
            if(!isset($config['route']['auth_success']))
            {
                throw new \Exception("please set router.auth_succes key under kmj_user config");
            }
            
            $this->addFlash("info", 'wellcome back : '. $this->getUser()->getUsername());
            return $this->redirectToRoute($config['route']['auth_success']);
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('@KmjUser/security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
    
    public function profile()
    {
        return $this->render('@KmjUser/security/profile.html.twig', [
            'kmj_user' => $this->getUser(),
            'title' => 'profile'
        ]);
    }
    
    public function changePassword(
        Request $request)
    {
        if(!$this->getUser()) 
        {
            return $this->redirectToRoute('kmj_user_logout');
        }
        
        $user = $this->userRepo->find($this->getUser()->getId());
        $form = $this->createForm(ChangePasswordType::class, $user, ["action" => $this->generateUrl("kmj_user_change_password")]);
        $form->handleRequest($request);
        if ($form->isSubmitted())
        {
            if($form->isValid()) {
                
                $em = $this->getDoctrine()->getManager();
                $con = $em->getConnection();
                $con->beginTransaction();
                    
                try{
                    $em->persist($form->getData());
                    $em->flush();
                    $con->commit();
                    $config = $this->container->getParameter('kmj_user');
                    if(!isset($config['route']['auth_success']))
                    {
                        throw new \Exception("please set router.auth_succes key under kmj_user config");
                    }

                    $this->addFlash("info", "change password successfully.");
                    return $this->redirectToRoute($config['route']['auth_success']);
                } catch (\Exception $ex) {
                    $con->rollBack();
                    
                    $this->addFlash("error", $ex->getMessages());
                }
            }else{
                $errors = $this->getErrorsFromForm($form);
                
                $this->addFlash("error", implode(", ", $errors));
            }
        }
        
        return $this->render('@KmjUser/security/change_password.html.twig', array(
            'title' => 'change_password',
           'form' => $form->createView() 
        ));
    }
    
    protected function getErrorsFromForm(FormInterface $form)
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = implode(", ", $childErrors);
                }
            }
        }
        return $errors;
    }
}
