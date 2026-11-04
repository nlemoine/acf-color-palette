<?php

declare(strict_types=1);

use function Mantle\Testing\manager;

require_once __DIR__ . '/../vendor/autoload.php';
$rootDir = realpath(__DIR__ . '/..');

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv("WP_CORE_DIR=$rootDir/tmp/wordpress");
putenv("CACHEDIR=$rootDir/tmp/test-cache");

// ACF PRO is installed on demand, from the WP Engine repository: see CONTRIBUTING.md.
$acf = (string) getenv('ACF_COLOR_PALETTE_ACF');
if (!in_array($acf, ['', 'pro'], true)) {
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI message.
    throw new InvalidArgumentException("Unknown ACF_COLOR_PALETTE_ACF value: {$acf}.");
}
$acfFile = $acf === 'pro'
    ? "{$rootDir}/wp-content/plugins/advanced-custom-fields-pro/acf.php"
    : "{$rootDir}/wp-content/plugins/advanced-custom-fields/acf.php";
if (!is_file($acfFile)) {
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI message.
    throw new RuntimeException($acf === 'pro'
        ? 'ACF PRO is not installed: run composer require --dev wpengine/advanced-custom-fields-pro, with your license in COMPOSER_AUTH or auth.json.'
        : 'ACF is not installed: run composer install.');
}

// Each integration runs in its own process, so the plugins it loads cannot affect the other tests.
$integration = (string) getenv('ACF_COLOR_PALETTE_TEST_INTEGRATION');
if (!in_array($integration, ['', 'wpgraphql', 'admin-columns'], true)) {
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI message.
    throw new InvalidArgumentException("Unknown ACF_COLOR_PALETTE_TEST_INTEGRATION value: {$integration}.");
}

manager()
    ->with_sqlite()
    ->loaded(static function () use ($integration, $acfFile): void {
        $plugins = __DIR__ . '/../wp-content/plugins';
        require $acfFile;

        if ($integration === 'wpgraphql') {
            require "{$plugins}/wp-graphql/wp-graphql.php";
            require "{$plugins}/wpgraphql-acf/wpgraphql-acf.php";
        }

        if ($integration === 'admin-columns') {
            // Admin Columns only boots in wp-admin; its classes are enough to test the render filter.
            require "{$plugins}/codepress-admin-columns/vendor/autoload.php";
        }

        require __DIR__ . '/../acf-color-palette.php';
    })
    ->install();
