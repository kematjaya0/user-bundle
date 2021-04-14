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
            auth_success: path if login success
   ```
4. update config/packages/security.yml
   ```
   security:
       role_hierarchy:
           # kmj_user default rule is (ROLE_SUPER_USER, ROLE_ADMINISTRATOR, ROLE_USER)
           ROLE_ADMINISTRATOR: ROLE_USER
           ROLE_SUPER_USER: ROLE_ADMINISTRATOR
       encoders:
           Kematjaya\UserBundle\Entity\DefaultUser:
              algorithm: auto
       providers:
           app_user_provider:
               entity:
                    class: Kematjaya\UserBundle\Entity\DefaultUser
                    property: username
       firewalls:
           main:
               logout: 
                   path: kmj_user_logout
               guard:
                   authenticators:
                       - Kematjaya\UserBundle\Security\KmjLoginAuthenticator
   ```
5. import route, update file config/routes/annotations.yaml
   ```
   kmj_user:
    resource: '@UserBundle/Resources/config/routing/all.xml'
   ```
6. for insert demo user, then run on command :
   ```
   php bin/console doctrine:fixtures:load
   ```
   then, use root and admin for login and password: admin123
7. other route :
   ```
   {{ path('kmj_user_profile') }} // for profile user
   {{ path('kmj_user_change_password') }} // for open change password form
   {{ path('kmj_user_logout') }} // for logout 
   ```
