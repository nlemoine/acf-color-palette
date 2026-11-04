<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Unit;

use n5s\AcfColorPalette\Palette;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PaletteTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, list<Palette>}>
     */
    public static function fields(): iterable
    {
        yield 'none selected' => [[], []];
        yield 'one' => [['default'], [Palette::Default]];
        yield 'display order' => [['custom', 'theme'], [Palette::Theme, Palette::Custom]];
        yield 'a single string' => ['theme', [Palette::Theme]];
        yield 'unknown values only' => [['other', 1], []];
        yield 'unknown values ignored' => [['other', 'theme'], [Palette::Theme]];
    }

    /**
     * @param list<Palette> $expected
     */
    #[DataProvider('fields')]
    public function testFromField(mixed $palettes, array $expected): void
    {
        $this->assertSame($expected, Palette::fromField(['palettes' => $palettes]));
    }

    public function testFromFieldWithoutSetting(): void
    {
        $this->assertSame([], Palette::fromField([]));
    }

    public function testEveryPaletteHasALabel(): void
    {
        foreach (Palette::cases() as $palette) {
            $this->assertNotSame('', $palette->label());
        }
    }
}
