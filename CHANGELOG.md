# Changelog

## [1.0.0](https://github.com/nlemoine/acf-color-palette/compare/0.1.0...1.0.0) (2026-11-04)


### ⚠ BREAKING CHANGES

* the Composer package is now n5s/acf-color-palette. Composer sites must replace hellonico/acf-color-palette with it.
* requires PHP 8.3, WordPress 6.0 and ACF 6.0. The main plugin file was renamed, so the plugin must be reactivated. Classes moved to the n5s\AcfColorPalette namespace without aliases, asset handles changed to acf-color-palette-field, and installs need the release ZIP or Composer's autoloader. See UPGRADE-1.0.md.

### Features

* modernize the plugin on the acf-country 4.x model ([b5560d0](https://github.com/nlemoine/acf-color-palette/commit/b5560d0059f5df2417a8c69626e5a3249928d9e3))


### Bug Fixes

* notify ACF when the Clear button empties the field ([b69ea5f](https://github.com/nlemoine/acf-color-palette/commit/b69ea5f693028a4e977270a9423a467d4b0d6de6))


### Build System

* rename the Composer package to n5s/acf-color-palette ([e165861](https://github.com/nlemoine/acf-color-palette/commit/e16586125d28d9fb9e845291e365cba8ae4a2b87))
