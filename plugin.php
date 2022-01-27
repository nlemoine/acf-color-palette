<?php

/**
 * Plugin Name:       Advanced Custom Fields: ACF Color Palette
 * Plugin URI:        https://github.com/nlemoine/acf-color-palette
 * Description:       A Gutenberg like color palette field for ACF
 * Version:           0.2.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            Nicolas Lemoine
 * Author URI:        https://github.com/nlemoine
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       acf-color-palette
 * Domain Path:       /languages.
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

        require_once 'src/ColorPaletteField.php';

        $this->register();

        if (defined('ACP_FILE')) {
            $this->hookAdminColumns();
        }
    }

    /**
     * Register the field type with ACF.
     *
     * @return void
     */
    protected function register()
    {
        foreach (['acf/include_field_types', 'acf/register_fields'] as $hook) {
            add_filter($hook, function () {
                return new HelloNico\AcfColorPalette\ColorPaletteField(
                    untrailingslashit(plugin_dir_url(__FILE__)),
                    untrailingslashit(plugin_dir_path(__FILE__))
                );
            });
        }
    }

    /**
     * Hook the Admin Columns Pro plugin to provide basic field support
     * if detected on the current WordPress installation.
     *
     * @return void
     */
    protected function hookAdminColumns()
    {
        add_filter('ac/column/value', function ($value, $id, $column) {
            if (
                !is_a($column, '\ACA\ACF\Column')
                || 'color_palette' !== $column->get_acf_field_option('type')
            ) {
                return $value;
            }

            return get_field($column->get_meta_key()) ?? $value;
        }, 10, 3);
    }
});
