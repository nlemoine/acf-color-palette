<?php

/**
 * Plugin Name:       Advanced Custom Fields: ACF Color Palette
 * Plugin URI:        https://github.com/nlemoine/acf-color-palette
 * Description:       A Gutenberg-like color palette field for ACF, using the colors of the theme.json palettes.
 * x-release-please-start-version
 * Version:           1.0.0
 * x-release-please-end
 * Requires at least: 6.0
 * Requires PHP:      8.3
 * Author:            Nicolas Lemoine
 * Author URI:        https://github.com/nlemoine
 * License:           MIT
 * License URI:       https://opensource.org/license/mit
 * Text Domain:       acf-color-palette
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/nlemoine/acf-color-palette
 * Primary Branch:    main
 * Release Asset:     true
 */

declare(strict_types=1);

// The plugin file registers hooks next to their named callbacks.
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace n5s\AcfColorPalette;

if (!\defined('ABSPATH')) {
    exit;
}

// @bundle-autoload

// Named functions rather than closures, so other plugins can remove these hooks.

// Before ACF registers field types on init 5: the field label is translated then.
\add_action('init', __NAMESPACE__ . '\\load_textdomain', 4);
\add_action('plugins_loaded', __NAMESPACE__ . '\\boot');

function load_textdomain(): void
{
    \load_plugin_textdomain('acf-color-palette', false, \basename(__DIR__) . '/languages');
}

function boot(): void
{
    // The GitHub source archive and Composer sites that never load vendor/autoload.php have no autoloader.
    if (!\class_exists(Plugin::class)) {
        \add_action('admin_notices', __NAMESPACE__ . '\\missing_autoloader_notice');

        return;
    }

    Plugin::getInstance()->init();
}

function missing_autoloader_notice(): void
{
    if (!\current_user_can('activate_plugins')) {
        return;
    }

    \printf(
        '<div class="notice notice-error"><p>%s</p></div>',
        \esc_html__('ACF Color Palette could not load its classes. Install the plugin from the acf-color-palette.zip release asset, or load Composer\'s vendor/autoload.php.', 'acf-color-palette')
    );
}
