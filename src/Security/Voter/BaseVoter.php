<?php

namespace Kematjaya\UserBundle\Security\Voter;

use Kematjaya\UserBundle\Entity\KmjUserInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BaseVoter extends Voter
{
    const ACTION_CREATE = 'create';
    const ACTION_EDIT   = 'edit';
    const ACTION_VIEW   = 'view';
    const ACTION_DELETE = 'delete';
    
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    
    protected function listActions():array
    {
        return [
            self::ACTION_CREATE, self::ACTION_DELETE,
            self::ACTION_EDIT, self::ACTION_VIEW
        ];
    }
    
    protected function supports(string $attribute, $subject)
    {
        return in_array($attribute, $this->listActions());
    }
    
    protected function isAdministrator()
    {
        return $this->security->isGranted(KmjUserInterface::ROLE_SUPER_USER) 
                or $this->security->isGranted(KmjUserInterface::ROLE_ADMINISTRATOR);
    }
    
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) 
        {
            return false;
        }
        
        switch($attribute)
        {
            case self::ACTION_CREATE:
            case self::ACTION_EDIT:
            case self::ACTION_VIEW:
            case self::ACTION_DELETE:
                return true;
                break;
        }
        
        return false;
    }
    
    public function vote(TokenInterface $token, $subject, array $attributes): int 
    {
        $vote = self::ACCESS_ABSTAIN;

        foreach ($attributes as $attribute) 
        {
            if (!$this->supports($attribute, $subject)) 
            {
                continue;
            }

            $vote = self::ACCESS_DENIED;

            if ($this->voteOnAttribute($attribute, $subject, $token)) 
            {
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }
}
