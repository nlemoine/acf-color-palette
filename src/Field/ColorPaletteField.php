<?php

declare(strict_types=1);

// acf_field method parameters stay untyped: a future ACF version could declare them untyped.
// phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType

namespace n5s\AcfColorPalette\Field;

use acf_field;
use n5s\AcfColorPalette\Palette;
use n5s\AcfColorPalette\Palettes;
use n5s\AcfColorPalette\ReturnFormat;

/**
 * The ACF "color_palette" field: radio swatches of the theme.json palette colors, storing the color slug.
 *
 * ACF method parameters stay untyped: a future ACF version could declare them untyped in acf_field.
 *
 * @phpstan-type Field array<string, mixed>
 * @phpstan-import-type Color from Palettes
 */
class ColorPaletteField extends acf_field
{
    /**
     * @var bool
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint -- acf_field declares it untyped.
    public $show_in_rest = true;

    private const string ASSET_HANDLE = 'acf-color-palette-field';

    private const string CURRENT_GROUP = 'current';

    /**
     * @var array<mixed>|null
     */
    private ?array $manifest = null;

    public function __construct(
        private readonly string $uri,
        private readonly string $path,
        private readonly Palettes $palettes,
    ) {

        parent::__construct();
    }

    public function initialize(): void
    {
        $this->name = 'color_palette';
        $this->label = \__('Color Palette', 'acf-color-palette');
        $this->category = 'advanced';
        $this->description = \__('Pick a color from the theme.json color palettes, like the block editor color picker.', 'acf-color-palette');
        $this->doc_url = 'https://github.com/nlemoine/acf-color-palette#readme';
        $this->defaults = [
            'palettes' => [],
            'include_colors' => [],
            'exclude_colors' => [],
            'return_format' => ReturnFormat::Slug->value,
            'allow_null' => 0,
            'default_value' => '',
        ];

        \add_filter('acf/get_field_label', [$this, 'addLabelIndicator'], 10, 3);
    }

    /**
     * Add the selected color indicator next to the field label.
     *
     * The script paints it: ACF passes labels through wp_kses(), which drops rgb(), hsl() and other color functions from style attributes.
     *
     * @param mixed $label
     * @param mixed $field
     * @param mixed $context
     */
    public function addLabelIndicator($label, $field, $context): mixed
    {
        if (
            $context === 'admin'
            || !\is_string($label)
            || !\is_array($field)
            || ($field['type'] ?? null) !== $this->name
        ) {
            return $label;
        }

        return $label . '<span class="component-color-indicator"></span>';
    }

    /**
     * @param Field $field
     */
    public function render_field($field): void
    {
        $value = \is_string($field['value'] ?? null) ? $field['value'] : '';
        $groups = $this->swatchGroups($field, $value);
        if ($groups === []) {
            return;
        }

        $checked = $this->checkedGroup($groups, $value);
        $fieldId = \is_string($field['id'] ?? null) ? $field['id'] : '';
        $name = \is_string($field['name'] ?? null) ? $field['name'] : '';
        $label = \is_string($field['label'] ?? null) ? \wp_strip_all_tags($field['label']) : '';
        ?>
        <div role="group" aria-label="<?= \esc_attr($label) ?>">
            <?php // Submitted when no color is checked, so that clearing the field saves an empty value. ?>
            <input type="hidden" name="<?= \esc_attr($name) ?>" value="">
            <?php foreach ($groups as $key => $group) : ?>
                <div class="acf-color-palette-item">
                    <?php if (\count($groups) > 1) : ?>
                        <div class="components-truncate components-text components-heading">
                            <?= \esc_html($group['label']) ?>
                        </div>
                    <?php endif ?>
                    <div class="components-circular-option-picker">
                        <div class="components-circular-option-picker__swatches" role="radiogroup" aria-label="<?= \esc_attr($group['label']) ?>">
                            <?php foreach ($group['colors'] as $index => $color) :
                                // Slugs can hold any character: the position makes a valid, unique id.
                                $id = "{$fieldId}-{$key}-{$index}";
                                /* translators: %s: color name */
                                $ariaLabel = \sprintf(\__('Color: %s', 'acf-color-palette'), $color['name']);
                                ?>
                                <div class="components-circular-option-picker__option-wrapper" data-color="<?= \esc_attr($color['color']) ?>">
                                    <input
                                        id="<?= \esc_attr($id) ?>"
                                        type="radio"
                                        name="<?= \esc_attr($name) ?>"
                                        value="<?= \esc_attr($color['slug']) ?>"
                                        aria-label="<?= \esc_attr($ariaLabel) ?>"
                                        <?php \checked($key === $checked && $color['slug'] === $value) ?>
                                    />
                                    <?php // The radio, visually hidden, takes the focus: the label is the swatch. ?>
                                    <label
                                        for="<?= \esc_attr($id) ?>"
                                        title="<?= \esc_attr($color['name']) ?>"
                                        class="components-button components-circular-option-picker__option acf-js-tooltip"
                                        style="background-color: <?= \esc_attr($color['color']) ?>; color: <?= \esc_attr($color['color']) ?>;"
                                    ></label>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#000" role="img" aria-hidden="true" focusable="false">
                                        <path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z" />
                                    </svg>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <?php if (!empty($field['allow_null'])) : ?>
            <div class="components-circular-option-picker__custom-clear-wrapper">
                <button type="button" class="components-button components-circular-option-picker__clear is-secondary is-small">
                    <?= \esc_html__('Clear', 'acf') ?>
                </button>
            </div>
        <?php endif;
    }

