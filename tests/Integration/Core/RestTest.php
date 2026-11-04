<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\Core;

use n5s\AcfColorPalette\Tests\TestCase;

final class RestTest extends TestCase
{
    public function testSchemaListsTheFieldColors(): void
    {
        $field = $this->registerField(['palettes' => ['theme'], 'required' => 1]);

        $schema = $this->fieldType()->get_rest_schema($field);

        $this->assertSame(['primary', 'secondary', 'light'], $schema['enum']);
    }

    public function testSchemaAllowsNullWhenTheFieldIsNotRequired(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);

        $this->assertSame(['primary', 'secondary', 'light', null], $this->fieldType()->get_rest_schema($field)['enum']);
    }

    public function testSchemaDropsADefaultTheFieldDoesNotShow(): void
    {
        $shown = $this->registerField(['palettes' => ['theme'], 'default_value' => 'primary']);
        $hidden = $this->registerField(['palettes' => ['theme'], 'default_value' => 'vivid-red']);

        $this->assertSame('primary', $this->fieldType()->get_rest_schema($shown)['default']);
        $this->assertArrayNotHasKey('default', $this->fieldType()->get_rest_schema($hidden));
    }

    public function testWritesAColor(): void
    {
        $field = $this->registerRestField();
        $postId = self::factory()->post->create();
        $this->acting_as('administrator');

        $this->post_json("/wp-json/wp/v2/posts/{$postId}", ['acf' => [$field['name'] => 'primary']])
            ->assertOk();

        $this->assertSame('primary', \get_post_meta($postId, $field['name'], true));
    }

    public function testRejectsAnInvalidColor(): void
    {
        $field = $this->registerRestField(['palettes' => ['theme']]);
        $postId = self::factory()->post->create();
        $this->acting_as('administrator');

        $this->post_json("/wp-json/wp/v2/posts/{$postId}", ['acf' => [$field['name'] => 'vivid-red']])
            ->assertStatus(400);

        $this->assertSame('', \get_post_meta($postId, $field['name'], true));
    }

    public function testValidatesAgainstTheFieldColors(): void
    {
        $field = $this->registerField(['palettes' => ['theme']]);

        $this->assertTrue($this->fieldType()->validate_rest_value(true, 'primary', $field));
        $this->assertInstanceOf(\WP_Error::class, $this->fieldType()->validate_rest_value(true, 'vivid-red', $field));
        $this->assertInstanceOf(\WP_Error::class, $this->fieldType()->validate_rest_value(true, '', $field));
    }

    public function testEmptiesAFieldThatIsNotRequired(): void
    {
        $this->assertTrue($this->fieldType()->validate_rest_value(true, null, $this->registerField()));
        $this->assertInstanceOf(\WP_Error::class, $this->fieldType()->validate_rest_value(true, null, $this->registerField(['required' => 1])));
    }

    public function testRejectsEveryColorWhenTheFieldHasNone(): void
    {
        // No custom palette in the tests.
        $field = $this->registerField(['palettes' => ['custom']]);

        $this->assertInstanceOf(\WP_Error::class, $this->fieldType()->validate_rest_value(true, 'primary', $field));
    }

    public function testKeepsAnEarlierValidationError(): void
    {
        $error = new \WP_Error('rest_invalid_param', 'Invalid');

        $this->assertSame($error, $this->fieldType()->validate_rest_value($error, 'primary', $this->registerField()));
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function registerRestField(array $settings = []): array
    {
        // A unique name: REST resolves fields by name across all field groups.
        return $this->registerField(
            $settings + ['name' => 'color_' . \uniqid()],
            ['show_in_rest' => 1]
        );
    }
}
