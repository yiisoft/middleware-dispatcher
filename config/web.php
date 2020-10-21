<?php

use Yiisoft\Middleware\Dispatcher\MiddlewareStack;
use Yiisoft\Middleware\Dispatcher\MiddlewareStackInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;

return [
    MiddlewareStackInterface::class => Middlewarestack::class,
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,
];
