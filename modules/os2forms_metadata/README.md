# OS2Forms Metadata

Tracks Webform creation, modification, creator/editor UID and username snapshots,
and the latest completed submission date. Displays the data in the overview and
under **Settings > OS2Forms**.

**Download CSV** exports all accessible forms using semicolons, regardless of
filters or pagination. Dates use Drupal's site timezone and existing permissions
apply throughout.

## Installation

```sh
drush en os2forms_metadata -y
drush cr
```

Existing forms use their current owner as the initial creator/editor. Unknown
historical dates remain empty; the latest submission date comes from surviving
completed submissions. Previously deleted submissions cannot be recovered.

## Behavior

- Every form save updates its modification date and editor.
- Username snapshots survive account renaming and deletion.
- Completed submissions, including tests and completed drafts, update the latest
  submission date. Draft saves and later submission edits do not.
- Deleting submissions or clearing results preserves the latest submission date.
- Duplicated forms receive fresh metadata. Deleting a form removes its metadata.
- Uninstalling the module deletes its metadata table.
