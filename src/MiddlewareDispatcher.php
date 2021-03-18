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
     * Contains a pipeline of middleware handler.
     *
     * @var MiddlewarePipelineInterface The pipeline of middleware.
     */
    private MiddlewarePipelineInterface $pipeline;

    private MiddlewareFactoryInterface $factory;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    public function __construct(MiddlewareFactoryInterface $factory, MiddlewarePipelineInterface $pipeline)
    {
        $this->factory = $factory;
        $this->pipeline = $pipeline;
    }

    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface
    {
        if ($this->pipeline->isEmpty()) {
            $this->pipeline = $this->pipeline->build($this->buildMiddlewares(), $fallbackHandler);
        }

        return $this->pipeline->handle($request);
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
        $clone->pipeline->reset();

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
            $middlewares[] = $this->factory->create($middlewareDefinition);
        }

        return $middlewares;
    }
}
