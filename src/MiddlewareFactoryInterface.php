<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareFactoryInterface
{
    public function create($middlewareDefinition): MiddlewareInterface;
}
