# user-bundle for symfony > 5.2 (for symfony under 5.2 use 2 version) 
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
            login_success: 
                ## ----- default redirect page ----
                default: dashboard_index
                ## ----- if multiple redirect ----
                roles: 
                    - { role: ROLE_USER, path: homepage }
                    - { role: ROLE_SUPER_USER, path: backend_dashboard }
   ```
4. update config/packages/security.yml
   ```
   security:
        role_hierarchy:
            # kmj_user default rule is (ROLE_SUPER_USER, ROLE_ADMINISTRATOR, ROLE_USER)
            ROLE_ADMINISTRATOR: ROLE_USER
            ROLE_SUPER_USER: ROLE_ADMINISTRATOR
        password_hashers:
            Kematjaya\UserBundle\Entity\KmjUser: auto
        enable_authenticator_manager: true
        providers:
            app_user_provider:
                entity:
                    class: Kematjaya\UserBundle\Entity\KmjUser
                    property: username
        firewalls:
            main:
                logout: 
                    path: kmj_user_logout
                custom_authenticators:
                    - Kematjaya\UserBundle\Security\FormLoginAuthenticator
   ```
5. import route, update file config/routes/annotations.yaml
   ```
   kmj_user:
    resource: '@UserBundle/Resources/config/routing/all.xml'
   ```
6. create entity
   ```
   // src/Entity/MyUser.php
   ..
   use Kematjaya\UserBundle\Entity\DefaultUser;
   ...
   
   class MyUser extends DefaultUser 
   {
        /**
        * @ORM\Column(type="string", length=255, nullable=true)
        */
        private $email;

        public function getEmail(): ?string
        {
            return $this->email;
        }

        public function setEmail(?string $email): self
        {
            $this->email = $email;

            return $this;
        }
   }
   ```
7. update schema:
   ```
   php bin/console doctrine:schema:update --force
   ```
8. for insert demo user, then run on command :
   ```
   php bin/console doctrine:fixtures:load
   ```
   then, use root and admin for login and password: admin123
9. other route :
   ```
   {{ path('kmj_user_user') }}
   {{ path('kmj_user_profile') }} // for profile user
   {{ path('kmj_user_change_password') }} // for open change password form
   {{ path('kmj_user_logout') }} // for logout 
   ```
10, password form theme:
    ```
twig:
    form_themes:
        ## .... for bootstrap 3
        - '@User/form/bootstrap_3_password.html.twig'
        ## .... for bootstrap 4
        - '@User/form/bootstrap_4_password.html.twig'
    ```
