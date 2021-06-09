<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

/**
 * Creates a PSR-15 middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface MiddlewareFactoryInterface
{
    /**
     * Create a PSR-15 middleware based on definition provided.
     *
     * @param mixed $middlewareDefinition Middleware definition to use.
     */
    public function create($middlewareDefinition): MiddlewareInterface;
}
