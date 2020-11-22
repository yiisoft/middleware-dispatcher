<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareFactoryInterface
{
    /**
     * @param array|callable|string $middlewareDefinition A name of PSR-15 middleware, a callable with
     * `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` signature or
     * a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable typed parameters
     * are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return MiddlewareInterface
     */
    public function create($middlewareDefinition): MiddlewareInterface;
}
