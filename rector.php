<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withPhpSets()
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true
    );
