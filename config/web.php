<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Middleware\Dispatcher\WrapperFactory;
use Yiisoft\Middleware\Dispatcher\WrapperFactoryInterface;

return [
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,
    WrapperFactoryInterface::class => WrapperFactory::class,
];
