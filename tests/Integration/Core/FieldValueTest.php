<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\Core;

use n5s\AcfColorPalette\ReturnFormat;
use n5s\AcfColorPalette\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FieldValueTest extends TestCase
{
    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function returnFormats(): iterable
    {
        yield 'slug' => [ReturnFormat::Slug->value, 'primary'];
        yield 'array' => [ReturnFormat::Array->value, ['name' => 'Primary', 'slug' => 'primary', 'color' => '#0d6efd']];
        yield 'name' => [ReturnFormat::Name->value, 'Primary'];
        yield 'color' => [ReturnFormat::Color->value, '#0d6efd'];
    }

    #[DataProvider('returnFormats')]
    public function testFormatsTheValue(string $format, mixed $expected): void
    {
        $field = $this->registerField(['return_format' => $format]);
        $postId = self::factory()->post->create();

        \update_field($field['key'], 'primary', $postId);

        $this->assertSame($expected, \get_field(self::FIELD_NAME, $postId));
        $this->assertSame('primary', \get_field(self::FIELD_NAME, $postId, false));
    }

    public function testReturnsTheSlugByDefault(): void
    {
        $field = $this->registerField();
        $postId = self::factory()->post->create();

        \update_field($field['key'], 'primary', $postId);

        $this->assertSame('primary', \get_field(self::FIELD_NAME, $postId));
    }

    public function testFormatsAColorTheFieldNoLongerShows(): void
    {
        $field = $this->registerField(['return_format' => ReturnFormat::Color->value]);
        $postId = self::factory()->post->create();
        \update_field($field['key'], 'vivid-red', $postId);

        $this->registerField(['return_format' => ReturnFormat::Color->value, 'palettes' => ['theme']]);

        $this->assertSame('#cf2e2e', \get_field(self::FIELD_NAME, $postId));
    }

    public function testReturnsNullForAColorRemovedFromThePalettes(): void
    {
        $field = $this->registerField(['return_format' => ReturnFormat::Name->value]);
        $postId = self::factory()->post->create();

        \update_field($field['key'], 'removed', $postId);

        $this->assertNull(\get_field(self::FIELD_NAME, $postId));
    }

    public function testReturnsNullForAnEmptyValue(): void
    {
        $field = $this->registerField();
        $postId = self::factory()->post->create();

        \update_field($field['key'], '', $postId);

        $this->assertNull(\get_field(self::FIELD_NAME, $postId));
    }

    public function testReturnsNullForANonStringValue(): void
    {
        $field = $this->registerField();

        $this->assertNull($this->fieldType()->format_value(['primary'], 1, $field));
    }
}
