<!-- markdownlint-disable MD024 -->
# OS2Forms Change Log

All notable changes to this project should be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

See ["how do I make a good changelog record?"](https://keepachangelog.com/en/1.0.0/#how)
before starting to add changes. Use example [placed in the end of the page](#example-of-change-log-record)

## [Unreleased]

- [#117](https://github.com/OS2Forms/os2forms/pull/117)
  Encrypts all elements if encryption enabled.
- [#114](https://github.com/OS2Forms/os2forms/pull/114)
  Encrypted computed elements.
- [OS-74] Updating DAWA matrikula select with Datafordeler select

## [3.15.3] 2024-06-25

- [OS-74] Replacing DAWA matrikula select with Datafordeler select

## [3.15.2] 2024-05-27

- [#108](https://github.com/OS2Forms/os2forms/pull/108)
  Patched webform encrypt to suppress warning when data is mixed between
  encrypted and not encrypted fields.

## [3.15.1] 2024-05-14

- Added missing return type.
- Added new webform submission storage class to handle enable both encryption
  and revision at the same time.

## [3.15.0] 2024-05-03

- Added webform encryption modules
- Adding Lat and Long fetching to DataAddress
- CprFetchData adding ajax error fix
- [#84](https://github.com/OS2Forms/os2forms/pull/84)
  Added digital post test command.
- Added FBS handler for supporting user creation in library systems
- [#95](https://github.com/OS2Forms/os2forms/pull/95)
  - Added `base_url` variable to twig templates.
  - Handled tokens in Maestro notification html.
- [#92](https://github.com/OS2Forms/os2forms/pull/92)
  Allow denying address protected citizen from webform.
- [#96](https://github.com/OS2Forms/os2forms/pull/96)
  NemLogin autologout pop-up styling.
- [#99](https://github.com/OS2Forms/os2forms/pull/99)
  Fix coding standards.
- [#102](https://github.com/OS2Forms/os2forms/pull/102)
  Fix array access with `purge_days` configuration.

## [3.14.1] 2024-01-16

- CprFetchData adding ajax error fix

## [3.14.0] 2024-01-14

- [OS-64] Setting a standard value for Automatic purge [#80](https://github.com/OS2Forms/os2forms/pull/80)

## [3.13.3] 2023-12-05

- [#76](https://github.com/OS2Forms/os2forms/pull/76)
  Fixed digital post logging on submissions.
- [#74](https://github.com/OS2Forms/os2forms/pull/74)
  Allow composite elements in Maestro notification recipient
- [#73](https://github.com/OS2Forms/os2forms/pull/73a)
  Fix issue with nested elements in webform inherit
- [#77](https://github.com/OS2Forms/os2forms/pull/77)
  Fix color picker fields in os2forms_webform_maps

## [3.13.2] 2023-10-19

- Fixing CPR fetch pattern

## [3.13.1] 2023-10-19

- Checking CPR format before fetching data

## [3.13.0] 2023-10-11

- [#62](https://github.com/OS2Forms/os2forms/pull/62)
  Added digital post module

## [3.12.2] 2023-10-03

- Removing webform_embed - fix

## [3.12.1] 2023-10-02

- os2forms_permissions_by_term: removing node access control - WSOD fix

## [3.12.0] 2023-10-02

- Removing webform_embed
- os2forms_permissions_by_term: removing node access control

## [3.11.0] 2023-09-25

- [OS-58] New company address fields
- Custom permissions by term field
- Removing dependency to config_entity_revisions, webform_revisions, coc_forms_auto_export
- [PR-56](https://github.com/OS2Forms/os2forms/pull/56)
  Handled anonymous users in notifications and flow tasks

## [3.10.0] 2023-08-23

- [OSF-55] DAWA Address-Matrikula (autocomplete) (required)
- [OSF-56] DAWA Address-Matrikula (autocomplete) (value in XML-file)

## [3.9.0] 2023-08-22

- [OS-57] - SBSIP XML element - Computed TWIG

## [3.8.3] 2023-08-17

- Fixed webform fetching from NemID Nemlogin link

## [3.8.2] 2023-08-17

- Fixed webform fetching from NemID Nemlogin link

## [3.8.1] 2023-08-02

- Fixed issue with wrong authorization provider used when multiple are enabled
- Fixed NemID fields populate caching issue

## [3.8.0] 2023-07-12

- [S2FRMS-37] - PDF attachment elements choosing

## [3.7.0] 2023-06-22

- [S2FRMS-18] - Fixing PDF styles

## [3.6.0] 2023-06-07

- [OSF-25] added modules/os2forms_forloeb
- [OSF-25] added modules/os2forms_permissions_by_term
- [OSF-25] added modules/os2forms_webform_list

## [3.5.0] 2023-04-25

- Added SessionDynamicValue webform element
- Fixed `Undefined array key` in os2forms_attachment module
- Added Maps element (<https://github.com/OS2Forms/os2forms/pull/39>).
- Added missing dependency
- Added changes to Map element after external review
- Fixed non-existent service "entity.manager" in webform_embed module

## [3.4.0] 2023-02-15

- Added github action for checking changelog changes when creating pull requests
- Added webform_embed as custom module and removed from composer
- Added cweagans/composer-patches as dependency
- Removed vaimo/composer-patches as dependency
- Changed composer patching configuration slightly
- Applied coding standards. Updated GitHub Actions.
- Removed NemID authentication message from AJAX requests
- Added OS2forms consent module (OS-36)
- Added GIT tag indicator (OS-34)
- Added PDF author, subject and keywords (OS-26)
- Added CVR datafordeler webservice (OS2FORMS-358)
- Added P-Number webservice (OS2FORMS-358)
- os2forms migrate_to_category default value fix (#17 issue)

## [3.3.0] 2022-12-22

- Added OS2Forms attachment component (with custom heards, footer and colophon) (OS2FORMS-361)
- Nemlogin link in shared webforms fix (OS-11)
- Updated new CPR lookup method (OS2FORMS-359)
- Added settings tab for all OS2forms settings (OS-25)

## [3.2.9] 2022-09-21

- SBSys file default name (AOP-664-86774)
- Allowed plugins section to composer.json. Fixes issues with github actions flow

## [3.2.8] 2022-08-11

- Added Webform Remote Select (webform_remote_select) as dependency (OS2FORMS-384)

## [3.2.7] 2022-06-29

### Added

- New "CPR / Navn validering" webforms element for easy person validation by CPR
  and Name (OS2FORMS-372)

### Fixed

- Codingstandard issues (OS2FORMS-380)
- NemID code file support  - company login, when CPR is also available

## [3.2.6] 2022-06-22

### Fixed

- Setting unique names to P-numner/CPR fetch buttons

## [3.2.5] - 2022-06-22

### Added

- Github CI action for checking Drupal Coding standards with PHP Code Sniffer
- Adding CPR fetch field

## See previous change log description on [Github release page](https://github.com/OS2Forms/os2forms/releases)

## Example of change log record

```markdown
## [x.x.x] Release name

### Added

- Description on added functionality.

### Changed/Updated

- Description on changed/updated functionality.

### Deprecated

- Description of soon-to-be removed features.

### Removed

- Description of removed features.

### Fixed

- Decription of bug fixes.

### Security

- Security in case of vulnerabilities.
```

[Unreleased]: https://github.com/OS2Forms/os2forms/compare/3.15.2...HEAD
[3.15.2]: https://github.com/OS2Forms/os2forms/compare/3.15.1...3.15.2
[3.15.1]: https://github.com/OS2Forms/os2forms/compare/3.15.0...3.15.1
[3.15.0]: https://github.com/OS2Forms/os2forms/compare/3.14.1...3.15.0
[3.14.1]: https://github.com/OS2Forms/os2forms/compare/3.14.0...3.14.1
[3.14.0]: https://github.com/OS2Forms/os2forms/compare/3.13.3...3.14.0
[3.13.3]: https://github.com/OS2Forms/os2forms/compare/3.13.2...3.13.3
[3.13.2]: https://github.com/OS2Forms/os2forms/compare/3.13.1...3.13.2
[3.13.1]: https://github.com/OS2Forms/os2forms/compare/3.13.0...3.13.1
[3.13.0]: https://github.com/OS2Forms/os2forms/compare/3.12.2...3.13.0
[3.12.2]: https://github.com/OS2Forms/os2forms/compare/3.12.1...3.12.2
[3.12.1]: https://github.com/OS2Forms/os2forms/compare/3.12.0...3.12.1
[3.12.0]: https://github.com/OS2Forms/os2forms/compare/3.11.0...3.12.0
[3.11.0]: https://github.com/OS2Forms/os2forms/compare/3.10.0...3.11.0
[3.10.0]: https://github.com/OS2Forms/os2forms/compare/3.9.0...3.10.0
[3.9.0]: https://github.com/OS2Forms/os2forms/compare/3.8.3...3.9.0
[3.8.3]: https://github.com/OS2Forms/os2forms/compare/3.8.2...3.8.3
[3.8.2]: https://github.com/OS2Forms/os2forms/compare/3.8.1...3.8.2
[3.8.1]: https://github.com/OS2Forms/os2forms/compare/3.8.0...3.8.1
[3.8.0]: https://github.com/OS2Forms/os2forms/compare/3.7.0...3.8.0
[3.7.0]: https://github.com/OS2Forms/os2forms/compare/3.6.0...3.7.0
[3.6.0]: https://github.com/OS2Forms/os2forms/compare/3.5.0...3.6.0
[3.5.0]: https://github.com/OS2Forms/os2forms/compare/3.4.0...3.5.0
[3.4.0]: https://github.com/OS2Forms/os2forms/compare/3.3.0...3.4.0
[3.3.0]: https://github.com/OS2Forms/os2forms/compare/3.2.9...3.3.0
[3.2.9]: https://github.com/OS2Forms/os2forms/compare/3.2.8...3.2.9
[3.2.8]: https://github.com/OS2Forms/os2forms/compare/3.2.7...3.2.8
[3.2.7]: https://github.com/OS2Forms/os2forms/compare/3.2.6...3.2.7
[3.2.6]: https://github.com/OS2Forms/os2forms/compare/3.2.5...3.2.6
[3.2.5]: https://github.com/OS2Forms/os2forms/compare/3.2.4...3.2.5
