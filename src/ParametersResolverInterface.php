<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionParameter;

/**
 * Creates a PSR-15 middleware that wraps the provided callable.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface ParametersResolverInterface
{
    /**
     * Create a PSR-15 middleware that wraps the provided callable.
     *
     * @param ReflectionParameter[] $parameters
     * @param ServerRequestInterface $request
     *
     * @return array<array-key, mixed>
     */
    public function resolve(array $parameters, ServerRequestInterface $request): array;
}
