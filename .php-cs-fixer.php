<?php

require_once __DIR__ . '/vendor/autoload.php';

use Valantic\PhpCsFixerConfig\ConfigFactory;

return ConfigFactory::createValanticConfig([
])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
    )
    // Enable risky rules (recommended as the ruleset includes risky rules)
    ->setRiskyAllowed(true)
    // Enable parallel execution
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ;
