# OS2Forms Forløb Change Log
All notable changes to this project should be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

See ["how do I make a good changelog record?"](https://keepachangelog.com/en/1.0.0/#how) 
before starting to add changes.

## [Unreleased]

## 2.5.2 - 27.03.2023

### Updated
- Bumped drupal/ultimate_cron version fixing [Deprecated function: Implicit conversion from float-string](https://www.drupal.org/project/ultimate_cron/issues/3256142). 

## 2.5.1 - 10.03.2023
- Added github action for checking changelog changes when creating pull requests
- Added os2forms/os2forms dependency
- Changed composer patching configuration
- Removed patches that don't belong in this project (Patched correctly in os2forms/os2forms project)
- Added patch for drupal/dynamic_entity_reference
- Remove drupal dependency on user default page module

## 2.5.0 - 11.10.2022

### Added 
- retry task controller action
- Added support for inheriting values without creating a submission

## 2.4.0 

### Added
- Github CI action for checking Drupal Coding standards with PHP Code Sniffer
- Fixed coding standards issues


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
