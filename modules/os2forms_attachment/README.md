# OS2Forms Attachment Drupal module

## Module purpose

The aim of this module is to provide an OS2forms attachment element for adding PDF/HTML attachment.

It also supports creation of reusable headers/footers components which are used when rendering the attachments.

## How does it work

To add custom headers/footer `admin/structure/webform/config/os2forms_attachment_component`

To specify headers/footers that will override the default ones on a global level (**Third party settings** -> **Entity
print** section): `admin/structure/webform/config`

To specify headers/footers that will override the default ones on a form level (**Third party settings** -> **Entity
print** section): ```/admin/structure/webform/manage/[webform]/settings```

## Overwriting templates

With some setups it might be necessary to overwrite templates
in order to access stylesheets or images.

See [templates](modules/os2forms_forloeb/README.md#templates)
for more details on how to do this.
