<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\Debug\MiddlewareCollector;
use Yiisoft\Middleware\Dispatcher\Event\AfterMiddleware;
use Yiisoft\Middleware\Dispatcher\Event\BeforeMiddleware;

if (!(bool) ($params['yiisoft/yii-debug']['enabled'] ?? false)) {
    return [];
}

return [
    BeforeMiddleware::class => [
        [MiddlewareCollector::class, 'collect'],
    ],
    AfterMiddleware::class => [
        [MiddlewareCollector::class, 'collect'],
    ],
];
