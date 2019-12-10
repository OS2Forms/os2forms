# OS2Forms Drupal module  [![Build Status](https://travis-ci.org/OS2Forms/os2forms.svg?branch=8.x)](https://travis-ci.org/OS2Forms/os2forms)

## Install

OS2Forms Drupal 8 module is available to download via composer.
```
composer require os2forms/os2forms
drush en os2forms
```

If you don't have Drupal installed on you server, you will to need install it first.
Read more about [how to install drupal core](https://www.drupal.org/docs/8/install).

We are recommending to install drupal via composer by using
[OS2Forms  composer project](https://github.com/drupal-composer/drupal-project).
By this way you will get standalone project with OS2Forms module on board, plus
all the other contrib modules you will probably need to configure OS2Forms to
your specific demands.

To get more benefits on your Drupal project we are offering you to use
[OS2web](https://packagist.org/packages/os2web/os2web) as installation
profile for Drupal. This profile is a part of OS2Forms composer project
mentioned above.

You can easy download and install OS2web installation profile to your
composer based Drupal project with commands:
```
composer require os2web/os2web
drush si os2web --db-url=mysql://db_user:db_pass@mysql_host/db_name --locale=da --site-name="OS2Forms" --account-pass=admin -y
```

## Update
Updating process for OS2forms module is similar to usual Drupal 8 module.
Use Composer's built-in command for listing packages that have updates available:

```
composer outdated os2forms/os2forms
```
## Automated testing and code quality
See [OS2Forms testing and CI information](https://github.com/OS2Forms/docs#testing-and-ci)

## Contribution

OS2Forms project is opened for new features and os course bugfixes.
If you have any suggestion or you found a bug in project, you are very welcome
to create an issue in github repository issue tracker.
For issue description there is expected that you will provide clear and
sufficient information about your feature request or bug report.

### Code review policy
See [OS2Forms code review policy](https://github.com/OS2Forms/docs#code-review)

### Git name convention
See [OS2Forms git name convention](https://github.com/OS2Forms/docs#git-guideline)

## Unstable features
### Export submissions to Word
This feature is still not part of Webform and Entity print modules stable versions
due to following issues:
* [[Webform] Unlock possibility of using Entity print module export to Word feature](https://www.drupal.org/project/webform/issues/3096552)
* [[Entity Print] Add Export to Word Support](https://www.drupal.org/project/entity_print/issues/2733781)

To get this functionality on drupal project there will be applied patches from issues above via Composer.

NOTE: If you are downloading os2forms module without using composer, be aware that you have apply those patches by yourself.