    /**
     * @param Field $field
     */
    public function render_field_settings($field): void
    {
        $palettes = [];
        foreach (Palette::cases() as $palette) {
            $palettes[$palette->value] = $palette->label();
        }

        \acf_render_field_setting($field, [
            'label' => \__('Color palettes', 'acf-color-palette'),
            'instructions' => \__('The palettes to show. Leave empty to show the palettes of the block editor color picker.', 'acf-color-palette'),
            'name' => 'palettes',
            'type' => 'checkbox',
            'layout' => 'horizontal',
            'choices' => $palettes,
        ]);

        $colors = $this->colorChoices();

        \acf_render_field_setting($field, [
            'label' => \__('Include colors', 'acf-color-palette'),
            'instructions' => \__('Only show these colors. Leave empty to show all of them.', 'acf-color-palette'),
            'name' => 'include_colors',
            'type' => 'select',
            'ui' => 1,
            'multiple' => 1,
            'allow_null' => 1,
            'placeholder' => \__('Select colors (optional)', 'acf-color-palette'),
            'choices' => $colors,
        ]);

        \acf_render_field_setting($field, [
            'label' => \__('Exclude colors', 'acf-color-palette'),
            'instructions' => \__('Hide these colors.', 'acf-color-palette'),
            'name' => 'exclude_colors',
            'type' => 'select',
            'ui' => 1,
            'multiple' => 1,
            'allow_null' => 1,
            'placeholder' => \__('Select colors (optional)', 'acf-color-palette'),
            'choices' => $colors,
        ]);

        \acf_render_field_setting($field, [
            'label' => \__('Default Value', 'acf'),
            'instructions' => \__('Appears when creating a new post', 'acf'),
            'name' => 'default_value',
            'type' => 'select',
            'ui' => 1,
            'allow_null' => 1,
            'choices' => $colors,
        ]);

        $formats = [];
        foreach (ReturnFormat::cases() as $format) {
            $formats[$format->value] = $format->label();
        }

        \acf_render_field_setting($field, [
            'label' => \__('Return Format', 'acf'),
            'instructions' => \__('Specify the returned value on front end', 'acf'),
            'name' => 'return_format',
            'type' => 'radio',
            'layout' => 'horizontal',
            'choices' => $formats,
        ]);
    }

    /**
     * @param Field $field
     */
    public function render_field_validation_settings($field): void
    {
        \acf_render_field_setting($field, [
            'label' => \__('Allow Null', 'acf'),
            'name' => 'allow_null',
            'type' => 'true_false',
            'ui' => 1,
        ]);
    }

    /**
     * @param mixed      $value
     * @param int|string $post_id
     * @param Field      $field
     */
    public function format_value($value, $post_id, $field): mixed
    {
        if (!\is_string($value) || $value === '') {
            return null;
        }

        // Every palette, not only the field ones: a value stays readable when the field settings change.
        $color = $this->palettes->find($value);

        return $color === null ? null : ReturnFormat::fromField($field)->format($color);
    }

    /**
     * Any color of the palettes is valid: the field shows the stored color even when its settings no longer include it.
     *
     * @param bool|string $valid
     * @param mixed       $value
     * @param Field       $field
     * @param string      $input
     */
    public function validate_value($valid, $value, $field, $input): bool|string
    {
        // ACF checks required fields before.
        if ($value === '' || $value === null) {
            return $valid;
        }

        if (\is_string($value) && $this->palettes->find($value) !== null) {
            return $valid;
        }

        return \sprintf(
            /* translators: %s: the submitted color slug */
            \__('%s is not a valid color', 'acf-color-palette'),
            \esc_html(\is_string($value) ? $value : \gettype($value))
        );
    }

