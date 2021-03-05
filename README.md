# user-bundle for symfony 4 or symfony 5
1. Install
   ```
   composer require kematjaya/user-bundle
   ```
2. Enable Bundle
   add to config/bundles.php
   ```
   Kematjaya\User\KmjUserBundle::class => ['all' => true]
   ```
3. create file config/packages/kmj_user.yml
   ```
   user:
        route:
            login: kmj_user_login
            auth_success: path if login success
   ```
4. create entity src/Entity/MyUser.php
   ```
   <?php

    namespace App\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use Kematjaya\UserBundle\Entity\KmjUserInterface;

    /**
     * @ORM\Entity(repositoryClass="App\Repository\MyUserRepository")
     */
    class MyUser implements KmjUserInterface
    {
        /**
         * @ORM\Id()
         * @ORM\GeneratedValue()
         * @ORM\Column(type="integer")
         */
        private $id;

        /**
         * @ORM\Column(type="string", length=180, unique=true)
         */
        private $username;

        /**
         * @ORM\Column(type="json")
         */
        private $roles = [];

        /**
         * @var string The hashed password
         * @ORM\Column(type="string")
         */
        private $password;

        /**
         * @ORM\Column(type="string", length=255)
         */
        private $name;

        /**
         * @ORM\Column(type="boolean")
         */
        private $is_active;

        public function getId(): ?int
        {
            return $this->id;
        }

        /**
         * A visual identifier that represents this user.
         *
         * @see UserInterface
         */
        public function getUsername(): string
        {
            return (string) $this->username;
        }

        public function setUsername(string $username): self
        {
            $this->username = $username;

            return $this;
        }

        /**
         * @see UserInterface
         */
        public function getRoles(): array
        {
            $roles = $this->roles;
            if(empty($roles))
            {
               $roles[] = self::ROLE_USER;
            }
            
            return array_unique($roles);
        }

        public function setRoles(array $roles): self
        {
            $this->roles = $roles;

            return $this;
        }

        /**
         * @see UserInterface
         */
        public function getPassword(): string
        {
            return (string) $this->password;
        }

        public function setPassword(string $password): self
        {
            $this->password = $password;

            return $this;
        }

        /**
         * @see UserInterface
         */
        public function getSalt()
        {
            // not needed when using the "bcrypt" algorithm in security.yaml
        }

        /**
         * @see UserInterface
         */
        public function eraseCredentials()
        {
            // If you store any temporary, sensitive data on the user, clear it here
            $this->is_active = false;
        }

        public function getName(): ?string
        {
            return $this->name;
        }

        public function setName(string $name): KmjUserInterface
        {
            $this->name = $name;

            return $this;
        }

        public function getIsActive(): ?bool
        {
            return $this->is_active;
        }

        public function setIsActive(bool $is_active): KmjUserInterface
        {
            $this->is_active = $is_active;

            return $this;
        }
    }

   ```
5. update config/packages/security.yml
   ```
   security:
       role_hierarchy:
           # kmj_user default rule is (ROLE_SUPER_USER, ROLE_ADMINISTRATOR, ROLE_USER)
           ROLE_ADMINISTRATOR: ROLE_USER
           ROLE_SUPER_USER: ROLE_ADMINISTRATOR
       encoders:
           App\Entity\MyUser:
              algorithm: auto
       providers:
           app_user_provider:
               entity:
                    class: App\Entity\MyUser
                    property: username
       firewalls:
           main:
               logout: 
                   path: kmj_user_logout
               guard:
                   authenticators:
                       - Kematjaya\UserBundle\Security\KmjLoginAuthenticator
   ```
6. import route, update file config/routes/annotations.yaml
   ```
   kmj_user:
    resource: '@UserBundle/Resources/config/routing/all.xml'
   ```
7. update user repo, src/Repository/MyUserRepository.php
   ```
   use App\Entity\MyUser;
   use Kematjaya\UserBundle\Entity\KmjUserInterface;
   use Kematjaya\UserBundle\Repo\KmjUserRepoInterface;
   ...
   class MyUserRepository extends ServiceEntityRepository implements KmjUserRepoInterface
   {
       ....
       public function createUser(): KmjUserInterface 
       {
            return new MyUser();
       }  
       
       public function findOneByUsernameAndActive(string $username): ?KmjUserInterface 
       {
            return $this->findOneBy(['username' => $username, 'is_active' => true]);
       }

       public function findOneByIdentityNumber(string $identityNumber): ?KmjUserInterface 
       {   
            return $this->find($identityNumber);
       }
   }
   ```
8. add Repo to Service, config/services.yml
   ```
   services:
       ....
       Kematjaya\UserBundle\Repo\KmjUserRepoInterface:
           class: App\Repository\MyUserRepository
   ```
9. for insert demo user, then run on command :
   ```
   php bin/console doctrine:fixtures:load
   ```
   then, use root and admin for login and password: admin123
10. other route :
   ```
   {{ path('kmj_user_profile') }} // for profile user
   {{ path('kmj_user_change_password') }} // for open change password form
   {{ path('kmj_user_logout') }} // for logout 
   ```
