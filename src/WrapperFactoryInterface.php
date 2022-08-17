<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Creates a PSR-15 middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface WrapperFactoryInterface
{
    /**
     * Create a PSR-15 middleware based on definition provided.
     *
     * @param callable $callback
     *
     * @return MiddlewareInterface
     */
    public function createCallableWrapper(callable $callback): MiddlewareInterface;

    public function createActionWrapper(string $class, string $method): MiddlewareInterface;
}
