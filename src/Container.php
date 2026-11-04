<?php

declare(strict_types=1);

namespace n5s\AcfColorPalette;

use InvalidArgumentException;
use n5s\AcfColorPalette\Field\ColorPaletteField;
use n5s\AcfColorPalette\Integration\AdminColumns;
use n5s\AcfColorPalette\Integration\WpGraphQl;

/**
 * Builds services on first use.
 *
 * @internal Not part of the public API; may change in minor versions.
 */
final class Container
{
    /**
     * @var array<class-string, object>
     */
    private array $services = [];

    /**
     * @var array<class-string, callable(): object>
     */
    private array $factories;

    public function __construct(string $pluginDir)
    {
        $this->factories = [
            Palettes::class => static fn (): Palettes => new Palettes(),
            ColorPaletteField::class => fn (): ColorPaletteField => new ColorPaletteField(
                \untrailingslashit(\plugin_dir_url($pluginDir . '/acf-color-palette.php')),
                $pluginDir,
                $this->get(Palettes::class)
            ),
            AdminColumns::class => fn (): AdminColumns => new AdminColumns(
                $this->get(Palettes::class)
            ),
            WpGraphQl::class => fn (): WpGraphQl => new WpGraphQl(
                $this->get(Palettes::class)
            ),
        ];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $name
     *
     * @return T
     */
    public function get(string $name): object
    {
        if (!isset($this->services[$name])) {
            if (!isset($this->factories[$name])) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new InvalidArgumentException("Unknown service: {$name}");
            }

            $this->services[$name] = $this->factories[$name]();
        }

        /** @var T $service */
        $service = $this->services[$name];

        return $service;
    }
}
