<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette;

/**
 * What get_field() returns for a color palette field.
 *
 * @phpstan-import-type Color from Palettes
 */
enum ReturnFormat: string
{
    case Slug = 'slug';
    case Array = 'array';
    case Name = 'name';
    case Color = 'color';

    /**
     * @param array<string, mixed> $field
     */
    public static function fromField(array $field): self
    {
        $format = $field['return_format'] ?? null;

        return \is_string($format) ? (self::tryFrom($format) ?? self::Slug) : self::Slug;
    }

    /**
     * @param Color $color
     *
     * @return string|Color
     */
    public function format(array $color): string|array
    {
        return match ($this) {
            self::Slug => $color['slug'],
            self::Array => $color,
            self::Name => $color['name'],
            self::Color => $color['color'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Slug => \__('Slug', 'acf-color-palette'),
            self::Array => \__('Slug, name and color', 'acf-color-palette'),
            self::Name => \__('Name', 'acf-color-palette'),
            self::Color => \__('Color value', 'acf-color-palette'),
        };
    }
}
