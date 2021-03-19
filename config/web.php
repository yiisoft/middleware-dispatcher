<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewarePipeline;
use Yiisoft\Middleware\Dispatcher\MiddlewarePipelineInterface;

return [
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,
    MiddlewarePipelineInterface::class => MiddlewarePipeline::class,
];
