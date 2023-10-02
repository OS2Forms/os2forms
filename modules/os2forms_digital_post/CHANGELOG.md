<!-- markdownlint-disable MD024 -->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic
Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.1]

### Fixed

- Fixed issue with wrong service being injected.
  [PR-50](https://github.com/itk-dev/os2forms_digital_post/pull/50)

## [3.0.0]

### Added

- Added API for sending digital post
  [PR-40](https://github.com/itk-dev/os2forms_digital_post/pull/40)

### Changed

- Changed dependency on CPR and CVR lookup modules. Handled physical post
  (“forsendelse”)
  [PR-37](https://github.com/itk-dev/os2forms_digital_post/pull/37)

### Removed

- Removed support for [SF-1600](https://digitaliseringskataloget.dk/integration/sf1600).

## [2.0.2]

### Added

- Added the `CPR / Navn validering` element to allowed recipient element names
  [PR-43](https://github.com/itk-dev/os2forms_digital_post/pull/43)

## Changed

- Changed composer name to `os2forms/os2forms_digital_post`
  [PR-47](https://github.com/itk-dev/os2forms_digital_post/pull/47)

## [2.0.1]

## Changed

- Updated allowed attachment elements to contain `os2forms_attachment`

## [2.0.0]

### Changed

- Updates `dompdf/dompdf` requirement to `^2.0`
  [PR-41](https://github.com/itk-dev/os2forms_digital_post/pull/41)

## [1.2.3]

### Changed

- Updated recipient element names
  [PR-38](https://github.com/itk-dev/os2forms_digital_post/pull/38)

## [1.2.2]

### Added

- Added creation of Beskedfordeler table.

## [1.2.0]

### Added

- Added handling of CVR recipients.
- Added handling of Beskedfordeler messages.

### Changed

- Update dompdf dependency.

## [1.1.2]

- Updated logging.
- Fixed setting person id

## [1.1.1]

### Changed

- Remove CPR from exception
- Added more recipient field types
- Fixed error logging.

## [1.1.0]

### Added

- Added support for [SF1601 »
  “KombiPostAfsend”](https://digitaliseringskataloget.dk/integration/sf1601).
- Added GitHub Actions for coding standards checks and code analysis.

[Unreleased]: https://github.com/itk-dev/os2forms_digital_post/compare/3.0.1...HEAD
[3.0.1]: https://github.com/itk-dev/os2forms_digital_post/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/itk-dev/os2forms_digital_post/compare/2.0.2...3.0.0
[2.0.2]: https://github.com/itk-dev/os2forms_digital_post/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/itk-dev/os2forms_digital_post/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/itk-dev/os2forms_digital_post/compare/1.2.3...2.0.0
[1.2.3]: https://github.com/itk-dev/os2forms_digital_post/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/itk-dev/os2forms_digital_post/compare/1.2.0...1.2.2
[1.2.0]: https://github.com/itk-dev/os2forms_digital_post/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/itk-dev/os2forms_digital_post/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/itk-dev/os2forms_digital_post/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/itk-dev/os2forms_digital_post/compare/1.0.2...1.1.0
