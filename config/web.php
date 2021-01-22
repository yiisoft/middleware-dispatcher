<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareQueue;
use Yiisoft\Middleware\Dispatcher\MiddlewareQueueInterface;

return [
    MiddlewareQueueInterface::class => MiddlewareQueue::class,
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,
];
