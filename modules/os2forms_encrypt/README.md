# OS2Forms Encrypt module

This module extends and modifies upon [Webform Encrypt](https://www.drupal.org/project/webform_encrypt)
to provide encryption of webform element values in the database.

## Modifications from the base Webform Encrypt module

### Encryption time

Any computed elements, e.g. Computed Twig, may cause issues as
their values are attempted computed after encryption. If any calculations
are done this could result in runtime TypeErrors.

This is handled by modifying the time at which decryption is made, in
`WebformOs2FormsEncryptSubmissionStorage`.

### Permissions

The Webform Encrypt module introduces a `view encrypted values` permission.
This permission should be granted to roles that need to view encrypted values.

**Note**, that in Drupal 9 and newer drush commands are ran as an
anonymous user. This means the anonymous user needs this permission, if
at any point they need values from submissions to do their job.

### Configurable per element

The Webform Encrypt module allows configuration on element level. That is,
webform builders can actively enable and disable for each element.

We want all elements to be encrypted whenever encryption is enabled.
This is done by `os2forms_encrypt_webform_presave` and `os2forms_encrypt_form_alter` in
`os2forms_encrypt.module`.
