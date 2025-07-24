# nibs-nisitp
Drupal And Laravel Demo


Drupal Setup:

1> Follow instructions on this page https://ddev.com/get-started/, to install and start using DDEV. Once OrbStack Docker Provider and DDEV is installed move to step 2>

2> Pull the code down from Repository. Go inside 'nibs-drupal' folder by using terminal command:
    cd nibs-drupal

3> Start ddev by running this command in terminal from inside 'nibs-drupal' folder:
    ddev start

4> Import DB by running this command in terminal from inside 'nibs-drupal' folder:
    ddev import-db --file=./backup/db.sql.gz

5> Launch the site by running this command in terminal from inside 'nibs-drupal' folder:
    ddev launch

Site should open in browser. Site url should look like: https://nibs-drupal.ddev.site:PORTNUMBER/



Tools Used:
Ddev / Docker
Drupal 11
Node.js
SASS / CSS
Javascript
Twig
MySQL


Drupal Modules Used:
Admin Toolbar
ctools
Display Suite
PathAuto
Smart Trim
Media
Media Library



Laravel Setup for Feedback REST API:


1> If you haven't already installed OrbStack Docker Provider & DDEV previously, Follow instructions on this page https://ddev.com/get-started/, to install and start using DDEV. Once OrbStack Docker Provider and DDEV is installed move to step 2>

2> If you haven't already Pulled  the code down from Repository, Pull the code down from Repository and  Go inside 'nibs-laravel' folder from the root of the repository using terminal command:
    cd nibs-laravel

3> Once inside folder 'nibs-laravel' run migrations using following command:
    ddev php artisan migrate

4> To test the unit Tests run following command from inside folder 'nibs-laravel':
    ddev php artisan test

5> To launch the app run follwing command from inside folder 'nibs-laravel':
    ddev php artisan serve --port=33002

6> App should come up on url: https://laravel-feedback-api.ddev.site:33002

7> Download Postman app for testing: https://www.postman.com/downloads/

8> Open Postman app and enter this url: https://laravel-feedback-api.ddev.site:33002/api/feedback , in Enter Request URL Field, select the REQUEST to POST

9> Click on Headers Tab and Under key. add 'Accept' and for Value add 'application/json'

10> Click on Body Tab and select 'form-data' radio button. 

11> Under Key add 'name' and Under Value add 'Name'

12> Under Key add 'email' and Under Value add 'email@email.com'

13> Under Key add 'message' and Under Value add 'Test Message Goes Here.'

14> Click 'Send' Button.

15> You can go through 11>, 12>, 13> and 14> with new values of name, email and message to add some more data in.

16> Visit https://laravel-feedback-api.ddev.site:33002/api/feedback in browser, this should show you all Feedback data enterned. 



Tools Used:

Laravel 12
Laravel api features
MySql
Postman for app testing