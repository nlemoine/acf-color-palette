<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette;

/**
 * The color palettes of the global settings: theme.json, or editor-color-palette theme support on classic themes.
 *
 * @phpstan-type Color array{name: string, slug: string, color: string}
 */
final class Palettes
{
    /**
     * Palettes with at least one color, keyed by origin, in display order.
     *
     * @return array<value-of<Palette>, non-empty-list<Color>>
     */
    public function all(): array
    {
        $settings = \wp_get_global_settings(['color', 'palette']);
        $palettes = [];
        foreach (Palette::cases() as $palette) {
            $colors = $this->colorsFrom(\is_array($settings) ? ($settings[$palette->value] ?? null) : null);
            if ($colors !== []) {
                $palettes[$palette->value] = $colors;
            }
        }

        return $palettes;
    }

    /**
     * The palettes a field shows: its selected palettes, with its included and excluded colors.
     *
     * @param array<string, mixed> $field
     *
     * @return array<value-of<Palette>, non-empty-list<Color>>
     */
    public function forField(array $field): array
    {
        $include = $this->slugs($field['include_colors'] ?? null);
        $exclude = $this->slugs($field['exclude_colors'] ?? null);
        $all = $this->all();

        $palettes = [];
        foreach (Palette::fromField($field) ?: $this->editorPalettes() as $palette) {
            $colors = \array_values(\array_filter(
                $all[$palette->value] ?? [],
                static fn (array $color): bool => ($include === [] || \in_array($color['slug'], $include, true))
                    && !\in_array($color['slug'], $exclude, true)
            ));
            if ($colors !== []) {
                $palettes[$palette->value] = $colors;
            }
        }

        return $palettes;
    }

    /**
     * The palettes the block editor shows: all of them, but the default one when the theme hides it.
     *
     * @return list<Palette>
     */
    public function editorPalettes(): array
    {
        $showDefault = \wp_get_global_settings(['color', 'defaultPalette']) !== false;

        return \array_values(\array_filter(
            Palette::cases(),
            static fn (Palette $palette): bool => $showDefault || $palette !== Palette::Default
        ));
    }

    /**
     * Colors of all palettes, by slug.
     *
     * A slug in several palettes resolves like the --wp--preset--color--<slug> CSS variable: custom, then theme, then default.
     *
     * @return array<string, Color>
     */
    public function colors(): array
    {
        $all = $this->all();
        $colors = [];
        foreach ([Palette::Default, Palette::Theme, Palette::Custom] as $palette) {
            foreach ($all[$palette->value] ?? [] as $color) {
                $colors[$color['slug']] = $color;
            }
        }

        return $colors;
    }

    /**
     * @return Color|null
     */
    public function find(string $slug): ?array
    {
        return $this->colors()[$slug] ?? null;
    }

    /**
     * Valid colors of a palette from the global settings.
     *
     * @return list<Color>
     */
    private function colorsFrom(mixed $palette): array
    {
        $colors = [];
        foreach (\is_array($palette) ? $palette : [] as $color) {
            if (!\is_array($color)) {
                continue;
            }

            $slug = $color['slug'] ?? null;
            $value = $color['color'] ?? null;
            if (!\is_string($slug) || $slug === '' || !\is_string($value) || $value === '') {
                continue;
            }

            $name = $color['name'] ?? null;
            $colors[] = ['name' => \is_string($name) && $name !== '' ? $name : $slug, 'slug' => $slug, 'color' => $value];
        }

        return $colors;
    }

    /**
     * @return list<string>
     */
    private function slugs(mixed $value): array
    {
        return \array_values(\array_filter((array) $value, static fn (mixed $slug): bool => \is_string($slug) && $slug !== ''));
    }
}
