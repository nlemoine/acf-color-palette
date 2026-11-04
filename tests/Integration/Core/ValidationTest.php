<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\Core;

use n5s\AcfColorPalette\Tests\TestCase;

final class ValidationTest extends TestCase
{
    public function testAcceptsAColorOfTheField(): void
    {
        $field = $this->registerField();

        $this->assertTrue($this->fieldType()->validate_value(true, 'primary', $field, 'acf[field]'));
        $this->assertTrue($this->fieldType()->validate_value(true, 'vivid-red', $field, 'acf[field]'));
    }

    public function testAcceptsAnEmptyValue(): void
    {
        $field = $this->registerField();

        $this->assertTrue($this->fieldType()->validate_value(true, '', $field, 'acf[field]'));
        $this->assertTrue($this->fieldType()->validate_value(true, null, $field, 'acf[field]'));
    }

    public function testKeepsAnEarlierError(): void
    {
        $field = $this->registerField();

        $this->assertSame('Error', $this->fieldType()->validate_value('Error', 'primary', $field, 'acf[field]'));
    }

    public function testRejectsAnUnknownColor(): void
    {
        $field = $this->registerField();

        $this->assertSame('unknown is not a valid color', $this->fieldType()->validate_value(true, 'unknown', $field, 'acf[field]'));
    }

    public function testAcceptsAColorTheFieldNoLongerShows(): void
    {
        // The field shows the stored color in its own group: saving the post keeps it.
        $field = $this->registerField(['palettes' => ['theme'], 'exclude_colors' => ['light']]);

        $this->assertTrue($this->fieldType()->validate_value(true, 'vivid-red', $field, 'acf[field]'));
        $this->assertTrue($this->fieldType()->validate_value(true, 'light', $field, 'acf[field]'));
    }

    public function testRejectsANonStringValue(): void
    {
        $field = $this->registerField();

        $this->assertSame('array is not a valid color', $this->fieldType()->validate_value(true, ['primary'], $field, 'acf[field]'));
    }

    public function testEscapesTheValueInTheMessage(): void
    {
        $field = $this->registerField();

        $this->assertSame('&lt;b&gt; is not a valid color', $this->fieldType()->validate_value(true, '<b>', $field, 'acf[field]'));
    }
}
