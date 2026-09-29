# user-bundle for symfony 5.4 and 6.4 (PHP >= 7.4 on Symfony 5.4, PHP >= 8.1 on Symfony 6.4)

For symfony 7 use version 7.x, for symfony under 5.2 use version 2.x.

1. Install
   ```
   composer require kematjaya/user-bundle
   ```
2. Enable Bundle
   add to config/bundles.php
   ```
   Kematjaya\UserBundle\UserBundle::class => ['all' => true]
   ```
3. create file config/packages/kmj_user.yml
   ```
   user:
        use_captcha: true
        # role required to reset another user's password (route kmj_user_reset_password)
        reset_password_role: ROLE_ADMINISTRATOR
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
        enable_authenticator_manager: true # symfony 5.4 only, remove on symfony 6
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

11. run the tests in docker (PHP x Symfony matrix):
    ```
    sh docker/test.sh 7.4 5.4   # PHP 7.4 + Symfony 5.4
    sh docker/test.sh 8.1 6.4   # PHP 8.1 + Symfony 6.4
    sh docker/test.sh all       # both
    ```
