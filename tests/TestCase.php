<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests;

use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testkit\TestCase as MantleTestCase;
use n5s\AcfColorPalette\Field\ColorPaletteField;
use WP_Theme_JSON_Data;

abstract class TestCase extends MantleTestCase
{
    use Refresh_Database;

    protected const FIELD_NAME = 'color';

    /**
     * The theme palette of the tests.
     */
    protected const THEME_PALETTE = [
        ['name' => 'Primary', 'slug' => 'primary', 'color' => '#0d6efd'],
        ['name' => 'Secondary', 'slug' => 'secondary', 'color' => '#6c757d'],
        ['name' => 'Light', 'slug' => 'light', 'color' => '#f8f9fa'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // ACF caches values per post ID, and rolled back post IDs are reused.
        \acf_get_store('values')->reset();

        // Whatever the active theme, the tests use THEME_PALETTE.
        $this->useThemePalette(self::THEME_PALETTE);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Hooks are restored after each test: drop the global settings built with them.
        \wp_clean_theme_json_cache();
    }

    protected function fieldType(): ColorPaletteField
    {
        $fieldType = \acf_get_field_type('color_palette');
        $this->assertInstanceOf(ColorPaletteField::class, $fieldType);

        return $fieldType;
    }

    /**
     * Register a color palette field on posts and return its settings.
     *
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $groupSettings
     * @return array<string, mixed>
     */
    protected function registerField(array $settings = [], array $groupSettings = []): array
    {
        // ACF caches local fields by key: use a fresh key for each registration.
        $suffix = \uniqid();
        $field = \array_merge(
            [
                'key' => 'field_acf_color_palette_' . $suffix,
                'label' => 'Color',
                'name' => self::FIELD_NAME,
                'type' => 'color_palette',
            ],
            $settings
        );

        \acf_add_local_field_group(\array_merge([
            'key' => 'group_acf_color_palette_' . $suffix,
            'title' => 'Color palette test',
            'fields' => [$field],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
        ], $groupSettings));

        return \acf_get_field($field['key']);
    }

    /**
     * @param list<array<string, mixed>> $colors
     */
    protected function useThemePalette(array $colors, bool $defaultPalette = true): void
    {
        \add_filter(
            'wp_theme_json_data_theme',
            static fn (WP_Theme_JSON_Data $data): WP_Theme_JSON_Data => $data->update_with([
                'version' => 2,
                'settings' => ['color' => ['palette' => $colors, 'defaultPalette' => $defaultPalette]],
            ])
        );
        \wp_clean_theme_json_cache();
    }

    /**
     * The palette users create in the Site Editor global styles.
     *
     * @param list<array<string, mixed>> $colors
     */
    protected function useCustomPalette(array $colors): void
    {
        \add_filter(
            'wp_theme_json_data_user',
            static fn (WP_Theme_JSON_Data $data): WP_Theme_JSON_Data => $data->update_with([
                'version' => 2,
                'settings' => ['color' => ['palette' => $colors]],
            ])
        );
        \wp_clean_theme_json_cache();
    }
}
