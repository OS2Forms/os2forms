# OS2Forms Drupal module  [![Build Status](https://travis-ci.org/OS2Forms/os2forms.svg?branch=8.x)](https://travis-ci.org/OS2Forms/os2forms)

## Install

OS2Forms Drupal 10 module is available to download via composer.

```sh
composer require os2forms/os2forms
drush pm:install os2forms
```

If you don't have Drupal installed on you server, you will to need install it first.
Read more about [how to install drupal core](https://www.drupal.org/docs/getting-started/installing-drupal).

To get more benefits on your Drupal project we recommend you use
[OS2web](https://packagist.org/packages/os2web/os2web) as installation
profile for Drupal.

You can easy download and install OS2web installation profile to your
composer based Drupal project with commands:

```sh
composer require os2web/os2web
drush site:install os2web --db-url=mysql://db_user:db_pass@mysql_host/db_name --locale=da --site-name="OS2Forms" --account-pass=admin -y
```

## Update

Updating process for OS2forms module is similar to usual Drupal 10 module.
Use Composer's built-in command for listing packages that have updates available:

```sh
composer outdated os2forms/os2forms
```

## Automated testing and code quality

See [OS2Forms testing and CI information](https://os2forms.github.io/os2forms-docs/for-developers.html#testing-and-ci)

## Contribution

The OS2Forms project is open for new features and bugfixes. If you have any
suggestion, or you found a bug in the project, you are very welcome to create
an issue in github repository issue tracker. For issue description was ask
that you will provide clear and sufficient information about your feature
request or bug report.

### Code review policy

See [OS2Forms code review policy](https://os2forms.github.io/os2forms-docs/for-developers.html#code-review)

### Git name convention

See [OS2Forms git name convention](https://os2forms.github.io/os2forms-docs/for-developers.html#git-guideline)

## Important notes

### Webforms

Each webform, along with all its settings, is stored as configuration in the
database and can be exported as a `yml` file through Drupal's configuration
management system, making it trackable via `git`.

This means that webform settings in the Drupal database will be synchronized
(exported/imported) with the state defined in `yml` files located in the
configuration folder of your git repository. Without taking the appropriate
precautions, webforms may be deleted or reverted to the state captured in
those `yml` files during synchronization.

To prevent this, we recommend using the
`Config ignore`-[module](https://www.drupal.org/project/config_ignore), which
allows you to exclude specific settings from the configuration management
export/import process.

### Serviceplatformen plugins

Similar to webforms, settings for the CPR and CVR Serviceplatformen plugins
are stored as configuration in the database and can be exported as `yml` files
through Drupal's configuration management system, making them trackable via
`git`.

Note that if your git repository is publicly accessible, these plugin settings
— which may contain sensitive information — will be exposed. As with webforms,
we recommend using the `Config ignore`-module to exclude them from the
export/import process.

### Other configuration

The two cases above are just some examples of configuration that may be
sensitive or subject to unintended changes during synchronization. In general,
any configuration that is environment-specific, contains sensitive data, or is
managed directly in the database rather than through code should be considered
for exclusion via the `Config ignore`-module.

## Unstable features

### Export submissions to Word

This feature is still not part of Webform and Entity print modules stable versions
due to following issues:

* [[Webform] Unlock possibility of using Entity print module export to Word
  feature](https://www.drupal.org/project/webform/issues/3096552)
* [[Entity Print] Add Export to Word
  Support](https://www.drupal.org/project/entity_print/issues/2733781)

To get this functionality on drupal project there will be applied patches from
issues above via Composer.

NOTE: If you are downloading os2forms module without using composer, be aware
that you have apply those patches by yourself.

## Coding standards

Our coding are checked by GitHub Actions (cf.
[.github/workflows/pr.yml](.github/workflows/pr.yml)). Use the commands below to
run the checks locally.

### PHP

```sh
docker compose pull
docker compose run --rm php composer install
# Fix (some) coding standards issues.
docker compose run --rm php composer coding-standards-apply
docker compose run --rm php composer coding-standards-check
```

> [!TIP]
> If the `composer install` commands fails with
>
> > Unable to install module simplesamlphp/simplesamlphp-assets-base, package name must be on the form "VENDOR/simplesamlphp-module-MODULENAME".
>
> you can remove the `vendor` folder and rerun the `composer install` command (cf.
> <https://www.drupal.org/project/simplesamlphp_auth/issues/3350773>).

### Markdown

```sh
docker compose pull
docker compose run --rm markdownlint markdownlint '**/*.md' --fix
docker compose run --rm markdownlint markdownlint '**/*.md'
```

## Code analysis

We use [PHPStan](https://phpstan.org/) for static code analysis.

Running static code analysis on a standalone Drupal module is a bit tricky, so we use a helper script to run the
analysis:

```shell
docker compose run --rm php ./scripts/code-analysis
```

**Note**: Currently the code analysis is only run on the `os2forms_digital_post` and `os2forms_fbs_handler` sub-modules
(cf. [`phpstan.neon`](./phpstan.neon)).
