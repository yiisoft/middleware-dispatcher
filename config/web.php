<?php

declare(strict_types=1);

use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\Middleware\Dispatcher\SimpleParametersResolver;

return [
    ParametersResolverInterface::class => SimpleParametersResolver::class,
];
