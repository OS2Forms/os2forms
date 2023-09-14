# OS2forms 2.1 med Forløb [![Build Status](https://app.travis-ci.com/OS2Forms/os2forms_forloeb.svg?branch=develop)](https://app.travis-ci.com/OS2Forms/os2forms_forloeb)

Adds a Maestro workflow engine and advanced workflow functionality to OS2forms.

## Installing OS2forms 2.1 med Forløb

This module requires the codebase from the [OS2forms core project](https://github.com/OS2Forms/os2forms8) installed per the documentation and by selecting the os2forms_forloeb_profile at installation. After succesful installation you should have the OS2forms med Forløb Module available for install via gui.

You can also install the module by using Drush:

```
./vendor/bin/drush pm:enable os2forms_forloeb
```

-------------------------------------------------------------------------------

## Maestro notifications

Maestro 3.1 adds a `hook_webform_submission_form_alter` hook which we utilize to
send assignment, reminder and escalation notifications by adding a *Maestro
notification* handler to a form that spawns a Maestro workflow or assigns a
task. If the notification recipient is identified by an an email address, the
notification is sent as an email, and if the identifier is a Danish CPR number,
the notifications is sent as digital post.

See [Opret flow-notifikationer](https://os2forms.os2.eu/node/457) (in Danish)
for details.

### Settings

Settings for OS2Forms forløb are defined on `/admin/config/system/os2forms_forloeb`.

#### Known anonymous roles

In order to make the notifications work, Maestro workflow tasks must be assigned
to a *known anonymous role* and these roles are defined under *Known anonymous
roles*.

#### Processing

A notification is not sent to a user immediately, but added to a queue which
must be processed asynchronously. Specify the queue handling notification jobs.

#### Templates

Define templates for emails and digital post (PDF).

### Note on digital post

Digital post is sent using the API provided by the [OS2Forms Digital Post
module](https://github.com/itk-dev/os2forms_digital_post)
(`os2forms_digital_post`) which in turn uses [SF1600: Print på
serviceplatformen](https://digitaliseringskataloget.dk/integration/sf1600). Not
all OS2Forms projects use `os2forms_digital_post` and in the future we should
generalize the API for sending digital post to allow other implementations (not
based on [SF1600](https://digitaliseringskataloget.dk/integration/sf1600)).
