<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\Core;

use n5s\AcfColorPalette\Tests\TestCase;

use function Mantle\Support\Helpers\capture;
use function Mantle\Testing\html_string;

final class RenderFieldTest extends TestCase
{
    public function testRendersASwatchPerColor(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);

        $html = html_string($this->renderField($field));

        $html->assertQuerySelectorCount('input[type="radio"]', 3);
        $wrapper = $html->first_by_selector('.components-circular-option-picker__option-wrapper');
        $this->assertSame('#0d6efd', $wrapper->get_attribute('data-color'));
        $this->assertSame('primary', $html->first_by_selector('input[type="radio"]')->get_attribute('value'));
        $this->assertSame('Theme', $html->first_by_selector('[role="radiogroup"]')->get_attribute('aria-label'));
    }

    public function testFocusesTheRadiosNotTheSwatches(): void
    {
        $html = html_string($this->renderField($this->registerField(['palettes' => ['theme']])));

        // Radios are named and reachable with the keyboard; swatches are not a second tab stop.
        $this->assertSame('Color: Primary', $html->first_by_selector('input[type="radio"]')->get_attribute('aria-label'));
        $html->assertQuerySelectorMissing('label[tabindex]');
    }

    public function testChecksTheValue(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);
        $field['value'] = 'secondary';

        $html = html_string($this->renderField($field));

        $html->assertQuerySelectorCount('input[type="radio"][checked]', 1);
        $this->assertSame('checked', $html->first_by_selector('input[value="secondary"]')->get_attribute('checked'));
    }

    public function testSubmitsAnEmptyValueBeforeTheColors(): void
    {
        $field = $this->registerField();

        $html = html_string($this->renderField($field));

        // A single hidden input, before every radio: a checked color in any palette is submitted.
        $html->assertQuerySelectorCount('input[type="hidden"][name="' . $field['name'] . '"]', 1);
        $inputs = $html->get_by_selector('input[name="' . $field['name'] . '"]');
        $this->assertSame('hidden', $inputs->first()->get_attribute('type'));
    }

    public function testShowsPaletteHeadingsWithSeveralPalettes(): void
    {
        $html = html_string($this->renderField($this->registerField()));

        $html->assertQuerySelectorCount('.acf-color-palette-item', 2);
        $html->assertQuerySelectorCount('.components-heading', 2);
        $this->assertSame('Theme', \trim($html->first_by_selector('.components-heading')->text()));
    }

    public function testHidesTheHeadingWithASinglePalette(): void
    {
        $html = html_string($this->renderField($this->registerField(['palettes' => ['default']])));

        $html->assertQuerySelectorCount('.acf-color-palette-item', 1);
        $html->assertQuerySelectorMissing('.components-heading');
    }

    public function testChecksASingleRadioForASlugInSeveralPalettes(): void
    {
        // WordPress drops theme colors reusing a default slug, unless the theme hides the default palette.
        $this->useThemePalette([['slug' => 'black', 'name' => 'Black', 'color' => '#111111']], defaultPalette: false);
        $field = $this->registerField(['palettes' => ['theme', 'default']]);
        $field['value'] = 'black';

        $html = html_string($this->renderField($field));

        $ids = [];
        foreach ($html->get_by_selector('input[type="radio"][value="black"]') as $input) {
            $ids[] = $input->getAttribute('id');
        }
        $this->assertCount(2, \array_unique($ids));
        // The theme one, like get_field() and the CSS variable.
        $html->assertQuerySelectorCount('input[type="radio"][checked]', 1);
        $this->assertStringContainsString('-theme-', (string) $html->first_by_selector('input[type="radio"][checked]')->get_attribute('id'));
    }

    public function testUsesValidIdsWhateverTheSlugs(): void
    {
        $this->useThemePalette([
            ['slug' => 'синий', 'name' => 'Blue', 'color' => '#00f'],
            ['slug' => 'красный', 'name' => 'Red', 'color' => '#f00'],
            ['slug' => 'brand.primary', 'name' => 'Brand', 'color' => '#123'],
            ['slug' => 'brandprimary', 'name' => 'Other brand', 'color' => '#456'],
        ]);

        $html = html_string($this->renderField($this->registerField(['palettes' => ['theme']])));

        $ids = [];
        foreach ($html->get_by_selector('input[type="radio"]') as $input) {
            $ids[] = $input->getAttribute('id');
            $this->assertMatchesRegularExpression('/\A[A-Za-z0-9_-]+\z/', $input->getAttribute('id'));
        }
        $this->assertCount(4, \array_unique($ids));
    }

    public function testKeepsAStoredColorTheFieldNoLongerShows(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);
        $field['value'] = 'vivid-red';

        $html = html_string($this->renderField($field));

        // Checked in its own group, so that saving the post keeps it.
        $this->assertSame('checked', $html->first_by_selector('input[value="vivid-red"]')->get_attribute('checked'));
        $html->assertQuerySelectorCount('.acf-color-palette-item', 2);
        $headings = [];
        foreach ($html->get_by_selector('.components-heading') as $heading) {
            $headings[] = \trim($heading->textContent);
        }
        $this->assertSame(['Theme', 'Current color'], $headings);
    }

    public function testDropsAStoredColorRemovedFromThePalettes(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);
        $field['value'] = 'removed';

        $html = html_string($this->renderField($field));

        $html->assertQuerySelectorCount('.acf-color-palette-item', 1);
        $html->assertQuerySelectorMissing('input[type="radio"][checked]');
    }

    public function testNamesTheGroupsAfterTheField(): void
    {
        $html = html_string($this->renderField($this->registerField(['label' => 'Accent <em>color</em>'])));

        $this->assertSame('Accent color', $html->first_by_selector('[role="group"]')->get_attribute('aria-label'));
    }

    public function testShowsTheClearButtonWhenNullIsAllowed(): void
    {
        $html = html_string($this->renderField($this->registerField(['allow_null' => 1])));

        $html->assertQuerySelectorExists('.components-circular-option-picker__clear');
    }

    public function testHidesTheClearButtonByDefault(): void
    {
        $html = html_string($this->renderField($this->registerField()));

        $html->assertQuerySelectorMissing('.components-circular-option-picker__clear');
    }

    public function testRendersNothingWithoutColors(): void
    {
        $field = $this->registerField(['include_colors' => ['unknown']]);

        $this->assertSame('', $this->renderField($field));
    }

    public function testAddsTheColorIndicatorToTheLabel(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);
        $field['value'] = 'primary';

        // Through acf_esc_html(), like ACF renders labels: the script paints the indicator.
        $html = html_string(capture(static fn () => \acf_render_field_label($field)));

        $html->assertQuerySelectorCount('.component-color-indicator', 1);
        $this->assertNull($html->first_by_selector('.component-color-indicator')->get_attribute('style'));
    }

    public function testLeavesOtherLabelsUntouched(): void
    {
        $field = $this->registerField();

        $this->assertSame('Color', \acf_get_field_label($field, 'admin'));
        $this->assertSame('Title', \acf_get_field_label(['label' => 'Title', 'type' => 'text', 'required' => 0]));
    }

    public function testRendersTheSettings(): void
    {
        $field = $this->registerField();

        $general = capture(fn () => $this->fieldType()->render_field_settings($field));
        foreach (['palettes', 'include_colors', 'exclude_colors', 'default_value', 'return_format'] as $setting) {
            $this->assertStringContainsString("[{$setting}]", $general);
        }

        // Color names, without markup: ACF escapes select options.
        $this->assertStringContainsString('>Primary</option>', $general);

        $this->assertStringContainsString('[allow_null]', capture(fn () => $this->fieldType()->render_field_validation_settings($field)));
    }

    /**
     * @param array<string, mixed> $field
     */
    private function renderField(array $field): string
    {
        return capture(fn () => $this->fieldType()->render_field($field + ['value' => null, 'id' => 'acf-field']));
    }
}
