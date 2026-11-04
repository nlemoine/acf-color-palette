<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Unit;

use n5s\AcfColorPalette\ReturnFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReturnFormatTest extends TestCase
{
    private const array COLOR = ['name' => 'Primary', 'slug' => 'primary', 'color' => '#0d6efd'];

    /**
     * @return iterable<string, array{mixed, ReturnFormat}>
     */
    public static function fields(): iterable
    {
        yield 'slug' => ['slug', ReturnFormat::Slug];
        yield 'array' => ['array', ReturnFormat::Array];
        yield 'name' => ['name', ReturnFormat::Name];
        yield 'color' => ['color', ReturnFormat::Color];
        yield 'unknown' => ['other', ReturnFormat::Slug];
        yield 'not a string' => [1, ReturnFormat::Slug];
    }

    #[DataProvider('fields')]
    public function testFromField(mixed $format, ReturnFormat $expected): void
    {
        $this->assertSame($expected, ReturnFormat::fromField(['return_format' => $format]));
    }

    public function testFromFieldWithoutFormat(): void
    {
        $this->assertSame(ReturnFormat::Slug, ReturnFormat::fromField([]));
    }

    /**
     * @return iterable<string, array{ReturnFormat, mixed}>
     */
    public static function formats(): iterable
    {
        yield 'slug' => [ReturnFormat::Slug, 'primary'];
        yield 'array' => [ReturnFormat::Array, self::COLOR];
        yield 'name' => [ReturnFormat::Name, 'Primary'];
        yield 'color' => [ReturnFormat::Color, '#0d6efd'];
    }

    #[DataProvider('formats')]
    public function testFormat(ReturnFormat $format, mixed $expected): void
    {
        $this->assertSame($expected, $format->format(self::COLOR));
    }

    public function testEveryFormatHasALabel(): void
    {
        foreach (ReturnFormat::cases() as $format) {
            $this->assertNotSame('', $format->label());
        }
    }
}
