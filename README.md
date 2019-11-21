#OS2Forms Drupal module  [![Build Status](https://travis-ci.org/OS2Forms/os2forms.svg?branch=8.x)](https://travis-ci.org/OS2Forms/os2forms)

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

To get more benefits on your Drupal project we are offering you to use
[OS2web](https://packagist.org/packages/os2web/os2web) as installation
profile for Drupal.

You can easy download and install OS2web installation profile to your
composer based Drupal project with commands:
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
## Automated testing and code quality

This project has continuous integration builds that are performing by [Travis CI](https://travis-ci.org).
To improve code quality and integration possibilities there are using set of following tools:
 * [PHP_CodeSniffer]() with [Drupal coding standards](https://www.drupal.org/docs/develop/standards/coding-standards) and best practices defined in [Coder module](https://www.drupal.org/project/coder).
 * [ESLint](https://eslint.org/) with [Drupal ESLint rules set](https://www.drupal.org/node/1955232).
 * [Stylelint](https://stylelint.io/) with rules set defined for Drupal core.
 * [Twigcs](https://github.com/friendsoftwig/twigcs) with standard set of rules
  for twig templates.
 * [Drupl-check](https://github.com/mglaman/drupal-check) to check project
 readiness to Drupal 9 via checking of deprecated code usage.
 * @TODO [PHPUnit](https://phpunit.de/) test to check key contrib modules tests.

For more details about travis-ci continuous integration builds
see `.travis-ci.yml` file.

NOTE: Project doesn't have its own PHPUnit test. This is a part of future
development scope.

## Contribution

OS2Forms project is opened for new features and os course bugfixes.
If you have any suggestion or you found a bug in project, you are very welcome
to [create an issue](https://github.com/OS2Forms/os2forms/issues) in github.
For issue description there is expected that you will provide clear and
sufficient information about your feature request or bug report.

### Code review policy
New changes or bugfixes in existing codebase have to be added to repository
through [code review process](https://github.com/features/code-review/).
To request a code review, use the following process:
1. Add Github pull request from the feature/bugfix branch to 8.x or other related dev branch.
2. Request code review from one of project contributor.
3. Reviewer approves, requests changes or rejects pull request.
4. Discuss/Add requested changes or merge approved pull request.

NOTE: There are preconditions that have to be met before accepting a pull request:
- All requested changes have to be done
- All discussion have to be resolved
- Pull request should have green Travis CI build status

### Git name convention

Since OS2Forms is Drupal module project, there is used drupal-friendly
git branch/tag names.
* 8.x, 8.x-2.x - development branches.
* 8.x-2.0-alpha, 8.x-2.0-alpha1, 8.x-2.0-beta - test release tags.
* 8.x-2.0-rc1, 8.x-2.0-rc2 - release candidate tags.
* 8.x-2.0, 8.x-2.1 - stable release tags.

There is no specific rules for feature branch names. However we recommend
use [OS2Forms JIRA](https://os2web.atlassian.net/browse/OS2FORMS) or
[github issue](https://github.com/OS2Forms/os2forms/issues) ticket number
as prefix for your branch name.
