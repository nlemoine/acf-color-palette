<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette\Integration;

use AC\Column\CustomFieldContext;
use n5s\AcfColorPalette\Palettes;

/**
 * Admin Columns 7+: color swatches and names in custom field columns.
 *
 * The hook callback methods stay public so they can be removed with remove_filter().
 *
 * @internal Not part of the public API; may change in minor versions.
 */
final readonly class AdminColumns implements IntegrationInterface
{
    public function __construct(private Palettes $palettes)
    {
    }

    public function registerHooks(): void
    {
        \add_filter('ac/column/render', [$this, 'render'], 10, 3);
    }

    public function render(mixed $value, mixed $context, mixed $id): mixed
    {
        if (!$context instanceof CustomFieldContext || !\is_numeric($id)) {
            return $value;
        }

        // Admin Columns can be active without ACF. Checked when rendering: ACF bundled in a theme loads after plugins.
        if (!\function_exists('get_field_object')) {
            return $value;
        }

        // ACF post ID format for each Admin Columns meta type.
        $postId = [
            'post' => (int) $id,
            'user' => 'user_' . $id,
            'term' => 'term_' . $id,
            'comment' => 'comment_' . $id,
        ][$context->get_meta_type()] ?? null;
        if ($postId === null) {
            return $value;
        }

        $field = \get_field_object($context->get_meta_key(), $postId, false);
        if (!\is_array($field) || $field['type'] !== 'color_palette' || !\is_string($field['value'])) {
            return $value;
        }

        $color = $this->palettes->find($field['value']);
        if ($color === null) {
            return $value;
        }

        return \sprintf(
            '<span style="display: inline-block; width: 1em; height: 1em; margin-inline-end: 0.4em; border: 1px solid rgba(0, 0, 0, 0.2); border-radius: 50%%; vertical-align: middle; background-color: %s"></span>%s',
            \esc_attr($color['color']),
            \esc_html($color['name'])
        );
    }
}
