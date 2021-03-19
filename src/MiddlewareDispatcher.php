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
     * Contains a middleware pipeline handler.
     *
     * @var MiddlewarePipelineInterface The middleware pipeline.
     */
    private MiddlewarePipelineInterface $pipeline;

    private MiddlewareFactoryInterface $middlewareFactory;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    public function __construct(MiddlewareFactoryInterface $middlewareFactory, MiddlewarePipelineInterface $pipeline)
    {
        $this->middlewareFactory = $middlewareFactory;
        $this->pipeline = $pipeline;
    }

    /**
     * Builds and handles a new middleware pipeline. All added middleware definitions are cleared.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $fallbackHandler
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface
    {
        return $this->pipeline->build($this->buildMiddlewares(), $fallbackHandler)->handle($request);
    }

    /**
     * @param array|callable|string $middlewareDefinition Name of PSR-15 middleware,
     * a callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`
     * signature or a handler action (an array of [handlerClass, handlerMethod]). For handler action and callable
     * typed parameters are automatically injected using dependency injection container passed to the route.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function add($middlewareDefinition): self
    {
        $this->middlewareDefinitions[] = $middlewareDefinition;
        return $this;
    }

    /**
     * Creates middleware instances and clears middleware definitions.
     *
     * @return MiddlewareInterface[]
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];

        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = $this->middlewareFactory->create($middlewareDefinition);
        }

        $this->middlewareDefinitions = [];
        return $middlewares;
    }
}
