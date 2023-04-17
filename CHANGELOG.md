# OS2Forms Change Log
All notable changes to this project should be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

See ["how do I make a good changelog record?"](https://keepachangelog.com/en/1.0.0/#how)
before starting to add changes. Use example [placed in the end of the page](#example-of-change-log-record)

## [Unreleased]
- Fixed `Undefined array key` in os2forms_attachment module

- Added Maps element (<https://github.com/OS2Forms/os2forms/pull/39>).

- Added missing dependency

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
- New "CPR / Navn validering" webforms element for easy person validation by CPR and Name (OS2FORMS-372)

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
```
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
[Unreleased]: https://github.com/OS2Forms/os2forms/compare/3.2.6...HEAD
[3.2.6]: https://github.com/OS2Forms/os2forms/compare/3.2.5...3.2.6
[3.2.5]: https://github.com/OS2Forms/os2forms/compare/3.2.4...3.2.5
