<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Integration;

use n5s\AcfColorPalette\Palettes;
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
            'resolve' => fn (mixed $root, mixed $args, mixed $context, mixed $info, mixed $fieldType, mixed $fieldConfig): ?array => $this->resolve(
                $fieldConfig instanceof FieldConfig
                    ? $fieldConfig->resolve_field($root, (array) $args, $context, $info)
                    : null
            ),
        ]);
    }

    /**
     * @return array{slug: string, name: ?string, color: ?string}|null
     */
    public function resolve(mixed $value): ?array
    {
        // A slug, or a color array in rows formatted by ACF.
        $slug = \is_array($value) ? ($value['slug'] ?? null) : $value;
        if (!\is_string($slug) || $slug === '') {
            return null;
        }

        $color = $this->palettes->find($slug);

        return ['slug' => $slug, 'name' => $color['name'] ?? null, 'color' => $color['color'] ?? null];
    }
}
