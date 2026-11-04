<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette;

/**
 * The origins of the color palettes in the global settings (theme.json), in display order.
 */
enum Palette: string
{
    case Theme = 'theme';
    case Default = 'default';
    case Custom = 'custom';

    /**
     * The palettes selected in a field, in display order: none when the field leaves the choice to the block editor.
     *
     * @param array<string, mixed> $field
     *
     * @return list<self>
     */
    public static function fromField(array $field): array
    {
        $selected = [];
        foreach ((array) ($field['palettes'] ?? []) as $value) {
            $palette = \is_string($value) ? self::tryFrom($value) : null;
            if ($palette !== null) {
                $selected[] = $palette;
            }
        }

        // Keep the display order, whatever the order of the field setting.
        return \array_values(\array_filter(
            self::cases(),
            static fn (self $palette): bool => \in_array($palette, $selected, true)
        ));
    }

    public function label(): string
    {
        return match ($this) {
            self::Theme => \__('Theme', 'acf-color-palette'),
            self::Default => \__('Default', 'acf-color-palette'),
            self::Custom => \__('Custom', 'acf-color-palette'),
        };
    }
}
