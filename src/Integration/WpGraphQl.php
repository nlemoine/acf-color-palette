<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Integration;

use n5s\AcfColorPalette\Palette;
use n5s\AcfColorPalette\Palettes;
use n5s\AcfColorPalette\ReturnFormat;
use WPGraphQL\Acf\FieldConfig;

/**
 * WPGraphQL for ACF 2+: color palette fields resolve to AcfColorPaletteColor objects.
 *
 * The hook callback methods stay public so they can be removed with remove_action().
 *
 * @internal Not part of the public API; may change in minor versions.
 */
final readonly class WpGraphQl implements IntegrationInterface
{
    public const string TYPE = 'AcfColorPaletteColor';

    public function __construct(private Palettes $palettes)
    {
    }

    public function registerHooks(): void
    {
        \add_action('graphql_register_types', [$this, 'registerObjectType']);
        \add_action('wpgraphql/acf/registry_init', [$this, 'registerFieldType']);
    }

    public function registerObjectType(): void
    {
        // graphql_register_types is a WPGraphQL hook: no AcfColorPaletteColor type without WPGraphQL for ACF.
        if (!\function_exists('register_graphql_acf_field_type')) {
            return;
        }

        \register_graphql_object_type(self::TYPE, [
            'description' => \__('A color selected in an ACF Color Palette field.', 'acf-color-palette'),
            'fields' => [
                'slug' => [
                    'type' => ['non_null' => 'String'],
                    'description' => \__('Color slug, as in the --wp--preset--color--<slug> CSS variable.', 'acf-color-palette'),
                ],
                'name' => [
                    'type' => 'String',
                    'description' => \__('Color name, null for a color no longer in the palettes.', 'acf-color-palette'),
                ],
                'color' => [
                    'type' => 'String',
                    'description' => \__('Color value, null for a color no longer in the palettes.', 'acf-color-palette'),
                ],
            ],
        ]);
    }

    public function registerFieldType(): void
    {
        \register_graphql_acf_field_type('color_palette', [
            'graphql_type' => self::TYPE,
            'resolve' => fn (mixed $root, mixed $args, mixed $context, mixed $info, mixed $fieldType, mixed $fieldConfig): ?array => $fieldConfig instanceof FieldConfig
                ? $this->resolve(
                    $fieldConfig->resolve_field($root, (array) $args, $context, $info),
                    $fieldConfig->get_acf_field(),
                    $root
                )
                : null,
        ]);
    }

    /**
     * @param array<string, mixed> $field The ACF field.
     * @param mixed                $root  The object or row the field belongs to.
     *
     * @return array{slug: string, name: ?string, color: ?string}|null
     */
    public function resolve(mixed $value, array $field = [], mixed $root = null): ?array
    {
        $slug = $this->slug($value, $field, $root);
        if ($slug === null) {
            return null;
        }

        $color = $this->palettes->find($slug);

        return ['slug' => $slug, 'name' => $color['name'] ?? null, 'color' => $color['color'] ?? null];
    }

    /**
     * The stored slug: top-level fields resolve it, rows of repeater, flexible content and clone fields pass the formatted value.
     *
     * @param array<string, mixed> $field
     */
    private function slug(mixed $value, array $field, mixed $root): ?string
    {
        $raw = $this->rawRowValue($field, $root);
        if ($raw !== null) {
            return $raw;
        }

        if (\is_array($value)) {
            $value = $value['slug'] ?? null;
        }
        if (!\is_string($value) || $value === '') {
            return null;
        }

        return $this->palettes->find($value) === null ? $this->slugOf($value, ReturnFormat::fromField($field)) : $value;
    }

    /**
     * The stored value of the row, when WPGraphQL for ACF passes it down (an internal key in 2.x and 3.x).
     *
     * @param array<string, mixed> $field
     */
    private function rawRowValue(array $field, mixed $root): ?string
    {
        $key = $field['key'] ?? null;
        $row = \is_array($root) ? ($root['__wpgraphql_acf_raw_row'] ?? null) : null;
        $raw = \is_string($key) && \is_array($row) ? ($row[$key] ?? null) : null;

        return \is_string($raw) && $raw !== '' ? $raw : null;
    }

    /**
     * The slug of a formatted name or color value: the first matching color, in the order Palettes::colors() resolves slugs.
     */
    private function slugOf(string $value, ReturnFormat $format): string
    {
        $property = match ($format) {
            ReturnFormat::Name => 'name',
            ReturnFormat::Color => 'color',
            default => null,
        };
        if ($property === null) {
            return $value;
        }

        $palettes = $this->palettes->all();
        foreach ([Palette::Custom, Palette::Theme, Palette::Default] as $palette) {
            foreach ($palettes[$palette->value] ?? [] as $color) {
                if ($color[$property] === $value) {
                    return $color['slug'];
                }
            }
        }

        return $value;
    }
}
