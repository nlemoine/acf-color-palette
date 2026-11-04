<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\AdminColumns;

use AC\Column\Context;
use AC\Column\CustomFieldContext;
use AC\MetaType;
use AC\Setting\Config;
use AC\Type\TableScreenContext;
use n5s\AcfColorPalette\ReturnFormat;
use n5s\AcfColorPalette\Tests\TestCase;

use function Mantle\Testing\html_string;

final class AdminColumnsTest extends TestCase
{
    public function testShowsASwatchAndTheColorName(): void
    {
        $field = $this->registerField();
        $postId = self::factory()->post->create();
        \update_field($field['key'], 'primary', $postId);

        $html = $this->render('primary', $this->customFieldContext(MetaType::POST), $postId);

        $this->assertStringEndsWith('</span>Primary', $html);
        $this->assertStringContainsString('background-color: #0d6efd', (string) html_string($html)->first_by_selector('span')->get_attribute('style'));
    }

    public function testShowsTheNameWhateverTheReturnFormat(): void
    {
        $field = $this->registerField(['return_format' => ReturnFormat::Color->value]);
        $postId = self::factory()->post->create();
        \update_field($field['key'], 'primary', $postId);

        $this->assertStringEndsWith('</span>Primary', $this->render('#0d6efd', $this->customFieldContext(MetaType::POST), $postId));
    }

    public function testSupportsUserColumns(): void
    {
        $field = $this->registerField();
        $userId = self::factory()->user->create();
        \update_field($field['key'], 'light', 'user_' . $userId);

        $this->assertStringEndsWith('</span>Light', $this->render('light', $this->customFieldContext(MetaType::USER), $userId));
    }

    public function testLeavesUnknownColorsUntouched(): void
    {
        $field = $this->registerField();
        $postId = self::factory()->post->create();
        \update_field($field['key'], 'removed', $postId);

        $this->assertSame('removed', $this->render('removed', $this->customFieldContext(MetaType::POST), $postId));
    }

    public function testLeavesEmptyValuesUntouched(): void
    {
        $this->registerField();
        $postId = self::factory()->post->create();

        $this->assertSame('&ndash;', $this->render('&ndash;', $this->customFieldContext(MetaType::POST), $postId));
    }

    public function testLeavesOtherCustomFieldsUntouched(): void
    {
        $postId = self::factory()->post->create();
        \update_post_meta($postId, 'subtitle', 'primary');

        $this->assertSame('primary', $this->render('primary', $this->customFieldContext(MetaType::POST, 'subtitle'), $postId));
    }

    public function testLeavesUnsupportedMetaTypesUntouched(): void
    {
        $this->assertSame('primary', $this->render('primary', $this->customFieldContext(MetaType::SITE), 1));
    }

    public function testLeavesOtherColumnsUntouched(): void
    {
        $postId = self::factory()->post->create();

        $this->assertSame('Title', $this->render('Title', new Context(new Config(['type' => 'title']), 'Title'), $postId));
    }

    private function customFieldContext(string $metaType, string $metaKey = self::FIELD_NAME): CustomFieldContext
    {
        return new CustomFieldContext(
            new Config(['type' => 'column-meta', 'field' => $metaKey]),
            'Custom Field',
            '',
            $metaKey,
            new TableScreenContext(new MetaType($metaType))
        );
    }

    private function render(string $value, Context $context, int $id): string
    {
        return \apply_filters('ac/column/render', $value, $context, $id);
    }
}