    /**
     * REST clients pick among the colors the field shows, or null to empty a field that is not required.
     *
     * @param Field $field
     *
     * @return array<string, mixed>
     */
    public function get_rest_schema(array $field): array
    {
        $schema = parent::get_rest_schema($field);
        $schema['enum'] = $this->fieldSlugs($field);

        if (empty($field['required'])) {
            $schema['enum'][] = null;
        }

        if (isset($schema['default']) && !\in_array($schema['default'], $schema['enum'], true)) {
            unset($schema['default']);
        }

        return $schema;
    }

    /**
     * Checks the value against the schema of this field: the REST schema is built once per request, for every field.
     *
     * @param bool|\WP_Error $valid
     * @param mixed          $value
     * @param Field          $field
     */
    public function validate_rest_value($valid, $value, $field): bool|\WP_Error
    {
        if ($valid !== true) {
            return $valid;
        }

        $schema = $this->get_rest_schema($field);
        $name = \is_string($field['name'] ?? null) ? $field['name'] : 'acf';

        // rest_validate_value_from_schema() skips an empty enum: a field without colors accepts no color.
        if (!\in_array($value, $schema['enum'], true)) {
            return new \WP_Error(
                'rest_not_in_enum',
                /* translators: %s: the field name */
                \sprintf(\__('%s is not one of the colors of the field.', 'acf-color-palette'), $name),
                ['param' => $name]
            );
        }

        return \rest_validate_value_from_schema($value, $schema, $name);
    }

    public function input_admin_enqueue_scripts(): void
    {
        // The swatches use the block editor component styles.
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- the file name is hashed by the build.
        \wp_enqueue_style(self::ASSET_HANDLE, $this->assetUrl('field.css'), ['wp-components'], null);
        // The build writes field-<hash>-rtl.css next to field-<hash>.css: WordPress loads it on RTL sites.
        \wp_style_add_data(self::ASSET_HANDLE, 'rtl', 'replace');
        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- the file name is hashed by the build.
        \wp_enqueue_script(self::ASSET_HANDLE, $this->assetUrl('field.js'), ['jquery'], null, true);
    }

    public function field_group_admin_enqueue_scripts(): void
    {
        // The field type icon, and the field preview.
        $this->input_admin_enqueue_scripts();
    }

    /**
     * Color names by slug, for the field settings.
     *
     * @return array<string, string>
     */
    private function colorChoices(): array
    {
        return \array_map(static fn (array $color): string => $color['name'], $this->palettes->colors());
    }

    /**
     * The swatches a field shows, by group: its palettes, and the stored color when the field settings no longer include it.
     *
     * @param Field $field
     *
     * @return array<string, array{label: string, colors: non-empty-list<Color>}>
     */
    private function swatchGroups(array $field, string $value): array
    {
        $groups = [];
        foreach ($this->palettes->forField($field) as $origin => $colors) {
            $groups[$origin] = ['label' => Palette::from($origin)->label(), 'colors' => $colors];
        }

        // Shown on its own, so that saving the post keeps it.
        $stored = $value === '' || \in_array($value, $this->fieldSlugs($field), true) ? null : $this->palettes->find($value);
        if ($stored !== null) {
            $groups[self::CURRENT_GROUP] = ['label' => \__('Current color', 'acf-color-palette'), 'colors' => [$stored]];
        }

        return $groups;
    }

    /**
     * The group of the checked swatch: a slug in several palettes resolves like Palettes::colors().
     *
     * @param array<string, array{label: string, colors: non-empty-list<Color>}> $groups
     */
    private function checkedGroup(array $groups, string $value): ?string
    {
        foreach ([self::CURRENT_GROUP, Palette::Custom->value, Palette::Theme->value, Palette::Default->value] as $key) {
            if (\in_array($value, \array_column($groups[$key]['colors'] ?? [], 'slug'), true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The slugs of the colors a field shows.
     *
     * @param Field $field
     *
     * @return list<string>
     */
    private function fieldSlugs(array $field): array
    {
        $slugs = [];
        foreach ($this->palettes->forField($field) as $colors) {
            foreach ($colors as $color) {
                $slugs[] = $color['slug'];
            }
        }

        return \array_values(\array_unique($slugs));
    }

    private function assetUrl(string $asset): string
    {
        $manifest = $this->manifest();

        return $this->uri . '/assets/dist/' . (\is_string($manifest[$asset] ?? null) ? $manifest[$asset] : $asset);
    }

    /**
     * The asset manifest (source file name to hashed file name), read once, empty when missing or invalid.
     *
     * @return array<mixed>
     */
    private function manifest(): array
    {
        if ($this->manifest === null) {
            $manifestPath = $this->path . '/assets/dist/manifest.json';
            $json = \is_readable($manifestPath) ? \file_get_contents($manifestPath) : false;
            $decoded = $json === false ? null : \json_decode($json, true);
            $this->manifest = \is_array($decoded) ? $decoded : [];
        }

        return $this->manifest;
    }
}
