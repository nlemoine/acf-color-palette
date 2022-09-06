<?php

namespace HelloNico\AcfColorPalette;

use WP_Theme_JSON_Resolver;

class ColorPaletteField extends \acf_field
{
    public const RETURN_SLUG = 'slug';

    public const RETURN_ARRAY = 'array';

    public const RETURN_NAME = 'name';

    public const RETURN_COLOR = 'color';

    public const RETURN_FORMATS = [
        self::RETURN_SLUG,
        self::RETURN_ARRAY,
        self::RETURN_NAME,
        self::RETURN_COLOR,
    ];

    public const PALETTE_DEFAULT = 'default';

    public const PALETTE_THEME = 'theme';

    protected string $path;

    protected string $uri;

    /**
     * Create a new field instance.
     *
     * @return void
     */
    public function __construct(string $uri, string $path)
    {
        $this->uri = $uri;
        $this->path = $path;
        parent::__construct();
    }

    public function initialize()
    {
        $this->name = 'color_palette';
        $this->label = \__('Color Palette');
        $this->category = 'jquery';
        $this->defaults = [
            'palettes'       => [],
            'exclude_colors' => [],
            'include_colors' => [],
            'return_format'  => self::RETURN_SLUG,
            'allow_null'     => false,
            'default_value'  => null,
        ];

        if (\is_admin()) {
            \add_filter('acf/get_field_label', [$this, 'add_label_indicator'], 10, 3);
        }
    }

    /**
     * Add color indicator to the field label.
     *
     * @param string $label
     * @param array  $field
     * @param string $context
     *
     * @return void
     */
    public function add_label_indicator($label, $field, $context)
    {
        if ('admin' === $context) {
            return $label;
        }

        if ($field['type'] !== $this->name) {
            return $label;
        }

        $label .= '<span class="component-color-indicator"';
        if ($field['value']) {
            $label .= ' style="background-color: ' . \esc_attr($this->get_color($field['value'], 'color')) . '"';
        }
        $label .= '></span>';

        return $label;
    }

    /**
     * The rendered field type.
     *
     * @param array $field
     *
     * @return void
     */
    public function render_field($field)
    {
        $palettes = $this->get_palettes($field['palettes'] ?? []);

        // Include colors
        if (!empty($field['include_colors'])) {
            $palettes = \array_map(function ($colors) use ($field) {
                return \array_values(\array_filter($colors, function ($color) use ($field) {
                    return \in_array($color['slug'], (array) $field['include_colors'], true);
                }));
            }, $palettes);
        }

        // Exclude colors
        if (!empty($field['exclude_colors'])) {
            $palettes = \array_map(function ($colors) use ($field) {
                return \array_values(\array_filter($colors, function ($color) use ($field) {
                    return !\in_array($color['slug'], (array) $field['exclude_colors'], true);
                }));
            }, $palettes);
        }

        $palettes = \array_filter($palettes);

        if (empty($palettes)) {
            return;
        } ?>
<div>
    <?php foreach ($palettes as $type => $colors): ?>
    <div class="acf-color-palette-item">
        <input type="hidden" name="<?php echo \esc_attr($field['name']); ?>">
        <?php if (!\is_numeric($type) && 1 !== \count($palettes)): ?>
            <div class="components-truncate components-text components-heading">
                <?php if (self::PALETTE_DEFAULT === $type): ?>
                    <?php echo \_x('Default', 'admin color scheme'); ?>
                <?php elseif (self::PALETTE_THEME === $type): ?>
                    <?php echo \__('Theme'); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="components-circular-option-picker">
            <div class="components-circular-option-picker__swatches">
                <?php foreach ($colors as $color) :
                    $id = $field['id'] . '-' . \str_replace(' ', '-', $color['slug']) . '-' . $type; ?>
                    <div class="components-circular-option-picker__option-wrapper" data-color="<?php echo \esc_attr($color['color']); ?>">
                        <input
                            id="<?php echo \esc_attr($id); ?>"
                            type="radio"
                            name="<?php echo \esc_attr($field['name']); ?>"
                            value="<?php echo \esc_attr($color['slug']); ?>"
                            <?php \checked($color['slug'] === $field['value']); ?>
                        />
                        <label
                            for="<?php echo \esc_attr($id); ?>"
                            tabindex="0"
                            title="<?php echo \esc_attr($color['name']); ?>"
                            aria-pressed="false"
                            aria-label="<?php \printf(\__('Color: %s'), $color['name']); ?>"
                            class="components-button components-circular-option-picker__option acf-js-tooltip"
                            style="background-color:<?php echo \esc_attr($color['color']); ?>;color:<?php echo \esc_attr($color['color']); ?>;"
                        ></label>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#000" role="img" aria-hidden="true" focusable="false">
                            <path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z" />
                        </svg>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php if (!empty($field['allow_null'])) : ?>
    <div class="components-circular-option-picker__custom-clear-wrapper">
        <button type="button" class="components-button components-circular-option-picker__clear is-secondary is-small">
            <?php \_e('Clear'); ?>
        </button>
    </div>
<?php endif; ?>
        <?php
    }

