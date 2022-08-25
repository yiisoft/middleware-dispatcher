<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Creates a PSR-15 middleware that wraps the provided callable.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface WrapperFactoryInterface
{
    /**
     * Create a PSR-15 middleware that wraps the provided callable.
     *
     * @param array{0:class-string, 1:string}|\Closure $callable
     */
    public function create($callable): MiddlewareInterface;
}
