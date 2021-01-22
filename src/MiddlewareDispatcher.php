<?php

declare(strict_types=1);

namespace Yiisoft\Middleware\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher
{
    /**
     * Contains a queue of middleware handler.
     *
     * @var MiddlewareQueueInterface queue of middleware
     */
    private MiddlewareQueueInterface $queue;

    private MiddlewareFactoryInterface $middlewareFactory;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    public function __construct(MiddlewareFactoryInterface $middlewareFactory, MiddlewareQueueInterface $queue)
    {
        $this->middlewareFactory = $middlewareFactory;
        $this->queue = $queue;
    }

    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface
    {
        if ($this->queue->isEmpty()) {
            $this->queue = $this->queue->build($this->buildMiddlewares(), $fallbackHandler);
        }

        return $this->queue->handle($request);
    }

    /**
     * Returns new instance with middleware handlers replaced.
     * Last specified handler will be executed first.
     *
     * @param array $middlewareDefinitions Each array element is a name of PSR-15 middleware, a callable with
     * `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` signature or
     * a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable typed parameters
     * are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $clone = clone $this;
        $clone->middlewareDefinitions = $middlewareDefinitions;
        $clone->queue->reset();

        return $clone;
    }

    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    private function buildMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = $this->middlewareFactory->create($middlewareDefinition);
        }

        return $middlewares;
    }
}
