<?php

namespace HelloNico\AcfColorPalette;

class ColorPaletteField extends \acf_field
{
    /**
     * The field name.
     *
     * @var string
     */
    public $name = 'color_palette';

    /**
     * The field label.
     *
     * @var string
     */
    public $label = 'Color Palette';

    /**
     * The field category.
     *
     * @var string
     */
    public $category = 'jquery';

    /**
     * The field defaults.
     *
     * @var array
     */
    public $defaults = [
        'exclude_colors' => [],
        'include_colors' => [],
        'return_format'  => 'slug',
        'allow_null'     => false,
        'default_value'  => null,
    ];

    /**
     * Create a new field instance.
     *
     * @param string $uri
     * @param string $path
     *
     * @return void
     */
    public function __construct($uri, $path)
    {
        $this->uri = $uri;
        $this->path = $path;

        parent::__construct();
    }

    public function initialize()
    {
        \add_filter('acf/get_field_label', [$this, 'add_label_indicator'], 10, 3);
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
            $label .= ' style="background-color: '.\esc_attr($this->get_color($field['value'], 'color')).'"';
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
        $colors = $this->get_colors();

        // Include colors
        if (!empty($field['include_colors'])) {
            $colors = \array_values(\array_filter($colors, function ($color) use ($field) {
                return \in_array($color['slug'], (array) $field['include_colors'], true);
            }));
        }

        // Exclude colors
        if (!empty($field['exclude_colors'])) {
            $colors = \array_values(\array_filter($colors, function ($color) use ($field) {
                return !\in_array($color['slug'], (array) $field['exclude_colors'], true);
            }));
        }

        if (empty($colors)) {
            return;
        } ?>
<div class="components-circular-option-picker">
    <div class="components-circular-option-picker__swatches">
        <?php foreach ($colors as $color) :
            $id = $field['id'].'-'.\str_replace(' ', '-', $color['slug']); ?>
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
        <?php if (!empty($field['allow_null'])) : ?>
            <div class="components-circular-option-picker__custom-clear-wrapper">
                <button type="button" class="components-button components-circular-option-picker__clear is-secondary is-small">
                    <?php \_e('Clear color'); ?>
                </button>
            </div>
        <?php endif; ?>
</div>
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
        foreach ($this->get_colors() as $item) {
            $colors[$item['slug']] = \sprintf(
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
                $item['color'],
                $item['name']
            );
        }

        // acf_render_field_setting($field, [
        //     'label' => __('Color palette sources', 'acf-color-palette'),
        //     'name' => 'palette_sources',
        //     'instructions' => __('Select color palette sources.', 'acf-color-palette'),
        //     'type' => 'checkbox',
        //     'ui' => false,
        //     'default_value' => 'theme',

        //     'multiple' => true,
        //     'choices' => [
        //         'theme' => __('Theme colors', 'acf-color-palette'),
        //         'custom' => __('Custom colors', 'acf-color-palette'),
        //     ],
        // ]);

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
                'slug'  => 'Slug',
                'array' => 'Array',
                'color' => 'Color',
                'name'  => 'Name',
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
        $format = $field['return_format'] ?? $this->defaults['return_format'];

        return $this->get_color($value, 'array' === $format ? null : $format);
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
    protected function get_colors(): array
    {
        if (\function_exists('wp_get_global_settings')) {
            return \wp_get_global_settings(['color', 'palette', 'theme']);
        }

        return \get_theme_support('editor-color-palette')[0] ?? [];
    }

    /**
     * Get color field.
     *
     * @param string $field
     *
     * @return mixed|null
     */
    protected function get_color(string $slug, $field = null)
    {
        $colors = $this->get_colors();
        if (empty($colors)) {
            return null;
        }
        foreach ($colors as $color) {
            if ($color['slug'] === $slug) {
                return null === $field ? $color : $color[$field] ?? null;
            }
        }

        return null;
    }

    /**
     * Get asset url.
     */
    protected function get_asset_url(string $asset): string
    {
        $manifest_path = $this->path.'/assets/dist/manifest.json';
        if (\is_file($manifest_path) && \is_readable($manifest_path)) {
            $manifest = \json_decode(\file_get_contents($manifest_path), true);
            $asset = $manifest[$asset] ?? $asset;
        }

        return $this->uri.'/assets/dist/'.$asset;
    }
}
