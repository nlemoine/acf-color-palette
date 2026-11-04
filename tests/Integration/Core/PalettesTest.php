<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\Core;

use n5s\AcfColorPalette\Palettes;
use n5s\AcfColorPalette\Plugin;
use n5s\AcfColorPalette\Tests\TestCase;

final class PalettesTest extends TestCase
{
    public function testReadsThePalettesOfTheGlobalSettings(): void
    {
        $palettes = $this->palettes()->all();

        $this->assertSame(['theme', 'default'], \array_keys($palettes));
        $this->assertSame(self::THEME_PALETTE, $palettes['theme']);
        // WordPress' own palette.
        $this->assertContains('vivid-red', \array_column($palettes['default'], 'slug'));
    }

    public function testReadsTheCustomPalette(): void
    {
        $this->useCustomPalette([['slug' => 'brand', 'name' => 'Brand', 'color' => '#ff0066']]);

        $palettes = $this->palettes()->all();

        $this->assertSame(['theme', 'default', 'custom'], \array_keys($palettes));
        $this->assertSame([['name' => 'Brand', 'slug' => 'brand', 'color' => '#ff0066']], $palettes['custom']);
    }

    public function testSkipsEmptyPalettes(): void
    {
        $this->useThemePalette([]);

        $this->assertSame(['default'], \array_keys($this->palettes()->all()));
    }

    public function testNamesAColorWithoutNameAfterItsSlug(): void
    {
        $this->useThemePalette([['slug' => 'accent', 'color' => '#123456']]);

        $this->assertSame([['name' => 'accent', 'slug' => 'accent', 'color' => '#123456']], $this->palettes()->all()['theme']);
    }

    public function testShowsTheSelectedPalettes(): void
    {
        $this->assertSame(['default'], \array_keys($this->palettes()->forField(['palettes' => ['default']])));
        $this->assertSame(['theme', 'default'], \array_keys($this->palettes()->forField(['palettes' => []])));
    }

    public function testShowsThePalettesOfTheBlockEditorByDefault(): void
    {
        $this->useThemePalette(self::THEME_PALETTE, defaultPalette: false);

        $this->assertSame(['theme'], \array_keys($this->palettes()->forField([])));
        // Selected explicitly, the default palette shows anyway.
        $this->assertSame(['default'], \array_keys($this->palettes()->forField(['palettes' => ['default']])));
    }

    public function testShowsOnlyIncludedColors(): void
    {
        $palettes = $this->palettes()->forField(['include_colors' => ['primary', 'light']]);

        $this->assertSame(['theme'], \array_keys($palettes));
        $this->assertSame(['primary', 'light'], \array_column($palettes['theme'], 'slug'));
    }

    public function testHidesExcludedColors(): void
    {
        $palettes = $this->palettes()->forField(['palettes' => ['theme'], 'exclude_colors' => ['secondary']]);

        $this->assertSame(['primary', 'light'], \array_column($palettes['theme'], 'slug'));
    }

    public function testResolvesASlugInSeveralPalettesLikeTheCssVariables(): void
    {
        $this->useThemePalette([['slug' => 'brand', 'name' => 'Theme brand', 'color' => '#000001']]);
        $this->assertSame('#000001', $this->palettes()->find('brand')['color'] ?? null);

        $this->useCustomPalette([['slug' => 'brand', 'name' => 'Custom brand', 'color' => '#000002']]);
        $this->assertSame('#000002', $this->palettes()->find('brand')['color'] ?? null);
    }

    public function testFindsNothingForAnUnknownSlug(): void
    {
        $this->assertNull($this->palettes()->find('unknown'));
    }

    private function palettes(): Palettes
    {
        return Plugin::getInstance()->palettes();
    }
}
