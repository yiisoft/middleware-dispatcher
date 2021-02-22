<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher
{
    /**
     * Contains a stack of middleware handler.
     *
     * @var MiddlewareStackInterface stack of middleware
     */
    private MiddlewareStackInterface $stack;

    private MiddlewareFactoryInterface $middlewareFactory;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    public function __construct(MiddlewareFactoryInterface $middlewareFactory, MiddlewareStackInterface $stack)
    {
        $this->middlewareFactory = $middlewareFactory;
        $this->stack = $stack;
    }

    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface
    {
        if ($this->stack->isEmpty()) {
            $this->stack = $this->stack->build($this->buildMiddlewares(), $fallbackHandler);
        }

        return $this->stack->handle($request);
    }

    /**
     * Returns new instance with middleware handlers replaced.
     * Last specified handler will be executed first.
     *
     * @param array[]|callable[]|string[] $middlewareDefinitions Each array element is a name of PSR-15 middleware,
     * a callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`
     * signature or a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable
     * typed parameters are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $clone = clone $this;
        $clone->middlewareDefinitions = $middlewareDefinitions;
        $clone->stack->reset();

        return $clone;
    }

    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @return MiddlewareInterface[]
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = $this->middlewareFactory->create($middlewareDefinition);
        }

        return $middlewares;
    }
}
