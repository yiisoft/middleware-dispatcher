<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Yiisoft\CodeStyle\Rector\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withSets([
        SetList::YII_CORE,
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ArrayToFirstClassCallableRector::class => [
            __DIR__ . '/tests/MiddlewareFactoryTest.php',
        ],
    ]);
