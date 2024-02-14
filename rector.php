<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

return RectorConfig::configure()
    ->withPhpSets()
    ->withPreparedSets(
        codeQuality: true,
    )
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
    )
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSkip([
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        DisallowedEmptyRuleFixerRector::class,
        NullToStrictStringFuncCallArgRector::class,
        StringClassNameToClassConstantRector::class => [
            'src/DependencyInjection/Compiler/ExtensionCompilerPass.php',
            'src/DependencyInjection/Compiler/TransformerCompilerPass.php',
        ],
        IssetOnPropertyObjectToPropertyExistsRector::class,
        CountArrayToEmptyArrayComparisonRector::class,
        SimplifyIfElseToTernaryRector::class => [
            'src/Form/Transformer/OverwriteAbstractTransformerTrait.php',
        ],
        MixedTypeRector::class,
    ])
    ->withRootFiles();
