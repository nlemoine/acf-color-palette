<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\Core;

use n5s\AcfColorPalette\Field\ColorPaletteField;
use n5s\AcfColorPalette\Integration\AdminColumns;
use n5s\AcfColorPalette\Integration\WpGraphQl;
use n5s\AcfColorPalette\Plugin;
use n5s\AcfColorPalette\Tests\TestCase;

use function Mantle\Support\Helpers\capture;

final class PluginTest extends TestCase
{
    public function testRegistersTheColorPaletteFieldType(): void
    {
        $fieldType = $this->fieldType();

        $this->assertSame('color_palette', $fieldType->name);
        $this->assertSame('advanced', $fieldType->category);
        $this->assertArrayHasKey($fieldType->category, \acf_get_field_categories_i18n());
        $this->assertTrue($fieldType->show_in_rest);
    }

    public function testInitRegistersTheFieldAndTheIntegrations(): void
    {
        // The test bootstrap already booted the plugin: reset the singleton to boot a fresh instance.
        $instance = new \ReflectionProperty(Plugin::class, 'instance');
        $saved = $instance->getValue();
        $instance->setValue(null, null);
        $fieldType = $this->fieldType();

        try {
            $fieldTypeHooks = \count($GLOBALS['wp_filter']['acf/include_field_types']->callbacks[10] ?? []);
            $plugin = Plugin::getInstance()->init();

            // The field registers on acf/include_field_types; integrations hook into their plugin's hooks.
            $this->assertCount($fieldTypeHooks + 1, $GLOBALS['wp_filter']['acf/include_field_types']->callbacks[10]);
            $this->assertNotFalse(\has_action('wpgraphql/acf/registry_init', [$plugin->getContainer()->get(WpGraphQl::class), 'registerFieldType']));
            $this->assertNotFalse(\has_filter('ac/column/render', [$plugin->getContainer()->get(AdminColumns::class), 'render']));
            $this->assertSame($plugin, $plugin->init());

            \do_action('acf/include_field_types');
            $registered = \acf_get_field_type('color_palette');
            $this->assertInstanceOf(ColorPaletteField::class, $registered);
            $this->assertNotSame($fieldType, $registered);
        } finally {
            $instance->setValue(null, $saved);
            \acf_register_field_type($fieldType);
        }
    }

    public function testLoadsTranslationsBeforeAcfBuildsTheFieldLabel(): void
    {
        // ACF fires acf/include_field_types from ACF::init(); the field label is translated there.
        $this->assertSame(4, \has_action('init', 'n5s\\AcfColorPalette\\load_textdomain'));
        $this->assertSame(5, \has_action('init', [\acf(), 'init']));
    }

    public function testHooksCanBeRemoved(): void
    {
        $this->assertSame(10, \has_action('plugins_loaded', 'n5s\\AcfColorPalette\\boot'));
        $this->assertSame(10, \has_action('acf/include_field_types', [Plugin::getInstance(), 'registerFieldType']));
        $this->assertSame(10, \has_filter('acf/get_field_label', [$this->fieldType(), 'addLabelIndicator']));
    }

    public function testEnqueuesTheFieldAssetsFromTheManifest(): void
    {
        $this->fieldType()->input_admin_enqueue_scripts();

        $this->assertScriptEnqueued('acf-color-palette-field');
        $this->assertStyleEnqueued('acf-color-palette-field');
        $this->assertBuiltAsset('field.js', \wp_scripts()->registered['acf-color-palette-field']->src);
        $this->assertBuiltAsset('field.css', \wp_styles()->registered['acf-color-palette-field']->src);
        // The swatches use the block editor component styles.
        $this->assertContains('wp-components', \wp_styles()->registered['acf-color-palette-field']->deps);
    }

    public function testEnqueuesTheFieldAssetsOnFieldGroupScreens(): void
    {
        $this->fieldType()->field_group_admin_enqueue_scripts();

        $this->assertScriptEnqueued('acf-color-palette-field');
        $this->assertStyleEnqueued('acf-color-palette-field');
    }

    public function testLoadsTheRtlStyleOnRtlSites(): void
    {
        // WP_Styles reads the text direction once, when it is created.
        $styles = \wp_styles();
        $direction = $styles->text_direction;
        $styles->text_direction = 'rtl';

        try {
            $this->fieldType()->input_admin_enqueue_scripts();
            $html = capture(static fn () => $styles->do_item('acf-color-palette-field'));
        } finally {
            $styles->text_direction = $direction;
        }

        $this->assertMatchesRegularExpression('#/assets/dist/(field-[0-9a-f]{20}-rtl\.css)#', $html);
        \preg_match('#/assets/dist/(field-[0-9a-f]{20}-rtl\.css)#', $html, $matches);
        $this->assertFileExists(\dirname(__DIR__, 3) . '/assets/dist/' . $matches[1]);
    }

    /**
     * The asset URL points to the hashed file listed in the manifest, and the file exists.
     */
    private function assertBuiltAsset(string $asset, mixed $src): void
    {
        $dist = \dirname(__DIR__, 3) . '/assets/dist';
        $manifest = \json_decode((string) \file_get_contents("{$dist}/manifest.json"), true);
        $this->assertIsArray($manifest);
        $this->assertIsString($manifest[$asset] ?? null);
        $this->assertMatchesRegularExpression('/-[0-9a-f]{20}\.(js|css)\z/', $manifest[$asset]);
        $this->assertFileExists("{$dist}/{$manifest[$asset]}");
        $this->assertIsString($src);
        $this->assertStringEndsWith("/assets/dist/{$manifest[$asset]}", $src);
    }
}
