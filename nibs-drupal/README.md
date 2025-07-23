Drupal Setup:

1> Follow instructions on this page https://ddev.com/get-started/, to install and start using DDEV. Once OrbStack Docker Provider and DDEV is installed move to step 2>

2> Pull the code down from Repository. Go inside nibs-drupal folder:
    cd nibs-drupal

3> Start ddev:
    ddev start

4> Import DB:
    ddev import-db --file=./backup/db.sql.gz

5> Lunach the site:
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




