# Upgrading from 0.x to 1.0

Stored values, the `color_palette` field type, its settings and return formats are unchanged: sites using `get_field()` and field groups built in the ACF UI need no change, apart from reactivating the plugin.

## Reactivate the plugin

The main plugin file was renamed from `plugin.php` to `acf-color-palette.php`. WordPress deactivates a plugin whose main file disappears: reactivate the plugin after updating. Sites that activate it in code, with `activate_plugin()` or a must-use loader, must use the new file name.

## PHP 8.3, WordPress 6.0 and ACF 6.0

1.0 requires PHP 8.3, WordPress 6.0 and ACF 6.0 (free or PRO). Sites on older versions can stay on 0.x.

## Installation without Composer

Install `acf-color-palette.zip` from the release assets. The "Source code" archives GitHub adds to each release no longer work as a plugin: they lack the autoloader. Git Updater picks the release asset automatically.

## Composer installs

0.x worked without an autoloader: the plugin file required its own class. 1.0 needs the site to load Composer's `vendor/autoload.php`, which Bedrock and similar setups already do. Otherwise the plugin shows an admin notice and stays inactive.

## Asset handles

The field's script and style handles changed from `color_palette` to `acf-color-palette-field`, which only matters if you dequeue them.

## Namespace and constants

Classes moved from `HelloNico\AcfColorPalette` to `n5s\AcfColorPalette`, and the class constants were replaced by enums. The old names are not kept as aliases.

| 0.x | 1.0 |
|---|---|
| `HelloNico\AcfColorPalette\ColorPaletteField` | `n5s\AcfColorPalette\Field\ColorPaletteField` |
| `ColorPaletteField::RETURN_SLUG` (and other `RETURN_*`) | `n5s\AcfColorPalette\ReturnFormat::Slug->value` |
| `ColorPaletteField::RETURN_FORMATS` | `n5s\AcfColorPalette\ReturnFormat::cases()` |
| `ColorPaletteField::PALETTE_THEME`, `PALETTE_DEFAULT` | `n5s\AcfColorPalette\Palette::Theme->value`, `Palette::Default->value` |

## Validation

- A submitted color must be one the field shows, given its palettes and included and excluded colors. 0.x accepted any color of any palette.
- An empty value is valid unless the field is required. 0.x rejected it when "Allow null" was off.

## Palettes

- The "Custom" palette (colors created in the Site Editor global styles) can now be selected in the field settings, and has its own heading. 0.x showed these colors without heading when no palette was selected.
- When no palette is selected, the field now shows the palettes of the block editor color picker: themes setting `settings.color.defaultPalette` to `false`, such as Twenty Twenty-Five, no longer get the WordPress default colors. Select the "Default" palette in the field settings to keep them.

## Keyboard

The swatch labels are no longer focusable (`tabindex="0"`): the radio buttons are, and arrow keys move between colors. The accessible name ("Color: …") moved from the label to the radio button.

## Admin Columns

The Admin Columns integration now supports Admin Columns 7+ (free or Pro) only. It never worked in 0.x.
