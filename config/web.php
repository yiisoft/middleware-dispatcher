<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\WrapperFactory;
use Yiisoft\Middleware\Dispatcher\WrapperFactoryInterface;

return [
    WrapperFactoryInterface::class => WrapperFactory::class,
];
