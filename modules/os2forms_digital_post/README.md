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

Thie digital post module depends on [Beskedfordeler for
Drupal](https://github.com/itk-dev/beskedfordeler-drupalon) to get get
information on how or why not a digital post has been delivered (cf.
[BeskedfordelerEventSubscriber](src/EventSubscriber/BeskedfordelerEventSubscriber.php)).

See the [documentation for Beskedfordeler for
Drupal](https://github.com/itk-dev/beskedfordeler-drupal#beskedfordeler) for
details on how to set up the Beskedfordeler module.
