<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Valantic\PhpCsFixerConfig\ConfigFactory;

return ConfigFactory::createValanticConfig([
])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->append([
                __DIR__ . '/rector.php',
                __DIR__ . '/.php-cs-fixer.php',
            ]),
    )
    // Enable risky rules (recommended as the ruleset includes risky rules)
    ->setRiskyAllowed(true)
    // Enable parallel execution
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
;
