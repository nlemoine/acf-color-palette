<?php

/**
 * Plugin Name:       Advanced Custom Fields: ACF Color Palette
 * Plugin URI:        https://github.com/nlemoine/acf-color-palette
 * Description:       A Gutenberg like color palette field for ACF
 * Version:           0.2.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Nicolas Lemoine
 * Author URI:        https://github.com/nlemoine
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       acf-color-palette
 * Domain Path:       /languages.
 * GitHub Plugin URI: https://github.com/nlemoine/acf-color-palette
 */
add_filter('after_setup_theme', new class() {
    /**
     * Invoke the plugin.
     *
     * @return void
     */
    public function __invoke()
    {
        if (!class_exists('acf_field')) {
            return;
        }

        require_once __DIR__.'/src/ColorPaletteField.php';

        add_filter('acf/include_field_types', [$this, 'register_field']);

        if (defined('ACP_FILE')) {
            add_filter('ac/column/value', [$this, 'admin_column'], 10, 3);
        }
    }

    public function register_field($acfMajorVersion)
    {
        $field = new HelloNico\AcfColorPalette\ColorPaletteField(
            untrailingslashit(plugin_dir_url(__FILE__)),
            untrailingslashit(plugin_dir_path(__FILE__))
        );
        acf_register_field_type($field);
    }

    /**
     * Hook the Admin Columns Pro plugin to provide basic field support
     * if detected on the current WordPress installation.
     *
     * @return void
     */
    protected function admin_column($value, $id, $column)
    {
        if (
            !is_a($column, '\ACA\ACF\Column')
            || 'country' !== $column->get_acf_field_option('type')
        ) {
            return $value;
        }

        return get_field($column->get_meta_key()) ?? $value;
    }
});
