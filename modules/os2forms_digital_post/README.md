# OS2Forms Digital Post

Send Digital Post to danish citizens from a webform.

This module uses the
[SF1601](https://digitaliseringskataloget.dk/integration/sf1601) service from
Serviceplatformen. Information and documentation can be obtained by following
that link.

## Usage

This module provides functionality for sending digital post to danish citizens.
A WebformHandler is provided that you can add to your webform, and if configured
it will send the submitted data as digital post.

## Installation

Enable the module with [`drush`](https://drush.org/)

```shell
drush pm:enable os2forms_digital_post
```

### Example forms

See [OS2Forms Digital Post
examples](modules/os2forms_digital_post_examples/README.md).

## Configuration

Go to `/admin/os2forms_digital_post/settings` to set up global settings for
digital post.

### Key

We use [os2web_key](https://github.com/OS2web/os2web_key) to provide the certificate for sending digital post, and the
key must be of type "[Certificate](https://github.com/os2web/os2web_key?tab=readme-ov-file#certificate)".

### Queue

The actual sending of digital post is handled by jobs in an [Advanced
Queue](https://www.drupal.org/project/advancedqueue) queue.

The default queue, OSForms digital post (`os2forms_digital_post`), must be
processed by a server `cron` job (cf.
`/admin/config/system/queues/manage/os2forms_digital_post?destination=/en/admin/config/system/queues`),
but this can be changed or a completely diffent queue can be used if nedd be.

If using the default queue, it can be processed by running the command

```sh
drush advancedqueue:queue:process os2forms_digital_post
```

List the queue (and all other queues) with

```sh
drush advancedqueue:queue:list
```

or go to `/admin/config/system/queues/jobs/os2forms_digital_post` for a
graphical overview of jobs in the queue.

## Beskedfordeler

The digital post module depends on [Beskedfordeler for
Drupal](https://github.com/itk-dev/beskedfordeler-drupal) to get get
information on how or why not a digital post has been delivered (cf.
[BeskedfordelerEventSubscriber](src/EventSubscriber/BeskedfordelerEventSubscriber.php)).

See the [documentation for Beskedfordeler for
Drupal](https://github.com/itk-dev/beskedfordeler-drupal#beskedfordeler) for
details on how to set up the Beskedfordeler module.

## Testing digital post

This module contains a simple Drush command for sending digital post to a list
of recipients:

``` shell
drush os2forms-digital-post:test:send --help
```

## Fjernprint (physical digital post)

To comply with the address placement in the envelope window (kuvert-rude) an
[event subscriber](src/EventSubscriber/Os2formsDigitalPostSubscriber.php) is
used to inject an address information element into generated HTML before it is
converted to a PDF.

We are only guaranteed to have the necessary information when in a digital
post context. For that reason, the injection of address information is only
done when in a digital post context. Note also that the information is only
injected – it is not styled. This allows flexibility across installations but
also means that it is up to individual installations to style it correctly.
This should be done in OS2Forms Attachment-templates, see
[Overwriting templates](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_attachment#overwriting-templates).

To see the exact requirements for address placement, see
[digst_a4_farve_ej_til_kant_demo_ny_rudeplacering.pdf](docs/digst_a4_farve_ej_til_kant_demo_ny_rudeplacering.pdf).

The injected HTML is on the form:

Without extended address information:

```html
<div id="envelope-window-digital-post">
  <div class="h-card">
    <div class="p-name">Jeppe</div>
    <div><span class="p-street-address">Test vej HouseNr</span></div>
    <div><span class="p-postal-code">2100</span> <span class="p-locality">Copenhagen</span></div>
  </div>
</div>
```

With extended address information:

```html
<div id="envelope-window-digital-post">
    <div class="h-card">
      <div class="p-name">Jeppe</div>
      <div><span class="p-street-address">Test vej HouseNr</span> <span class="p-extended-address">Floor AppartmentNr</span></div>
      <div><span class="p-postal-code">2100</span> <span class="p-locality">Copenhagen</span></div>
    </div>
</div>
```

Without c/o:

```html

<div id="envelope-window-digital-post">
  <div class="h-card">
    <div class="p-name">Jeppe</div>
    <div><span class="p-street-address">Test vej HouseNr</span> <span class="p-extended-address">Floor AppartmentNr</span></div>
    <div><span class="p-postal-code">2100</span> <span class="p-locality">Copenhagen</span></div>
  </div>
</div>
```

With c/o:

```html
<div id="envelope-window-digital-post">
  <div class="h-card">
    <div class="p-name">Jeppe</div>
    <div class="p-name">c/o Mikkel</div>
    <div><span class="p-street-address">Test vej HouseNr</span> <span class="p-extended-address">Floor AppartmentNr</span></div>
    <div><span class="p-postal-code">2100</span> <span class="p-locality">Copenhagen</span></div>
  </div>
</div>
```