    /**
     * The rendered field type settings.
     *
     * @param array $field
     *
     * @return void
     */
    public function render_field_settings($field)
    {
        $colors = [];
        foreach ($this->get_colors() as $color) {
            $colors[$color['slug']] = \sprintf(
                '<span style="display: inline-block;
                    background-color: %s;
                    width: 1.1em;
                    height: 1.1em;
                    margin: -0.2em 3px 0;
                    border: 1px solid #ccd0d4;
                    border-radius: 1.1em;
                    vertical-align: middle;
                    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);"
                ></span> %s',
                $color['color'],
                $color['name']
            );
        }

        \acf_render_field_setting($field, [
            'label'         => \__('Color palettes', 'acf-color-palette'),
            'name'          => 'palettes',
            'instructions'  => \__('Select color palettes.', 'acf-color-palette'),
            'type'          => 'checkbox',
            'ui'            => false,
            'default_value' => 'theme',
            'multiple'      => true,
            'choices'       => [
                self::PALETTE_THEME   => \__('Theme'),
                self::PALETTE_DEFAULT => \_x('Default', 'Default Preset'),
            ],
        ]);

        \acf_render_field_setting($field, [
            'label'         => \__('Exclude Colors', 'acf-color-palette'),
            'name'          => 'exclude_colors',
            'instructions'  => \__('Exclude colors from palette.', 'acf-color-palette'),
            'type'          => 'select',
            'ui'            => true,
            'default_value' => null,
            'allow_null'    => true,
            'multiple'      => true,
            'placeholder'   => \__('Select colors (optional)', 'acf-color-palette'),
            'choices'       => $colors,
        ]);

        \acf_render_field_setting($field, [
            'label'         => \__('Include Colors', 'acf-color-palette'),
            'name'          => 'include_colors',
            'instructions'  => \__('Include colors from palette.', 'acf-color-palette'),
            'type'          => 'select',
            'ui'            => true,
            'default_value' => null,
            'allow_null'    => true,
            'multiple'      => true,
            'placeholder'   => \__('Select colors (optional)', 'acf-color-palette'),
            'choices'       => $colors,
        ]);

        \acf_render_field_setting($field, [
            'label'         => \__('Return Format', 'acf-color-palette'),
            'name'          => 'return_format',
            'instructions'  => \__('The format of the returned data.', 'acf-color-palette'),
            'type'          => 'select',
            'default_value' => $this->defaults['return_format'],
            'ui'            => false,
            'choices'       => [
                self::RETURN_SLUG  => \__('Slug'),
                self::RETURN_ARRAY => \__('Array'),
                self::RETURN_COLOR => \__('Color'),
                self::RETURN_NAME  => \__('Name'),
            ],
        ]);

        // allow_null
        \acf_render_field_setting($field, [
            'label'         => \__('Allow Null?', 'acf'),
            'name'          => 'allow_null',
            'default_value' => $this->defaults['allow_null'],
            'type'          => 'true_false',
            'ui'            => 1,
        ]);

