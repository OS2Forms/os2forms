# OS2forms 2.1 med Forløb [![Build Status](https://app.travis-ci.com/OS2Forms/os2forms_forloeb.svg?branch=develop)](https://app.travis-ci.com/OS2Forms/os2forms_forloeb)

Adds a Maestro workflow engine and advanced workflow functionality to OS2forms.

## Installing OS2forms 2.1 med Forløb

This module requires the codebase from the [OS2forms core project](https://github.com/OS2Forms/os2forms8) installed per
the documentation and by selecting the os2forms_forloeb_profile at installation. After succesful installation you should
have the OS2forms med Forløb Module available for install via gui.

You can also install the module by using Drush:

```shell
./vendor/bin/drush pm:enable os2forms_forloeb
```

-------------------------------------------------------------------------------

## Maestro notifications

Maestro 3.1 adds a `hook_webform_submission_form_alter` hook which we utilize to
send assignment, reminder and escalation notifications by adding a *Maestro
notification* handler to a form that spawns a Maestro workflow or assigns a
task. If the notification recipient is identified by an email address, the
notification is sent as an email, and if the identifier is a Danish CPR number,
the notifications is sent as digital post.

See [Opret flow-notifikationer](https://os2forms.os2.eu/node/457) (in Danish)
for details.

### Settings

Settings for OS2Forms forløb are defined on `/admin/config/system/os2forms_forloeb`.

#### Known anonymous roles

In order to make the notifications work, Maestro workflow tasks must be assigned to a *known anonymous role* and these
roles are defined under *Known anonymous roles*.

#### Processing

A notification is not sent to a user immediately, but added to a queue which must be processed asynchronously. Specify
the queue handling notification jobs.

#### Templates

Define templates for emails and digital post (PDF).

To reference assets, e.g. stylesheet or images, in your templates,
you can use the `base_url` Twig variable to get the base URL:

```html
<link rel="stylesheet" href="{{ base_url }}/sites/default/templates/notification.html.twig
```

The value of `base_url` is defined in settings.local.php:

```php
/**
 * Base url.
 *
 * Used to specify full URL to stylesheets in templates.
 */
$settings['base_url'] = 'http://nginx:8080';
```
