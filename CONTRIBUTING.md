# Contributing

## Requirements

- PHP 8.3+
- [Composer](https://getcomposer.org/)
- Node.js with [Corepack](https://github.com/nodejs/corepack) enabled, which provides the pnpm version pinned in `package.json`

## Setup

Clone the repository

```bash
git clone git@github.com:nlemoine/acf-color-palette.git
```

Install dependencies

```bash
composer install
pnpm install
```

## Development

Watch assets

```bash
pnpm start
```

Build for production/release (built assets in `assets/dist` are committed)

```bash
pnpm build
```

## Tests

Tests use [Mantle Testkit](https://mantle.alley.com/docs/testing) and run against WordPress and ACF with SQLite, so no database is needed. WordPress is downloaded to `tmp/` on the first run. Each integration runs in its own PHP process, so the plugins it loads cannot affect the other tests: `composer test:core` runs the `unit` and `core` suites with ACF only, `composer test:wpgraphql` and `composer test:admin-columns` load their plugin (the bootstrap reads `ACF_COLOR_PALETTE_TEST_INTEGRATION`), and `composer test` runs all three. `composer test:coverage` covers the core run only.

Tests replace the theme palette with their own, whatever the active theme.

```bash
composer test
```

## Demo

`blueprint.json` builds a [WordPress Playground](https://wordpress.org/playground/) demo from the latest release. To try local changes, build a ZIP the way the release workflow does, and replace the `installPlugin` step of the plugin with an `activatePlugin` step, running the [Playground CLI](https://wordpress.github.io/wordpress-playground/developers/local-development/wp-playground-cli) with `--mount=<build>:/wordpress/wp-content/plugins/acf-color-palette`.

## Static analysis

```bash
composer phpstan
composer rector      # check
composer rector:fix  # apply
```

## Coding standards

PHP code follows the [Syde](https://github.com/inpsyde/phpcs) coding standards. PHPCompatibility also checks that the plugin runs on PHP 8.3.

```bash
composer cs      # check
composer cs:fix  # fix what can be fixed automatically
```

## Translations

After changing translatable strings, regenerate the POT file:

```bash
composer i18n:pot
```

## Commits

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/). [Release Please](https://github.com/googleapis/release-please) uses them to generate the changelog and releases, so use `feat:` and `fix:` for user-facing changes.
