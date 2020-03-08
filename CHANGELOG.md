# Changelog

All notable changes to `currency` will be documented in this file

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



## [0.4.2] - 2020-03-08

### Added
- Laravel 7



## [0.4.1] - 2020-01-27

### Added
- GitHub actions workflows file

### Changed
- Updated the code-of-conduct to https://www.contributor-covenant.org/version/2/0/code_of_conduct.html
- Added Treeware details
- Bumped dependencies
- Updated non-Testbench tests so they could use the non-namespaced phpunit TestCase from old versions of phpunit (because old versions of Testbench require old versions of phpunit). This allowed testing back to Laravel 5.2.



## [0.4.0] - 2019-11-27

### Changed
- Changed the Laravel config file name



## [0.3.1] - 2019-11-13

### Changed
- Added custom exceptions



## [0.3.0] - 2019-11-12

### Added
- Added default_currency_code to the Laravel config file

### Changed (breaking)
- Updated the use of code-distortion/options which has changed ->resolve(x) to be chainable



## [0.2.0] - 2019-11-11

### Added
- Updates to documentation

### Changed (breaking)
- Altered format() to use code-distortion/options based option values
- Changed locale, noBreakWhitespace and decPl to be format-settings
- Swapped instantiation parameters $curCode and $value
- Altered instantiation to not require a curCode - provided a default has been specified

### Fixed
- Changed the Laravel unit-test to an integration-test, and updated it to use the service-provider



## [0.1.0] - 2019-10-29

### Added
- beta release
