<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withCache(__DIR__ . '/tmp/rector')
    // PHP version comes from the "php" constraint in composer.json.
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        instanceOf: true,
        earlyReturn: true,
        phpunitCodeQuality: true,
        phpunitNarrowAsserts: true,
    )
    // PHPUnit migration rules, bound to the PHPUnit version in composer.lock.
    ->withComposerBased(phpunit: true)
    // WordPress and ACF signatures, so type inference does not go blind on their APIs.
    ->withPhpstanConfigs([__DIR__ . '/phpstan.neon.dist'])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withSkip([
        // WordPress hook callbacks stay array callables: closures cannot be removed with remove_action()/remove_filter().
        ArrayToFirstClassCallableRector::class,
        // acf_field methods stay untyped: if ACF declares them untyped in acf_field, a narrower type would be fatal.
        StrictArrayParamDimFetchRector::class => [__DIR__ . '/src/Field/ColorPaletteField.php'],
    ]);
