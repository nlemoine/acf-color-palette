<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Tests\Integration\WpGraphQl;

use n5s\AcfColorPalette\Integration\WpGraphQl;
use n5s\AcfColorPalette\Plugin;
use n5s\AcfColorPalette\ReturnFormat;
use n5s\AcfColorPalette\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class GraphqlTest extends TestCase
{
    private const string QUERY = <<<'GRAPHQL'
        query ($id: ID!) {
          post(id: $id, idType: DATABASE_ID) {
            colorDetails { color { slug name color } }
          }
        }
        GRAPHQL;

    protected function setUp(): void
    {
        parent::setUp();

        // Local field groups outlive a test: earlier groups would shadow the GraphQL names of this one.
        \acf_get_local_store('groups')->reset();
        \acf_get_local_store('fields')->reset();
    }

    protected function tearDown(): void
    {
        // The show_in_graphql groups of this test must not leak into later tests.
        \acf_get_local_store('groups')->reset();
        \acf_get_local_store('fields')->reset();

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function returnFormats(): iterable
    {
        foreach (ReturnFormat::cases() as $format) {
            yield $format->value => [$format->value];
        }
    }

    #[DataProvider('returnFormats')]
    public function testResolvesTheColorWhateverTheReturnFormat(string $format): void
    {
        $postId = $this->postWithColor(['return_format' => $format], 'primary');

        $this->assertSame(['slug' => 'primary', 'name' => 'Primary', 'color' => '#0d6efd'], $this->color($postId));
    }

    public function testResolvesAnEmptyFieldToNull(): void
    {
        $this->assertNull($this->color($this->postWithColor([], '')));
    }

    public function testResolvesAColorNoLongerInThePalettes(): void
    {
        $postId = $this->postWithColor([], 'primary');
        \update_post_meta($postId, self::FIELD_NAME, 'removed');

        $this->assertSame(['slug' => 'removed', 'name' => null, 'color' => null], $this->color($postId));
    }

    public function testResolvesAFormattedRow(): void
    {
        $integration = Plugin::getInstance()->getContainer()->get(WpGraphQl::class);

        $this->assertSame(
            ['slug' => 'light', 'name' => 'Light', 'color' => '#f8f9fa'],
            $integration->resolve(['name' => 'Light', 'slug' => 'light', 'color' => '#f8f9fa'])
        );
        $this->assertNull($integration->resolve(['primary']));
    }

    public function testSchemaDeclaresTheType(): void
    {
        $this->postWithColor([], 'primary');

        // WPGraphQL disables public introspection by default.
        $this->acting_as('administrator');
        $result = \graphql(['query' => '{ __type(name: "AcfColorPaletteColor") { fields { name } } }']);

        $this->assertArrayNotHasKey('errors', $result, (string) \json_encode($result['errors'] ?? []));
        $names = \array_column($result['data']['__type']['fields'] ?? [], 'name');
        \sort($names);
        $this->assertSame(['color', 'name', 'slug'], $names);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function postWithColor(array $settings, string $value): int
    {
        $field = $this->registerField(
            ['show_in_graphql' => 1, 'graphql_field_name' => 'color'] + $settings,
            ['show_in_graphql' => 1, 'graphql_field_name' => 'colorDetails']
        );
        \WPGraphQL::clear_schema();

        $postId = self::factory()->post->create(['post_status' => 'publish']);
        \update_field($field['key'], $value, $postId);

        return $postId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function color(int $postId): ?array
    {
        $result = \graphql(['query' => self::QUERY, 'variables' => ['id' => $postId]]);
        $this->assertArrayNotHasKey('errors', $result, (string) \json_encode($result['errors'] ?? []));

        return $result['data']['post']['colorDetails']['color'];
    }
}
