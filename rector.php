<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php84: true)
    ->withSets([
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
    ->withComposerBased(symfony: true)
    ->withPreparedSets(deadCode: true, typeDeclarations: true)
    ->withSkip([
        \Rector\Renaming\Rector\MethodCall\RenameMethodRector::class,
        \Rector\Symfony\Symfony60\Rector\MethodCall\GetHelperControllerToServiceRector::class,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