        // default_value
        \acf_render_field_setting($field, [
            'label'        => \__('Default Value', 'acf'),
            'instructions' => \__('Appears when creating a new post', 'acf'),
            'type'         => 'select',
            'name'         => 'default_value',
            'allow_null'   => true,
            'ui'           => true,
            'choices'      => $colors,
        ]);
    }

    /**
     * The formatted field value.
     *
     * @param mixed $value
     * @param int   $post_id
     * @param array $field
     *
     * @return mixed
     */
    public function format_value($value, $post_id, $field)
    {
        if (empty($value) || !\is_string($value)) {
            return null;
        }

        $format = $field['return_format'] ?? $this->defaults['return_format'];

        return $this->get_color($value, self::RETURN_ARRAY === $format ? null : $format);
    }

    /**
     * The condition the field value must meet before
     * it is valid and can be saved.
     *
     * @param bool  $valid
     * @param mixed $value
     * @param array $field
     * @param array $input
     *
     * @return bool
     */
    public function validate_value($valid, $value, $field, $input)
    {
        if (empty($value) && $field['allow_null']) {
            return $valid;
        }

        $colors = $this->get_colors();
        if (!\in_array($value, \array_column($colors, 'slug'), true)) {
            return \sprintf(\__('The color %s is not a valid color', 'acf'), $value);
        }

        return $valid;
    }

    /**
     * The field value after loading from the database.
     *
     * @param mixed $value
     * @param int   $post_id
     * @param array $field
     *
     * @return mixed
     */
    public function load_value($value, $post_id, $field)
    {
        return $value;
    }

    /**
     * The field value before saving to the database.
     *
     * @param mixed $value
     * @param int   $post_id
     * @param array $field
     *
     * @return mixed
     */
    public function update_value($value, $post_id, $field)
    {
        // bail early if is empty
        if (empty($value)) {
            return $value;
        }

        // select -> update_value()
        $value = \acf_get_field_type('select')->update_value($value, $post_id, $field);

        return $value;
    }

    /**
     * The action fired when deleting a field value from the database.
     *
     * @param int    $post_id
     * @param string $key
     *
     * @return void
     */
    public function delete_value($post_id, $key)
    {
        // delete_value($post_id, $key);
    }

    /**
     * The field after loading from the database.
     *
     * @param array $field
     *
     * @return array
     */
    public function load_field($field)
    {
        return $field;
    }

    /**
     * The field before saving to the database.
     *
     * @param array $field
     *
     * @return array
     */
    public function update_field($field)
    {
        return $field;
    }

    /**
     * The action fired when deleting a field from the database.
     *
     * @param array $field
     *
     * @return void
     */
    public function delete_field($field)
    {
        // parent::delete_field($field);
    }

    /**
     * The assets enqueued when rendering the field.
     *
     * @return void
     */
    public function input_admin_enqueue_scripts()
    {
        \wp_enqueue_style($this->name, $this->get_asset_url('field.css'), ['wp-components'], null);
        \wp_enqueue_script($this->name, $this->get_asset_url('field.js'), ['jquery'], null, true);
    }

    /**
     * The assets enqueued when creating a field group.
     *
     * @return void
     */
    public function field_group_admin_enqueue_scripts()
    {
        $this->input_admin_enqueue_scripts();
    }

    /**
     * Get color platte.
     */
    protected function get_palettes(array $palette_types = []): array
    {
        $palettes = [];

        // Validate palette types
        $palette_types = $this->get_palette_types($palette_types);

        // Theme supports theme.json
        if (\class_exists('WP_Theme_JSON_Resolver') && WP_Theme_JSON_Resolver::theme_has_support()) {
            // One palette, get the palette type
            // Needed to avoid merged data of WP_Theme_JSON_Resolver::get_merged_data()
            // e.g. color of theme placed into the default palette if is the same
            if (1 === \count($palette_types)) {
                $type = \reset($palette_types);

                // Get settings
                $settings = [];
                switch ($type) {
                    case self::PALETTE_THEME:
                        $settings = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
                        break;
                    case self::PALETTE_DEFAULT:
                        $settings = WP_Theme_JSON_Resolver::get_core_data()->get_settings();
                        break;
                }

                if (empty($settings)) {
                    return $palettes;
                }

                $palettes[$type] = \_wp_array_get($settings, ['color', 'palette', $type], []);

                return $palettes;
            } else {
                $settings = WP_Theme_JSON_Resolver::get_merged_data()->get_settings();
                $palettes = \_wp_array_get($settings, ['color', 'palette'], []);
                // "core" became "default" in 5.9
                if (\array_key_exists('core', $palettes)) {
                    $palettes[self::PALETTE_DEFAULT] = $palettes['core'];
                    unset($palettes['core']);
                }

                return $palettes;
            }
        }

        return \get_theme_support('editor-color-palette') ?? [];
    }

    /**
     * Validate palette types.
     */
    public function get_palette_types(array $types): array
    {
        return \array_filter($types, function ($type) {
            return \in_array($type, [self::PALETTE_THEME, self::PALETTE_DEFAULT], true);
        });
    }

    /**
     * Get color field.
     *
     * @return mixed|null
     */
    protected function get_color(string $slug, ?string $field = null)
    {
        $colors = $this->get_colors();
        if (empty($colors)) {
            return null;
        }
        foreach ($colors as $color) {
            if (isset($color['slug']) && $color['slug'] === $slug) {
                return null === $field ? $color : $color[$field] ?? null;
            }
        }

        return null;
    }

    /**
     * Merge palette colors.
     */
    protected function get_colors(): array
    {
        $palettes = $this->get_palettes();
        $colors = [];
        foreach ($palettes as $palette) {
            foreach ($palette as $color) {
                $colors[$color['slug']] = $color;
            }
        }

        return $colors;
    }

    /**
     * Get asset url.
     */
    protected function get_asset_url(string $asset): string
    {
        $manifest_path = $this->path . '/assets/dist/manifest.json';
        if (\is_file($manifest_path) && \is_readable($manifest_path)) {
            $manifest = \json_decode(\file_get_contents($manifest_path), true);
            $asset = $manifest[$asset] ?? $asset;
        }

        return $this->uri . '/assets/dist/' . $asset;
    }
}
