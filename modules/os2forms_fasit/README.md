# OS2Forms Fasit

Adds [Fasit Schultz](https://schultz.dk/loesninger/schultz-fasit/) handler for archiving purposes.

For usage in danish, see [BENYTTELSE](docs/BENYTTELSE.md).

## Installation

```shell
drush pm:enable os2forms_fasit
```

## Settings

Go to `/admin/os2forms_fasit/settings` and configure the module.

### Certificate

The certificate must be in `pem` or `cer` format and must be whitelisted by Fasit Schultz. For this the certificate
thumbprint, in lowercase and without colons, is needed.
To get the thumbprint in the correct format from the command line run

```shell
openssl x509 -in SOME_CERTIFICATE.pem -noout -fingerprint |  cut -d= -f2 | sed 's/://g' | tr '[:upper:]' '[:lower:]'
```

Example output:

```shell
6acb261f393172d87fa3997cec86569759a8528a
```

## Queue

Archiving is done via an
[Advanced Queue](https://www.drupal.org/project/advancedqueue)
called `fasit_queue`.

The queue should be processed with `drush`:

```shell
drush advancedqueue:queue:process fasit_queue
```

List the queue (and all other queues) with

```shell
drush advancedqueue:queue:list
```

or go to `/admin/config/system/queues/jobs/fasit_queue`
for a graphical overview of jobs in the queue.

### Cronjob

Consider running the queue via a cronjob.

```cron
*/5 * * * * /path/to/drush advancedqueue:queue:process fasit_queue
```
