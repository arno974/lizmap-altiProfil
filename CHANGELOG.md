# Changelog

<!--
Format from https://keepachangelog.com/en/1.0.0/
added, fixed, changed, removed, deprecated, security
with some extra keywords : backend, tests, test, translation, funders, important
-->

## Unreleased

### Added

### Changed

## 0.4.1 - 2024-03-28

### Fixed

* fix click unavailable when layers use popup (altprofil now enable/disable popup when active)

## 0.4.0 - 2024-02-07

### Added

* Compatibility with Lizmap Web Client 3.7

### Fixed

* Fix a Javascript error if the map projection was in EPSG:4326

## 0.3.2 - 2023-02-27

### Changed

* Fix: altiprofil did not respond, because of some syntax issues with some class names.

## 0.3.1 - 2023-02-27

### Changed

* Fix the display of the menu icon in Lizmap 3.6
* Increase required lizmap version to 3.7

## 0.3.0 - 2022-12-15

### Added

* Compatibility with Lizmap Web Client 3.6

### Changed

* Some code refactoring

### Tests

* Add a docker stack for testing the module

## 0.2.3 - 2022-03-11

* Fix IGN service Provider and set it as default provider
* Allow a setting for the DEM resolution

## 0.2.2 - 2021-04-09

* Minor changes on admin menu and add tooltip information
* Configuration to install the module with Composer
* Improve the documentation about the installation

## 0.2.1 - 2020-08-12

* New administration module to edit the configuration
* Add new option dock to choose target dock: `dock`, `minidock`, `rightdock`
* Add choice of unit for slope calculation

## 0.2.0 - 2020-08-12

* New administration module allowing to modify the configuration with a form.
  The configuration is written in a dedicated file instead of `localconfig.ini.php`

## 0.1.0 - 2020-06-22

* First release
