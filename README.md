## Install

OS2Forms Drupal 8 module is available to download via composer. 
```
composer require os2forms/os2forms
drush en os2forms
```

If you don't have Drupal installed on you server, you will to need install it first. 
Read more about [how to install drupal core](https://www.drupal.org/docs/8/install).

We are recommending to install drupal via composer by using
[drupal-composer project](https://github.com/drupal-composer/drupal-project). 
Example:
```
composer create-project drupal-composer/drupal-project:8.x-dev your-drupal-directory --no-interaction
```
To get more benefits on your Drupal project we are offering you to use 
[OS2Web](https://packagist.org/packages/os2web/os2web)  as installation
profile for Drupal.

You can easy download and install OS2web installation profile to your composer based 
Drupal project with commands:
```
composer require os2web/os2web
drush si os2web --db-url=mysql://db_user:db_pass@mysql_host/db_name --account-pass=admin -y
```

## Update
Updating process for OS2forms module is similar to usual Drupal 8 module.
Use Composer's built-in command for listing packages that have updates available:

```
composer outdated os2forms/os2forms
```
